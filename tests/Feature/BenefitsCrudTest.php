<?php

use App\Models\ApprovalRequest;
use App\Models\Benefit;
use App\Models\BenefitEnrollment;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Grade;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;

function actingBenefitsAdmin(): User
{
    $role = Role::factory()->create();

    foreach (['benefits', 'benefit-enrollments', 'benefit-claims', 'approvals'] as $slug) {
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

function benefitsApprovalSetup(User $maker): Role
{
    $approverRole = Role::factory()->create();

    app(ApprovalWorkflowService::class)->replaceConfiguration('benefits', [
        'is_active' => true,
        'maker_roles' => [$maker->role_id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$approverRole->id]],
        ],
    ], $maker);

    return $approverRole;
}

function benefitPayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'BEN-'.fake()->unique()->numerify('#####'),
        'name' => fake()->unique()->words(3, true),
        'type' => 'medical',
        'description' => 'Test benefit',
        'limit_amount' => '1000000.00',
        'period' => 'yearly',
        'min_tenure_months' => 0,
        'requires_receipt' => '1',
        'is_active' => '1',
    ], $overrides);
}

beforeEach(function () {
    $this->maker = actingBenefitsAdmin();
    $this->approverRole = benefitsApprovalSetup($this->maker);
    $this->actingAs($this->maker);
});

test('benefits index lists benefits', function () {
    Benefit::factory()->create(['name' => 'Quixotic Benefit']);

    $this->get(route('benefits.index'))
        ->assertOk()
        ->assertSee('Quixotic Benefit');
});

test('benefits index filters by status', function () {
    $active = Benefit::factory()->create(['name' => 'Active Benefit']);
    $inactive = Benefit::factory()->inactive()->create(['name' => 'Inactive Benefit']);

    $this->get(route('benefits.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee($inactive->name)
        ->assertDontSee($active->name);
});

test('benefit create route submits a request without creating a benefit', function () {
    $this->post(route('benefits.store'), benefitPayload(['code' => 'BEN-NEW-1', 'name' => 'New Benefit']))
        ->assertRedirect(route('benefits.index', ['tab' => 'requests']));

    $this->assertDatabaseMissing('benefits', ['name' => 'New Benefit']);
    $this->assertDatabaseHas('approval_requests', [
        'module_key' => 'benefits',
        'action' => ApprovalRequest::ACTION_CREATE,
        'status' => ApprovalRequest::STATUS_PENDING,
    ]);
});

test('benefit code and name must be unique when submitting', function () {
    $existing = Benefit::factory()->create(['code' => 'BEN-TAKEN', 'name' => 'Taken Benefit']);

    $this->post(route('benefits.store'), benefitPayload(['code' => $existing->code, 'name' => 'Other Name']))
        ->assertSessionHasErrors('code');

    $this->post(route('benefits.store'), benefitPayload(['code' => 'BEN-OTHER', 'name' => $existing->name]))
        ->assertSessionHasErrors('name');

    $this->assertDatabaseCount('approval_requests', 0);
});

test('a benefit with enrollments cannot request deletion', function () {
    $benefit = Benefit::factory()->create();
    $employee = Employee::factory()->create(['join_date' => now()->subYears(2)->toDateString()]);
    BenefitEnrollment::factory()->create(['benefit_id' => $benefit->id, 'employee_id' => $employee->id]);

    $this->delete(route('benefits.destroy', $benefit))->assertSessionHasErrors('benefit');

    $this->assertModelExists($benefit);
    $this->assertDatabaseCount('approval_requests', 0);
});

test('benefits apply directly when no approval matrix is active', function () {
    app(ApprovalWorkflowService::class)->replaceConfiguration('benefits', [
        'is_active' => false,
        'maker_roles' => [$this->maker->role_id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$this->approverRole->id]],
        ],
    ], $this->maker);

    $this->post(route('benefits.store'), benefitPayload(['code' => 'BEN-DIRECT', 'name' => 'Direct Benefit']))
        ->assertRedirect(route('benefits.index'));
    $this->assertDatabaseHas('benefits', ['name' => 'Direct Benefit']);

    $benefit = Benefit::query()->where('name', 'Direct Benefit')->sole();

    $this->put(route('benefits.update', $benefit), benefitPayload(['code' => 'BEN-DIRECT', 'name' => 'Renamed Direct']))
        ->assertRedirect(route('benefits.index'));
    expect($benefit->fresh()->name)->toBe('Renamed Direct');

    $this->delete(route('benefits.destroy', $benefit))->assertRedirect(route('benefits.index'));
    $this->assertModelMissing($benefit);

    $this->assertDatabaseCount('approval_requests', 0);
});

test('benefit edit form renders eligibility multi-selects seeded with saved grades and statuses', function () {
    $benefit = Benefit::factory()->create();
    $grades = Grade::factory()->count(2)->create();
    $statuses = EmploymentStatus::factory()->count(2)->create();

    $benefit->eligibleGrades()->sync($grades->pluck('id'));
    $benefit->eligibleEmploymentStatuses()->sync($statuses->pluck('id'));

    $response = $this->get(route('benefits.edit', $benefit))->assertOk();

    $response->assertSee('name="eligible_grade_ids[]"', false);
    $response->assertSee('name="eligible_employment_status_ids[]"', false);

    foreach ($grades as $grade) {
        $response->assertSee($grade->name);
    }

    foreach ($statuses as $status) {
        $response->assertSee($status->name);
    }
});

test('updating a benefit syncs multiple eligible grades and statuses', function () {
    app(ApprovalWorkflowService::class)->replaceConfiguration('benefits', [
        'is_active' => false,
        'maker_roles' => [$this->maker->role_id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$this->approverRole->id]],
        ],
    ], $this->maker);

    $benefit = Benefit::factory()->create();
    $grades = Grade::factory()->count(2)->create();
    $statuses = EmploymentStatus::factory()->count(2)->create();

    $this->put(route('benefits.update', $benefit), benefitPayload([
        'code' => $benefit->code,
        'name' => $benefit->name,
        'eligible_grade_ids' => $grades->pluck('id')->all(),
        'eligible_employment_status_ids' => $statuses->pluck('id')->all(),
    ]))->assertRedirect(route('benefits.index'));

    expect($benefit->fresh()->eligibleGrades->pluck('id')->sort()->values()->all())
        ->toBe($grades->pluck('id')->sort()->values()->all());
    expect($benefit->fresh()->eligibleEmploymentStatuses->pluck('id')->sort()->values()->all())
        ->toBe($statuses->pluck('id')->sort()->values()->all());
});

test('clearing the eligibility multi-selects removes all pivots', function () {
    app(ApprovalWorkflowService::class)->replaceConfiguration('benefits', [
        'is_active' => false,
        'maker_roles' => [$this->maker->role_id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$this->approverRole->id]],
        ],
    ], $this->maker);

    $benefit = Benefit::factory()->create();
    $benefit->eligibleGrades()->sync(Grade::factory()->create()->id);
    $benefit->eligibleEmploymentStatuses()->sync(EmploymentStatus::factory()->create()->id);

    $this->put(route('benefits.update', $benefit), benefitPayload([
        'code' => $benefit->code,
        'name' => $benefit->name,
    ]))->assertRedirect(route('benefits.index'));

    expect($benefit->fresh()->eligibleGrades)->toHaveCount(0);
    expect($benefit->fresh()->eligibleEmploymentStatuses)->toHaveCount(0);
});
