<?php

use App\Models\ApprovalRequest;
use App\Models\DevelopmentEnrollment;
use App\Models\DevelopmentProgram;
use App\Models\Employee;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;

function actingDevelopmentAdmin(): User
{
    $role = Role::factory()->create();
    // The data migration seeds this slug under RefreshDatabase, so reuse it when present.
    $menu = Menu::query()->firstOrCreate(['slug' => 'development-programs'], ['name' => 'Development Programs']);
    $employeesMenu = Menu::query()->firstOrCreate(['slug' => 'employees'], ['name' => 'Employees']);
    $role->menus()->sync([
        $menu->id => [
            'can_view' => true,
            'can_create' => true,
            'can_update' => true,
            'can_delete' => true,
        ],
        $employeesMenu->id => [
            'can_view' => true,
            'can_create' => false,
            'can_update' => false,
            'can_delete' => false,
        ],
    ]);
    app(PermissionService::class)->flush();

    return User::factory()->create(['role_id' => $role->id]);
}

/**
 * @return array<string, mixed>
 */
function validProgramPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Leadership Training',
        'type' => 'training',
        'description' => 'Core leadership skills.',
        'organizer' => 'HR Department',
        'location' => 'Jakarta',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-03',
        'capacity' => 20,
        'cost' => 1500000,
        'status' => 'planned',
        'is_active' => '1',
    ], $overrides);
}

beforeEach(function () {
    $this->maker = actingDevelopmentAdmin();
    $this->approverRole = Role::factory()->create();
    $this->actingAs($this->maker);

    app(ApprovalWorkflowService::class)->replaceConfiguration('development-programs', [
        'is_active' => true,
        'maker_roles' => [$this->maker->role_id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$this->approverRole->id]],
        ],
    ], $this->maker);
});

test('development programs index lists programs', function () {
    DevelopmentProgram::factory()->create(['name' => 'Quixotic Leadership']);

    $this->get(route('development-programs.index'))
        ->assertOk()
        ->assertSee('Quixotic Leadership');
});

test('program create route submits a request without creating a program', function () {
    $this->post(route('development-programs.store'), validProgramPayload(['name' => 'New Leadership']))
        ->assertRedirect(route('development-programs.index'));

    $this->assertDatabaseMissing('development_programs', ['name' => 'New Leadership']);
    $this->assertDatabaseHas('approval_requests', [
        'module_key' => 'development-programs',
        'action' => ApprovalRequest::ACTION_CREATE,
        'status' => ApprovalRequest::STATUS_PENDING,
    ]);
});

test('program update route submits a request without changing the program', function () {
    $program = DevelopmentProgram::factory()->create(['name' => 'Original Program']);

    $this->put(route('development-programs.update', $program), validProgramPayload(['name' => 'Renamed Program']))
        ->assertRedirect(route('development-programs.index'));

    expect($program->fresh()->name)->toBe('Original Program');
    $this->assertDatabaseHas('approval_requests', [
        'module_key' => 'development-programs',
        'action' => ApprovalRequest::ACTION_UPDATE,
        'target_id' => $program->id,
        'status' => ApprovalRequest::STATUS_PENDING,
    ]);
});

test('invalid program payload creates no requests', function () {
    $this->post(route('development-programs.store'), validProgramPayload([
        'name' => 'Bad Program',
        'type' => 'invalid-type',
        'end_date' => '2026-09-01',
    ]))->assertSessionHasErrors(['type', 'end_date']);

    $this->assertDatabaseMissing('development_programs', ['name' => 'Bad Program']);
    $this->assertDatabaseCount('approval_requests', 0);
});

test('a program with participants cannot request deletion', function () {
    $program = DevelopmentProgram::factory()->create();
    DevelopmentEnrollment::factory()->create(['development_program_id' => $program->id]);

    $this->delete(route('development-programs.destroy', $program))->assertSessionHasErrors('development_program');

    $this->assertModelExists($program);
    $this->assertDatabaseCount('approval_requests', 0);
});

test('unused program deletion route submits a request without deleting', function () {
    $program = DevelopmentProgram::factory()->create();

    $this->delete(route('development-programs.destroy', $program))
        ->assertRedirect(route('development-programs.index'));

    $this->assertModelExists($program);
    $this->assertDatabaseHas('approval_requests', [
        'module_key' => 'development-programs',
        'action' => ApprovalRequest::ACTION_DELETE,
        'target_id' => $program->id,
        'status' => ApprovalRequest::STATUS_PENDING,
    ]);
});

test('programs apply directly when no approval matrix is active', function () {
    app(ApprovalWorkflowService::class)->replaceConfiguration('development-programs', [
        'is_active' => false,
        'maker_roles' => [$this->maker->role_id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$this->approverRole->id]],
        ],
    ], $this->maker);

    $this->post(route('development-programs.store'), validProgramPayload(['name' => 'Direct Program', 'code' => 'DEV-90001']))
        ->assertRedirect(route('development-programs.index'));
    $this->assertDatabaseHas('development_programs', ['name' => 'Direct Program', 'code' => 'DEV-90001']);

    $program = DevelopmentProgram::query()->where('name', 'Direct Program')->sole();

    $this->put(route('development-programs.update', $program), validProgramPayload(['name' => 'Renamed Direct', 'code' => 'DEV-90001']))
        ->assertRedirect(route('development-programs.index'));
    expect($program->fresh()->name)->toBe('Renamed Direct');

    $this->delete(route('development-programs.destroy', $program))->assertRedirect(route('development-programs.index'));
    $this->assertSoftDeleted('development_programs', ['id' => $program->id]);

    $this->assertDatabaseCount('approval_requests', 0);
});

test('direct create generates a code when blank', function () {
    app(ApprovalWorkflowService::class)->replaceConfiguration('development-programs', [
        'is_active' => false,
        'maker_roles' => [$this->maker->role_id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$this->approverRole->id]],
        ],
    ], $this->maker);

    $this->post(route('development-programs.store'), validProgramPayload(['name' => 'Coded Program', 'code' => '']))
        ->assertRedirect(route('development-programs.index'));

    $program = DevelopmentProgram::query()->where('name', 'Coded Program')->sole();
    expect($program->code)->not->toBe('');
});

test('employee profile shows development history', function () {
    $program = DevelopmentProgram::factory()->create(['name' => 'History Program']);
    $employee = Employee::factory()->create();
    DevelopmentEnrollment::factory()->create([
        'development_program_id' => $program->id,
        'employee_id' => $employee->id,
    ]);

    $this->get(route('employees.show', $employee))
        ->assertOk()
        ->assertSee('Development history')
        ->assertSee('History Program');
});
