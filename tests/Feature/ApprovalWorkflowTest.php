<?php

use App\Models\Activity;
use App\Models\ApprovalRequest;
use App\Models\Employee;
use App\Models\Grade;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;

function grantApprovalMenu(Role $role, Menu $menu, array $actions): void
{
    $flags = [
        'can_view' => false,
        'can_create' => false,
        'can_update' => false,
        'can_delete' => false,
    ];

    foreach ($actions as $action) {
        $flags['can_'.$action] = true;
    }

    $role->menus()->syncWithoutDetaching([$menu->id => $flags]);
}

function gradeApprovalScenario(array $stageRoleIds, string $action = 'create', ?Grade $grade = null, array $payload = []): array
{
    $gradesMenu = Menu::factory()->create(['slug' => 'grades']);
    $approvalsMenu = Menu::factory()->create(['slug' => 'approvals']);
    $makerRole = Role::factory()->create();
    grantApprovalMenu($makerRole, $gradesMenu, ['view', 'create', 'update', 'delete']);
    grantApprovalMenu($makerRole, $approvalsMenu, ['view', 'delete']);
    $maker = User::factory()->create(['role_id' => $makerRole->id]);

    $approvers = [];
    foreach (array_unique($stageRoleIds) as $roleId) {
        $role = Role::query()->findOrFail($roleId);
        grantApprovalMenu($role, $approvalsMenu, ['view', 'update']);
        $approvers[$roleId] = User::factory()->create(['role_id' => $roleId]);
    }

    app(PermissionService::class)->flush();
    $service = app(ApprovalWorkflowService::class);
    $service->replaceConfiguration('grades', [
        'is_active' => true,
        'maker_roles' => [$makerRole->id],
        'stages' => collect($stageRoleIds)->map(fn (int $roleId, int $index): array => [
            'stage_number' => $index + 1,
            'name' => 'Stage '.($index + 1),
            'role_ids' => [$roleId],
        ])->values()->all(),
    ], $maker);

    $request = $service->submit($maker, 'grades', $action, $grade?->id, $payload);

    return [
        'request' => $request,
        'maker' => $maker,
        'approvers' => $approvers,
        'service' => $service,
    ];
}

test('requests require one, two, or three sequential approvals', function (int $stageCount) {
    $stageRoleIds = collect(range(1, $stageCount))->map(fn (): int => Role::factory()->create()->id)->all();
    $scenario = gradeApprovalScenario($stageRoleIds, payload: ['name' => 'Sequential Grade', 'level' => 4, 'is_active' => true]);
    $request = $scenario['request'];

    expect($request->stages->pluck('stage_number')->all())->toBe(range(1, $stageCount));

    foreach ($stageRoleIds as $index => $roleId) {
        $this->actingAs($scenario['approvers'][$roleId])
            ->post(route('approvals.approve', $request), ['comment' => 'Approved '.($index + 1)])
            ->assertRedirect(route('approvals.show', $request));

        $request->refresh();
        expect($request->status)->toBe($index === $stageCount - 1 ? ApprovalRequest::STATUS_APPROVED : ApprovalRequest::STATUS_PENDING)
            ->and($request->current_stage)->toBe(min($index + 2, $stageCount));
    }

    expect(Grade::query()->where('name', 'Sequential Grade')->exists())->toBeTrue();
})->with([
    'one stage' => [1],
    'two stages' => [2],
    'three stages' => [3],
]);

test('final approval atomically applies create, update, and delete grade requests', function (string $action) {
    $grade = $action === 'create' ? null : Grade::factory()->create(['name' => 'Original Grade']);
    $payload = match ($action) {
        'create' => ['name' => 'Approved Create', 'level' => 7, 'description' => null, 'is_active' => true],
        'update' => ['name' => 'Approved Update', 'level' => 8, 'description' => 'Updated', 'is_active' => false],
        'delete' => [],
    };
    $approverRole = Role::factory()->create();
    $scenario = gradeApprovalScenario([$approverRole->id], $action, $grade, $payload);
    $request = $scenario['request'];

    $this->actingAs($scenario['approvers'][$approverRole->id])
        ->post(route('approvals.approve', $request), ['comment' => 'Final approval'])
        ->assertRedirect(route('approvals.show', $request));

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);

    if ($action === 'create') {
        expect(Grade::query()->where('name', 'Approved Create')->exists())->toBeTrue();
    }

    if ($action === 'update') {
        expect($grade->fresh()->name)->toBe('Approved Update')
            ->and($grade->fresh()->level)->toBe(8)
            ->and($grade->fresh()->is_active)->toBeFalse();
    }

    if ($action === 'delete') {
        $this->assertModelMissing($grade);
    }

    expect(Activity::query()->where('log_name', 'approval')->where('event', 'approved')->exists())->toBeTrue();
    expect(Activity::query()->where('log_name', 'grade')->where('event', match ($action) {
        'create' => 'created',
        'update' => 'updated',
        'delete' => 'deleted',
    })->exists())->toBeTrue();
})->with([
    'create' => ['create'],
    'update' => ['update'],
    'delete' => ['delete'],
]);

test('only current-stage eligible roles may decide in stage order', function () {
    $firstRole = Role::factory()->create();
    $secondRole = Role::factory()->create();
    $scenario = gradeApprovalScenario([$firstRole->id, $secondRole->id], payload: ['name' => 'Eligible Grade', 'level' => 1, 'is_active' => true]);
    $request = $scenario['request'];
    $ineligibleRole = Role::factory()->create();
    grantApprovalMenu($ineligibleRole, Menu::query()->where('slug', 'approvals')->sole(), ['view', 'update']);
    $ineligible = User::factory()->create(['role_id' => $ineligibleRole->id]);
    app(PermissionService::class)->flush();

    $this->actingAs($scenario['approvers'][$secondRole->id])
        ->post(route('approvals.approve', $request))
        ->assertSessionHasErrors('decision');
    $this->actingAs($ineligible)
        ->post(route('approvals.approve', $request))
        ->assertSessionHasErrors('decision');

    $this->actingAs($scenario['approvers'][$firstRole->id])
        ->post(route('approvals.approve', $request))
        ->assertRedirect(route('approvals.show', $request));

    $this->actingAs($scenario['approvers'][$firstRole->id])
        ->post(route('approvals.approve', $request))
        ->assertSessionHasErrors('decision');
    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING);
});

test('requesters cannot approve their own requests', function () {
    $approverRole = Role::factory()->create();
    $scenario = gradeApprovalScenario([$approverRole->id], payload: ['name' => 'Self Approval Grade', 'level' => 1, 'is_active' => true]);
    $request = $scenario['request'];
    $scenario['maker']->update(['role_id' => $approverRole->id]);
    app(PermissionService::class)->flush();

    $this->actingAs($scenario['maker'])
        ->post(route('approvals.approve', $request))
        ->assertSessionHasErrors('decision');

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING);
});

test('each user may decide only once per request', function () {
    $sharedRole = Role::factory()->create();
    $scenario = gradeApprovalScenario([$sharedRole->id, $sharedRole->id], payload: ['name' => 'Shared Approver Grade', 'level' => 1, 'is_active' => true]);
    $request = $scenario['request'];
    $firstApprover = $scenario['approvers'][$sharedRole->id];

    $this->actingAs($firstApprover)->post(route('approvals.approve', $request))->assertRedirect();
    $this->actingAs($firstApprover)->post(route('approvals.approve', $request))->assertSessionHasErrors('decision');

    $secondApprover = User::factory()->create(['role_id' => $sharedRole->id]);
    $this->actingAs($secondApprover)->post(route('approvals.approve', $request))->assertRedirect();

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);
});

test('rejection requires a comment and leaves the grade unchanged', function () {
    $approverRole = Role::factory()->create();
    $scenario = gradeApprovalScenario([$approverRole->id], payload: ['name' => 'Rejected Grade', 'level' => 1, 'is_active' => true]);
    $request = $scenario['request'];
    $approver = $scenario['approvers'][$approverRole->id];

    $this->actingAs($approver)->post(route('approvals.reject', $request))->assertSessionHasErrors('comment');
    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING);

    $this->actingAs($approver)
        ->post(route('approvals.reject', $request), ['comment' => 'Not aligned with policy'])
        ->assertRedirect(route('approvals.show', $request));

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_REJECTED)
        ->and($request->fresh()->resolution_comment)->toBe('Not aligned with policy');
    expect(Activity::query()->where('log_name', 'approval')->where('event', 'rejected')->exists())->toBeTrue();
    $this->assertDatabaseMissing('grades', ['name' => 'Rejected Grade']);
});

test('makers can cancel pending requests but other users cannot', function () {
    $approverRole = Role::factory()->create();
    $scenario = gradeApprovalScenario([$approverRole->id], payload: ['name' => 'Cancelled Grade', 'level' => 1, 'is_active' => true]);
    $request = $scenario['request'];
    $outsiderRole = Role::factory()->create();
    grantApprovalMenu($outsiderRole, Menu::query()->where('slug', 'approvals')->sole(), ['view', 'delete']);
    $outsider = User::factory()->create(['role_id' => $outsiderRole->id]);
    app(PermissionService::class)->flush();

    $this->actingAs($outsider)->post(route('approvals.cancel', $request))->assertSessionHasErrors('approval');
    $this->actingAs($scenario['maker'])
        ->post(route('approvals.cancel', $request), ['comment' => 'No longer needed'])
        ->assertRedirect(route('approvals.show', $request));

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_CANCELLED)
        ->and($request->fresh()->resolution_comment)->toBe('No longer needed');
    expect(Activity::query()->where('log_name', 'approval')->where('event', 'cancelled')->exists())->toBeTrue();
    $this->assertDatabaseMissing('grades', ['name' => 'Cancelled Grade']);
});

test('matrix changes do not alter in-flight request snapshots', function () {
    $oldApproverRole = Role::factory()->create();
    $scenario = gradeApprovalScenario([$oldApproverRole->id], payload: ['name' => 'Snapshot Grade', 'level' => 1, 'is_active' => true]);
    $request = $scenario['request'];
    $newMakerRole = Role::factory()->create();
    $newApproverRole = Role::factory()->create();

    $scenario['service']->replaceConfiguration('grades', [
        'is_active' => true,
        'maker_roles' => [$newMakerRole->id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Replacement Stage', 'role_ids' => [$newApproverRole->id]],
        ],
    ], $scenario['maker']);

    expect($request->fresh()->stages->sole()->name)->toBe('Stage 1')
        ->and($request->fresh()->matrix_configuration_version)->toBe(1);

    $this->actingAs($scenario['approvers'][$oldApproverRole->id])
        ->post(route('approvals.approve', $request))
        ->assertRedirect(route('approvals.show', $request));

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);
});

test('stale update conflicts remain pending without partially applying values', function () {
    $grade = Grade::factory()->create(['name' => 'Stale Grade', 'level' => 2]);
    $approverRole = Role::factory()->create();
    $scenario = gradeApprovalScenario(
        [$approverRole->id],
        'update',
        $grade,
        ['name' => 'Proposed Grade', 'level' => 8, 'description' => null, 'is_active' => true],
    );
    $request = $scenario['request'];
    $grade->update(['level' => 99]);

    $this->actingAs($scenario['approvers'][$approverRole->id])
        ->post(route('approvals.approve', $request))
        ->assertSessionHasErrors('approval');

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING)
        ->and($grade->fresh()->name)->toBe('Stale Grade')
        ->and($grade->fresh()->level)->toBe(99);
});

test('duplicate grade names conflict at final approval and remain pending', function () {
    $approverRole = Role::factory()->create();
    $scenario = gradeApprovalScenario(
        [$approverRole->id],
        payload: ['name' => 'Conflict Grade', 'level' => 1, 'description' => null, 'is_active' => true],
    );
    $request = $scenario['request'];
    Grade::factory()->create(['name' => 'Conflict Grade']);

    $this->actingAs($scenario['approvers'][$approverRole->id])
        ->post(route('approvals.approve', $request))
        ->assertSessionHasErrors('name');

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING);
});

test('deletion blocked by later employee assignments remains pending', function () {
    $grade = Grade::factory()->create();
    $approverRole = Role::factory()->create();
    $scenario = gradeApprovalScenario([$approverRole->id], 'delete', $grade);
    $request = $scenario['request'];
    Employee::factory()->create(['grade_id' => $grade->id]);

    $this->actingAs($scenario['approvers'][$approverRole->id])
        ->post(route('approvals.approve', $request))
        ->assertSessionHasErrors('approval');

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING);
    $this->assertModelExists($grade);
});

test('duplicate pending update and delete requests are rejected', function (string $action) {
    $grade = Grade::factory()->create(['name' => 'Duplicate Target', 'level' => 1]);
    $approverRole = Role::factory()->create();
    $payload = $action === 'update'
        ? ['name' => 'Duplicate Update', 'level' => 2, 'description' => null, 'is_active' => true]
        : [];
    $scenario = gradeApprovalScenario([$approverRole->id], $action, $grade, $payload);
    $this->actingAs($scenario['maker']);

    $submission = $action === 'update'
        ? $this->put(route('grades.update', $grade), $payload)
        : $this->delete(route('grades.destroy', $grade));

    $submission->assertSessionHasErrors('approval');
    expect(ApprovalRequest::query()->where('target_id', $grade->id)->where('status', ApprovalRequest::STATUS_PENDING)->count())->toBe(1);
    expect($scenario['request']->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING);
})->with([
    'update' => ['update'],
    'delete' => ['delete'],
]);
