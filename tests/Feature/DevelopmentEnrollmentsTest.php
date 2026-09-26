<?php

use App\Models\ApprovalRequest;
use App\Models\DevelopmentEnrollment;
use App\Models\DevelopmentProgram;
use App\Models\Employee;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

beforeEach(function () {
    $role = Role::factory()->create();
    // The data migration seeds this slug under RefreshDatabase, so reuse it when present.
    $menu = Menu::query()->firstOrCreate(['slug' => 'development-programs'], ['name' => 'Development Programs']);
    $role->menus()->sync([$menu->id => [
        'can_view' => true,
        'can_create' => true,
        'can_update' => true,
        'can_delete' => true,
    ]]);
    app(PermissionService::class)->flush();

    $this->user = User::factory()->create(['role_id' => $role->id]);
    $this->actingAs($this->user);
});

test('enrolling an active employee writes directly without an approval request', function () {
    $program = DevelopmentProgram::factory()->create();
    $employee = Employee::factory()->create();

    $this->post(route('development-programs.enrollments.store', $program), [
        'employee_id' => $employee->id,
        'status' => 'registered',
    ])->assertRedirect(route('development-programs.show', $program));

    $this->assertDatabaseHas('development_enrollments', [
        'development_program_id' => $program->id,
        'employee_id' => $employee->id,
        'status' => 'registered',
    ]);
    $this->assertDatabaseCount('approval_requests', 0);
});

test('duplicate enrollment for the same employee and program is rejected', function () {
    $program = DevelopmentProgram::factory()->create();
    $employee = Employee::factory()->create();
    DevelopmentEnrollment::factory()->create([
        'development_program_id' => $program->id,
        'employee_id' => $employee->id,
    ]);

    $this->post(route('development-programs.enrollments.store', $program), [
        'employee_id' => $employee->id,
        'status' => 'registered',
    ])->assertSessionHasErrors('employee_id');

    expect(DevelopmentEnrollment::query()->where('development_program_id', $program->id)->count())->toBe(1);
});

test('enrollment beyond capacity is rejected', function () {
    $program = DevelopmentProgram::factory()->create(['capacity' => 1]);
    $first = Employee::factory()->create();
    $second = Employee::factory()->create();
    DevelopmentEnrollment::factory()->create([
        'development_program_id' => $program->id,
        'employee_id' => $first->id,
        'status' => 'registered',
    ]);

    $this->post(route('development-programs.enrollments.store', $program), [
        'employee_id' => $second->id,
        'status' => 'registered',
    ])->assertSessionHasErrors('employee_id');

    $this->assertDatabaseMissing('development_enrollments', [
        'development_program_id' => $program->id,
        'employee_id' => $second->id,
    ]);
});

test('cancelled seats free capacity for new enrollments', function () {
    $program = DevelopmentProgram::factory()->create(['capacity' => 1]);
    $first = Employee::factory()->create();
    $second = Employee::factory()->create();
    DevelopmentEnrollment::factory()->create([
        'development_program_id' => $program->id,
        'employee_id' => $first->id,
        'status' => 'cancelled',
    ]);

    $this->post(route('development-programs.enrollments.store', $program), [
        'employee_id' => $second->id,
        'status' => 'registered',
    ])->assertRedirect(route('development-programs.show', $program));

    $this->assertDatabaseHas('development_enrollments', [
        'development_program_id' => $program->id,
        'employee_id' => $second->id,
    ]);
});

test('result update persists outcome fields', function () {
    $program = DevelopmentProgram::factory()->create();
    $enrollment = DevelopmentEnrollment::factory()->create([
        'development_program_id' => $program->id,
        'status' => 'registered',
    ]);

    $this->put(route('development-programs.enrollments.update', [$program, $enrollment]), [
        'status' => 'completed',
        'score' => 87.5,
        'completed_at' => '2026-10-05',
        'certificate_no' => 'CERT-001',
        'notes' => 'Excellent participation.',
    ])->assertRedirect(route('development-programs.show', $program));

    $enrollment->refresh();

    expect($enrollment->status)->toBe('completed')
        ->and((float) $enrollment->score)->toBe(87.5)
        ->and($enrollment->completed_at->format('Y-m-d'))->toBe('2026-10-05')
        ->and($enrollment->certificate_no)->toBe('CERT-001')
        ->and($enrollment->notes)->toBe('Excellent participation.');
});

test('completed status requires a completion date', function () {
    $program = DevelopmentProgram::factory()->create();
    $enrollment = DevelopmentEnrollment::factory()->create([
        'development_program_id' => $program->id,
        'status' => 'registered',
    ]);

    $this->put(route('development-programs.enrollments.update', [$program, $enrollment]), [
        'status' => 'completed',
    ])->assertSessionHasErrors('completed_at');

    expect($enrollment->fresh()->status)->toBe('registered');
});

test('unenrolling deletes the row', function () {
    $program = DevelopmentProgram::factory()->create();
    $enrollment = DevelopmentEnrollment::factory()->create(['development_program_id' => $program->id]);

    $this->delete(route('development-programs.enrollments.destroy', [$program, $enrollment]))
        ->assertRedirect(route('development-programs.show', $program));

    $this->assertModelMissing($enrollment);
});

test('enrollment scoped to another program cannot be touched', function () {
    $program = DevelopmentProgram::factory()->create();
    $other = DevelopmentProgram::factory()->create();
    $enrollment = DevelopmentEnrollment::factory()->create(['development_program_id' => $other->id]);

    $this->put(route('development-programs.enrollments.update', [$program, $enrollment]), [
        'status' => 'completed',
        'completed_at' => '2026-10-05',
    ])->assertNotFound();

    $this->delete(route('development-programs.enrollments.destroy', [$program, $enrollment]))->assertNotFound();

    $this->assertModelExists($enrollment);
});

test('enrollment survives a program update', function () {
    $program = DevelopmentProgram::factory()->create();
    $enrollment = DevelopmentEnrollment::factory()->create(['development_program_id' => $program->id]);

    $program->update(['name' => 'Renamed Program']);

    $this->assertDatabaseHas('development_enrollments', [
        'id' => $enrollment->id,
        'development_program_id' => $program->id,
    ]);
    expect(ApprovalRequest::query()->count())->toBe(0);
});
