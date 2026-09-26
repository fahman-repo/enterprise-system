<?php

use App\Models\ApprovalRequest;
use App\Models\Benefit;
use App\Models\BenefitClaim;
use App\Models\BenefitEnrollment;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Grade;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;

function actingEnrollmentAdmin(): User
{
    $role = Role::factory()->create();

    foreach (['benefit-enrollments', 'approvals'] as $slug) {
        $menu = Menu::factory()->create(['slug' => $slug]);
        $role->menus()->syncWithoutDetaching([$menu->id => [
            'can_view' => true,
            'can_create' => true,
            'can_update' => true,
            'can_delete' => true,
        ]]);
    }

    app(PermissionService::class)->flush();

    return User::factory()->create(['role_id' => $role->id]);
}

function enrollmentApprovalSetup(User $maker): Role
{
    $approverRole = Role::factory()->create();

    app(ApprovalWorkflowService::class)->replaceConfiguration('benefit-enrollments', [
        'is_active' => true,
        'maker_roles' => [$maker->role_id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$approverRole->id]],
        ],
    ], $maker);

    return $approverRole;
}

function enrollableEmployee(array $overrides = []): Employee
{
    return Employee::factory()->create(array_merge([
        'join_date' => now()->subYears(2)->toDateString(),
        'is_active' => true,
    ], $overrides));
}

beforeEach(function () {
    $this->maker = actingEnrollmentAdmin();
    $this->approverRole = enrollmentApprovalSetup($this->maker);
    $this->actingAs($this->maker);
});

test('enrollments index lists enrollments', function () {
    $benefit = Benefit::factory()->create(['name' => 'Quixotic Cover']);
    $employee = enrollableEmployee(['name' => 'Quixotic Person']);
    BenefitEnrollment::factory()->create(['benefit_id' => $benefit->id, 'employee_id' => $employee->id]);

    $this->get(route('benefit-enrollments.index'))
        ->assertOk()
        ->assertSee('Quixotic Cover')
        ->assertSee('Quixotic Person');
});

test('ineligible grade is rejected at submit', function () {
    $allowed = Grade::factory()->create();
    $blocked = Grade::factory()->create();
    $benefit = Benefit::factory()->create();
    $benefit->eligibleGrades()->sync([$allowed->id]);
    $employee = enrollableEmployee(['grade_id' => $blocked->id]);

    $this->post(route('benefit-enrollments.store'), [
        'benefit_id' => $benefit->id,
        'employee_id' => $employee->id,
        'status' => 'active',
        'effective_from' => now()->toDateString(),
    ])->assertSessionHasErrors('employee');

    $this->assertDatabaseCount('approval_requests', 0);
});

test('ineligible employment status is rejected at submit', function () {
    $allowed = EmploymentStatus::factory()->create();
    $blocked = EmploymentStatus::factory()->create();
    $benefit = Benefit::factory()->create();
    $benefit->eligibleEmploymentStatuses()->sync([$allowed->id]);
    $employee = enrollableEmployee(['employment_status_id' => $blocked->id]);

    $this->post(route('benefit-enrollments.store'), [
        'benefit_id' => $benefit->id,
        'employee_id' => $employee->id,
        'status' => 'active',
        'effective_from' => now()->toDateString(),
    ])->assertSessionHasErrors('employee');

    $this->assertDatabaseCount('approval_requests', 0);
});

test('insufficient tenure is rejected at submit', function () {
    $benefit = Benefit::factory()->create(['min_tenure_months' => 12]);
    $employee = enrollableEmployee(['join_date' => now()->subMonths(2)->toDateString()]);

    $this->post(route('benefit-enrollments.store'), [
        'benefit_id' => $benefit->id,
        'employee_id' => $employee->id,
        'status' => 'active',
        'effective_from' => now()->toDateString(),
    ])->assertSessionHasErrors('employee');

    $this->assertDatabaseCount('approval_requests', 0);
});

test('duplicate active enrollment is rejected', function () {
    $benefit = Benefit::factory()->create();
    $employee = enrollableEmployee();
    BenefitEnrollment::factory()->create([
        'benefit_id' => $benefit->id,
        'employee_id' => $employee->id,
        'status' => 'active',
    ]);

    $this->post(route('benefit-enrollments.store'), [
        'benefit_id' => $benefit->id,
        'employee_id' => $employee->id,
        'status' => 'active',
        'effective_from' => now()->toDateString(),
    ])->assertSessionHasErrors('enrollment');

    $this->assertDatabaseCount('approval_requests', 0);
});

test('expired enrollment is not usable for claims', function () {
    $benefit = Benefit::factory()->create(['is_active' => true]);
    $employee = enrollableEmployee();
    $enrollment = BenefitEnrollment::factory()->create([
        'benefit_id' => $benefit->id,
        'employee_id' => $employee->id,
        'status' => 'active',
        'effective_from' => now()->subYear()->toDateString(),
        'effective_to' => now()->subMonth()->toDateString(),
    ]);

    expect($enrollment->fresh(['benefit', 'employee'])->isUsable())->toBeFalse();
});

test('enrollment with claims cannot be deleted', function () {
    $benefit = Benefit::factory()->create(['requires_receipt' => false, 'limit_amount' => '1000000.00']);
    $employee = enrollableEmployee();
    $enrollment = BenefitEnrollment::factory()->create(['benefit_id' => $benefit->id, 'employee_id' => $employee->id]);
    BenefitClaim::factory()->create([
        'benefit_id' => $benefit->id,
        'benefit_enrollment_id' => $enrollment->id,
        'employee_id' => $employee->id,
    ]);

    $this->delete(route('benefit-enrollments.destroy', $enrollment))->assertSessionHasErrors('enrollment');

    $this->assertModelExists($enrollment);
    $this->assertDatabaseCount('approval_requests', 0);
});

test('enrollment create submits a request without creating a row', function () {
    $benefit = Benefit::factory()->create();
    $employee = enrollableEmployee();

    $this->post(route('benefit-enrollments.store'), [
        'benefit_id' => $benefit->id,
        'employee_id' => $employee->id,
        'status' => 'active',
        'effective_from' => now()->toDateString(),
    ])->assertRedirect(route('benefit-enrollments.index', ['tab' => 'requests']));

    $this->assertDatabaseCount('benefit_enrollments', 0);
    $this->assertDatabaseHas('approval_requests', [
        'module_key' => 'benefit-enrollments',
        'action' => ApprovalRequest::ACTION_CREATE,
        'status' => ApprovalRequest::STATUS_PENDING,
    ]);
});
