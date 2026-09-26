<?php

use App\Models\ApprovalRequest;
use App\Models\Benefit;
use App\Models\BenefitClaim;
use App\Models\BenefitEnrollment;
use App\Models\Employee;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\BenefitEligibilityService;
use App\Services\PermissionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function actingClaimsAdmin(): User
{
    $role = Role::factory()->create();

    foreach (['benefit-claims', 'approvals'] as $slug) {
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

function claimsApprovalSetup(User $maker): array
{
    $approverRole = Role::factory()->create();
    $approvalsMenu = Menu::query()->where('slug', 'approvals')->first();
    $approver = User::factory()->create(['role_id' => $approverRole->id]);
    $approverRole->menus()->sync($approvalsMenu ? [$approvalsMenu->id => [
        'can_view' => true, 'can_create' => false, 'can_update' => true, 'can_delete' => false,
    ]] : []);

    app(PermissionService::class)->flush();

    app(ApprovalWorkflowService::class)->replaceConfiguration('benefit-claims', [
        'is_active' => true,
        'maker_roles' => [$maker->role_id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$approverRole->id]],
        ],
    ], $maker);

    return [$approverRole, $approver];
}

function claimEnrollment(float $limit = 1000000): BenefitEnrollment
{
    $benefit = Benefit::factory()->create([
        'limit_amount' => number_format($limit, 2, '.', ''),
        'period' => 'yearly',
        'requires_receipt' => false,
        'is_active' => true,
    ]);
    $employee = Employee::factory()->create([
        'join_date' => now()->subYears(2)->toDateString(),
        'is_active' => true,
    ]);

    return BenefitEnrollment::factory()->create([
        'benefit_id' => $benefit->id,
        'employee_id' => $employee->id,
        'status' => 'active',
        'effective_from' => now()->subMonth()->toDateString(),
        'effective_to' => null,
    ]);
}

beforeEach(function () {
    $this->maker = actingClaimsAdmin();
    [$this->approverRole, $this->approver] = claimsApprovalSetup($this->maker);
    $this->actingAs($this->maker);
});

test('over-limit claim is rejected at submit', function () {
    $enrollment = claimEnrollment(1000000);

    $this->post(route('benefit-claims.store'), [
        'benefit_id' => $enrollment->benefit_id,
        'benefit_enrollment_id' => $enrollment->id,
        'employee_id' => $enrollment->employee_id,
        'claim_date' => now()->toDateString(),
        'amount' => '2000000.00',
    ])->assertSessionHasErrors('amount');

    $this->assertDatabaseCount('benefit_claims', 0);
    $this->assertDatabaseCount('approval_requests', 0);
});

test('receipt is required when the benefit requires it', function () {
    $enrollment = claimEnrollment();
    $enrollment->benefit->update(['requires_receipt' => true]);

    $this->post(route('benefit-claims.store'), [
        'benefit_id' => $enrollment->benefit_id,
        'benefit_enrollment_id' => $enrollment->id,
        'employee_id' => $enrollment->employee_id,
        'claim_date' => now()->toDateString(),
        'amount' => '10000.00',
    ])->assertSessionHasErrors('receipt_path');

    $this->assertDatabaseCount('benefit_claims', 0);
});

test('claim create leaves the row pending until the approval path completes', function () {
    Storage::fake('public');
    $enrollment = claimEnrollment();

    $this->post(route('benefit-claims.store'), [
        'benefit_id' => $enrollment->benefit_id,
        'benefit_enrollment_id' => $enrollment->id,
        'employee_id' => $enrollment->employee_id,
        'claim_date' => now()->toDateString(),
        'amount' => '250000.00',
        'receipt' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
    ])->assertRedirect(route('benefit-claims.index', ['tab' => 'requests']));

    $this->assertDatabaseCount('benefit_claims', 0);

    $request = ApprovalRequest::query()->where('module_key', 'benefit-claims')->sole();

    $this->actingAs($this->approver)->post(route('approvals.approve', $request))->assertRedirect();

    $claim = BenefitClaim::query()->sole();
    expect($claim->status)->toBe(BenefitClaim::STATUS_PENDING);

    // Final approval only materializes the pending row; the claim-level
    // approve transition stays direct from the show page. Deactivate the
    // matrix so the direct transition is available.
    app(ApprovalWorkflowService::class)->replaceConfiguration('benefit-claims', [
        'is_active' => false,
        'maker_roles' => [$this->maker->role_id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$this->approverRole->id]],
        ],
    ], $this->maker);

    $this->actingAs($this->maker)->post(route('benefit-claims.approve', $claim))->assertRedirect();

    expect($claim->fresh()->status)->toBe(BenefitClaim::STATUS_APPROVED);

    $remaining = app(BenefitEligibilityService::class)->remainingAmount($claim->fresh()->enrollment()->with(['benefit', 'employee'])->firstOrFail());
    expect($remaining)->toBe('750000.00');
});

test('second concurrent claim fails balance at apply time', function () {
    $enrollment = claimEnrollment(500000);

    $payload = fn (string $amount): array => [
        'benefit_id' => $enrollment->benefit_id,
        'benefit_enrollment_id' => $enrollment->id,
        'employee_id' => $enrollment->employee_id,
        'claim_date' => now()->toDateString(),
        'amount' => $amount,
        'status' => BenefitClaim::STATUS_PENDING,
    ];

    $workflow = app(ApprovalWorkflowService::class);
    $first = $workflow->submit($this->maker, 'benefit-claims', ApprovalRequest::ACTION_CREATE, null, $payload('400000.00'));
    $second = $workflow->submit($this->maker, 'benefit-claims', ApprovalRequest::ACTION_CREATE, null, $payload('400000.00'));

    $this->actingAs($this->approver)->post(route('approvals.approve', $first))->assertRedirect();
    $firstClaim = BenefitClaim::query()->findOrFail(ApprovalRequest::query()->findOrFail($first->id)->target_id);
    $firstClaim->update(['status' => BenefitClaim::STATUS_APPROVED, 'decided_at' => now()]);

    // The second request passed the submit-time balance check but must fail
    // at final apply once the first claim consumes the window.
    $this->actingAs($this->approver)->post(route('approvals.approve', $second))
        ->assertSessionHasErrors('approval');

    expect($second->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING);
    expect(BenefitClaim::query()->count())->toBe(1);
});

test('paid only transitions from approved', function () {
    app(ApprovalWorkflowService::class)->replaceConfiguration('benefit-claims', [
        'is_active' => false,
        'maker_roles' => [$this->maker->role_id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$this->approverRole->id]],
        ],
    ], $this->maker);

    $enrollment = claimEnrollment();
    $claim = BenefitClaim::factory()->create([
        'benefit_id' => $enrollment->benefit_id,
        'benefit_enrollment_id' => $enrollment->id,
        'employee_id' => $enrollment->employee_id,
        'amount' => '10000.00',
        'status' => BenefitClaim::STATUS_PENDING,
    ]);

    $this->post(route('benefit-claims.paid', $claim))->assertSessionHasErrors('claim');
    expect($claim->fresh()->status)->toBe(BenefitClaim::STATUS_PENDING);

    $this->post(route('benefit-claims.approve', $claim))->assertRedirect();
    $this->post(route('benefit-claims.paid', $claim))->assertRedirect();
    expect($claim->fresh()->status)->toBe(BenefitClaim::STATUS_PAID);
});
