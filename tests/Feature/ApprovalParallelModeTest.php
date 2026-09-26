<?php

use App\Models\Activity;
use App\Models\ApprovalMatrix;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStage;
use App\Models\Grade;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;

if (! function_exists('parallelGrantMenu')) {
    function parallelGrantMenu(Role $role, Menu $menu, array $actions): void
    {
        $flags = ['can_view' => false, 'can_create' => false, 'can_update' => false, 'can_delete' => false];
        foreach ($actions as $action) {
            $flags['can_'.$action] = true;
        }
        $role->menus()->syncWithoutDetaching([$menu->id => $flags]);
    }
}

if (! function_exists('parallelActingAdmin')) {
    function parallelActingAdmin(): User
    {
        $role = Role::factory()->create();
        $menu = Menu::factory()->create(['slug' => 'approval-matrices']);
        $role->menus()->sync([$menu->id => ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true]]);
        app(PermissionService::class)->flush();

        return User::factory()->create(['role_id' => $role->id]);
    }
}

function parallelApprovalScenario(array $stageRoleIds): array
{
    $gradesMenu = Menu::factory()->create(['slug' => 'grades']);
    $approvalsMenu = Menu::factory()->create(['slug' => 'approvals']);
    $matricesMenu = Menu::factory()->create(['slug' => 'approval-matrices']);
    $makerRole = Role::factory()->create();
    parallelGrantMenu($makerRole, $gradesMenu, ['view', 'create', 'update', 'delete']);
    parallelGrantMenu($makerRole, $approvalsMenu, ['view', 'delete']);
    $maker = User::factory()->create(['role_id' => $makerRole->id]);

    $approvers = [];
    foreach (array_unique($stageRoleIds) as $roleId) {
        $role = Role::query()->findOrFail($roleId);
        parallelGrantMenu($role, $approvalsMenu, ['view', 'update']);
        $approvers[$roleId] = User::factory()->create(['role_id' => $roleId]);
    }

    parallelGrantMenu($makerRole, $matricesMenu, ['view', 'update']);
    app(PermissionService::class)->flush();

    $service = app(ApprovalWorkflowService::class);
    $service->replaceConfiguration('grades', [
        'is_active' => true,
        'mode' => 'parallel',
        'maker_roles' => [$makerRole->id],
        'stages' => collect($stageRoleIds)->map(fn (int $roleId, int $index): array => [
            'stage_number' => $index + 1,
            'name' => 'Stage '.($index + 1),
            'role_ids' => [$roleId],
        ])->values()->all(),
    ], $maker);

    $request = $service->submit($maker, 'grades', 'create', null, ['name' => 'Parallel Grade', 'level' => 5, 'is_active' => true]);

    return compact('request', 'maker', 'approvers', 'service', 'makerRole', 'stageRoleIds');
}

test('approval mode is configured per module and validated', function () {
    $admin = parallelActingAdmin();
    $makerRole = Role::factory()->create();
    $approverRole = Role::factory()->create();
    $payload = [
        'module_key' => 'grades',
        'is_active' => '1',
        'mode' => 'parallel',
        'maker_roles' => [$makerRole->id],
        'stages' => [['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$approverRole->id]]],
    ];

    $this->actingAs($admin)->put(route('approval-matrices.update', 'grades'), $payload)
        ->assertRedirect(route('approval-matrices.index'));

    expect(ApprovalMatrix::query()->where('module_key', 'grades')->sole()->mode)->toBe('parallel');

    $entry = Activity::query()->where('log_name', 'approval-matrix')->where('event', 'configuration_replaced')->latest('id')->first();
    expect($entry->properties['after']['mode'])->toBe('parallel');

    $this->actingAs($admin)->put(route('approval-matrices.update', 'grades'), [...$payload, 'mode' => 'sideways'])
        ->assertSessionHasErrors('mode');

    $this->actingAs(User::factory()->create())->put(route('approval-matrices.update', 'grades'), $payload)
        ->assertForbidden();
});

test('parallel submit snapshots mode and in-flight requests keep it', function () {
    $stageRoleIds = collect(range(1, 2))->map(fn (): int => Role::factory()->create()->id)->all();
    $scenario = parallelApprovalScenario($stageRoleIds);

    expect($scenario['request']->approval_mode)->toBe('parallel');

    $scenario['service']->replaceConfiguration('grades', [
        'is_active' => true,
        'mode' => 'sequential',
        'maker_roles' => [$scenario['makerRole']->id],
        'stages' => collect($scenario['stageRoleIds'])->map(fn (int $roleId, int $index): array => [
            'stage_number' => $index + 1,
            'name' => 'Stage '.($index + 1),
            'role_ids' => [$roleId],
        ])->values()->all(),
    ], $scenario['maker']);
    $second = $scenario['service']->submit($scenario['maker'], 'grades', 'create', null, ['name' => 'Sequential After Flip', 'level' => 6, 'is_active' => true]);

    expect($second->approval_mode)->toBe('sequential')
        ->and($scenario['request']->fresh()->approval_mode)->toBe('parallel');
});

test('parallel approval from any stage completes and skips the rest', function () {
    $stageRoleIds = collect(range(1, 3))->map(fn (): int => Role::factory()->create()->id)->all();
    $scenario = parallelApprovalScenario($stageRoleIds);
    $request = $scenario['request'];
    $lastRoleId = $stageRoleIds[2];

    $this->actingAs($scenario['approvers'][$lastRoleId])
        ->post(route('approvals.approve', $request), ['comment' => 'Stage three wins'])
        ->assertRedirect(route('approvals.show', $request));

    $request->refresh()->load('stages');
    expect($request->status)->toBe(ApprovalRequest::STATUS_APPROVED)
        ->and($request->current_stage)->toBe(3)
        ->and($request->stages->firstWhere('stage_number', 3)->status)->toBe(ApprovalRequestStage::STATUS_APPROVED)
        ->and($request->stages->firstWhere('stage_number', 1)->status)->toBe(ApprovalRequestStage::STATUS_SKIPPED)
        ->and($request->stages->firstWhere('stage_number', 2)->status)->toBe(ApprovalRequestStage::STATUS_SKIPPED)
        ->and(Grade::query()->where('name', 'Parallel Grade')->exists())->toBeTrue();
});

test('parallel rejection rejects the request and leaves siblings pending', function () {
    $stageRoleIds = collect(range(1, 3))->map(fn (): int => Role::factory()->create()->id)->all();
    $scenario = parallelApprovalScenario($stageRoleIds);
    $request = $scenario['request'];
    $middleRoleId = $stageRoleIds[1];

    $this->actingAs($scenario['approvers'][$middleRoleId])
        ->post(route('approvals.reject', $request))
        ->assertSessionHasErrors('comment');

    $this->actingAs($scenario['approvers'][$middleRoleId])
        ->post(route('approvals.reject', $request), ['comment' => 'Needs work'])
        ->assertRedirect(route('approvals.show', $request));

    $request->refresh()->load('stages');
    expect($request->status)->toBe(ApprovalRequest::STATUS_REJECTED)
        ->and($request->stages->firstWhere('stage_number', 2)->status)->toBe(ApprovalRequestStage::STATUS_REJECTED)
        ->and($request->stages->firstWhere('stage_number', 1)->status)->toBe(ApprovalRequestStage::STATUS_PENDING)
        ->and($request->stages->firstWhere('stage_number', 3)->status)->toBe(ApprovalRequestStage::STATUS_PENDING)
        ->and(Grade::query()->where('name', 'Parallel Grade')->exists())->toBeFalse();
});

test('parallel guards maker, repeats, and completed requests', function () {
    $stageRoleIds = collect(range(1, 2))->map(fn (): int => Role::factory()->create()->id)->all();
    $scenario = parallelApprovalScenario($stageRoleIds);
    $request = $scenario['request'];
    $firstRoleId = $stageRoleIds[0];
    parallelGrantMenu($scenario['makerRole'], Menu::query()->where('slug', 'approvals')->sole(), ['view', 'update', 'delete']);
    app(PermissionService::class)->flush();

    $this->actingAs($scenario['maker'])->post(route('approvals.approve', $request))
        ->assertSessionHasErrors('decision');

    $this->actingAs($scenario['approvers'][$firstRoleId])
        ->post(route('approvals.approve', $request), ['comment' => 'First wins'])
        ->assertRedirect(route('approvals.show', $request));

    $this->actingAs($scenario['approvers'][$firstRoleId])
        ->post(route('approvals.approve', $request))
        ->assertSessionHasErrors('approval');

    $otherRoleId = $stageRoleIds[1];
    $this->actingAs($scenario['approvers'][$otherRoleId])
        ->post(route('approvals.approve', $request))
        ->assertSessionHasErrors('approval');

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);
});

test('parallel awaiting scope includes later-stage approvers', function () {
    $stageRoleIds = collect(range(1, 2))->map(fn (): int => Role::factory()->create()->id)->all();
    $scenario = parallelApprovalScenario($stageRoleIds);
    $request = $scenario['request'];
    $laterApprover = $scenario['approvers'][$stageRoleIds[1]];

    $this->actingAs($laterApprover)->get(route('approvals.index', ['scope' => 'awaiting']))
        ->assertOk()
        ->assertSee('#'.$request->id);
});
