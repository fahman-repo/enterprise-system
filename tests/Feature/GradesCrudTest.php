<?php

use App\Models\ApprovalRequest;
use App\Models\Employee;
use App\Models\Grade;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;

function actingGradeAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'grades']);
    $role->menus()->sync([$menu->id => [
        'can_view' => true,
        'can_create' => true,
        'can_update' => true,
        'can_delete' => true,
    ]]);
    app(PermissionService::class)->flush();

    return User::factory()->create(['role_id' => $role->id]);
}

beforeEach(function () {
    $this->maker = actingGradeAdmin();
    $this->approverRole = Role::factory()->create();
    $this->actingAs($this->maker);

    app(ApprovalWorkflowService::class)->replaceConfiguration('grades', [
        'is_active' => true,
        'maker_roles' => [$this->maker->role_id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$this->approverRole->id]],
        ],
    ], $this->maker);
});

test('grades index lists grades', function () {
    Grade::factory()->create(['name' => 'Quixotic Grade']);

    $this->get(route('grades.index'))
        ->assertOk()
        ->assertSee('Quixotic Grade');
});

test('grades index filters by status', function () {
    $active = Grade::factory()->create(['name' => 'Active Grade']);
    $inactive = Grade::factory()->inactive()->create(['name' => 'Inactive Grade']);

    $this->get(route('grades.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee($inactive->name)
        ->assertDontSee($active->name);
});

test('grade create route submits a request without creating a grade', function () {
    $this->post(route('grades.store'), [
        'name' => 'New Grade',
        'level' => 3,
        'is_active' => '1',
    ])->assertRedirect(route('grades.index', ['tab' => 'requests']));

    $this->assertDatabaseMissing('grades', ['name' => 'New Grade']);
    $this->assertDatabaseHas('approval_requests', [
        'module_key' => 'grades',
        'action' => ApprovalRequest::ACTION_CREATE,
        'status' => ApprovalRequest::STATUS_PENDING,
    ]);
    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'approval',
        'event' => 'submitted',
    ]);
});

test('grade update route submits a request without changing the grade', function () {
    $grade = Grade::factory()->create(['name' => 'Original Grade', 'level' => 2]);

    $this->put(route('grades.update', $grade), [
        'name' => 'Renamed Grade',
        'level' => 5,
        'is_active' => '0',
    ])->assertRedirect(route('grades.index', ['tab' => 'requests']));

    expect($grade->fresh()->name)->toBe('Original Grade')
        ->and($grade->fresh()->level)->toBe(2)
        ->and($grade->fresh()->is_active)->toBeTrue();
    $this->assertDatabaseHas('approval_requests', [
        'module_key' => 'grades',
        'action' => ApprovalRequest::ACTION_UPDATE,
        'target_id' => $grade->id,
        'status' => ApprovalRequest::STATUS_PENDING,
    ]);
});

test('grade name must be unique when submitting', function () {
    $existing = Grade::factory()->create(['name' => 'Taken Grade']);

    $this->post(route('grades.store'), [
        'name' => $existing->name,
        'level' => 1,
    ])->assertSessionHasErrors('name');

    $this->assertDatabaseCount('approval_requests', 0);
});

test('a grade assigned to employees cannot request deletion', function () {
    $grade = Grade::factory()->create();
    Employee::factory()->create(['grade_id' => $grade->id]);

    $this->delete(route('grades.destroy', $grade))->assertSessionHasErrors('grade');

    $this->assertModelExists($grade);
    $this->assertDatabaseCount('approval_requests', 0);
});

test('unused grade deletion route submits a request without deleting the grade', function () {
    $grade = Grade::factory()->create();

    $this->delete(route('grades.destroy', $grade))
        ->assertRedirect(route('grades.index', ['tab' => 'requests']));

    $this->assertModelExists($grade);
    $this->assertDatabaseHas('approval_requests', [
        'module_key' => 'grades',
        'action' => ApprovalRequest::ACTION_DELETE,
        'target_id' => $grade->id,
        'status' => ApprovalRequest::STATUS_PENDING,
    ]);
});

test('grades apply directly when no approval matrix is active', function () {
    app(ApprovalWorkflowService::class)->replaceConfiguration('grades', [
        'is_active' => false,
        'maker_roles' => [$this->maker->role_id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$this->approverRole->id]],
        ],
    ], $this->maker);

    $this->post(route('grades.store'), ['name' => 'Direct Grade', 'level' => 2])
        ->assertRedirect(route('grades.index'));
    $this->assertDatabaseHas('grades', ['name' => 'Direct Grade', 'level' => 2]);

    $grade = Grade::query()->where('name', 'Direct Grade')->sole();

    $this->put(route('grades.update', $grade), ['name' => 'Renamed Direct', 'level' => 4])
        ->assertRedirect(route('grades.index'));
    expect($grade->fresh()->name)->toBe('Renamed Direct')
        ->and($grade->fresh()->level)->toBe(4);

    $this->delete(route('grades.destroy', $grade))->assertRedirect(route('grades.index'));
    $this->assertModelMissing($grade);

    $this->assertDatabaseCount('approval_requests', 0);
});
