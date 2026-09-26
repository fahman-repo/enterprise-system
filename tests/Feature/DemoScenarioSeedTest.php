<?php

use App\Models\Activity;
use App\Models\ApprovalMatrix;
use App\Models\ApprovalRequest;
use App\Models\BenefitClaim;
use App\Models\BenefitEnrollment;
use App\Models\DevelopmentEnrollment;
use App\Models\DevelopmentProgram;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\BenefitEligibilityService;
use App\Services\PermissionService;
use Database\Seeders\ApprovalMatrixSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoScenarioSeeder;
use Database\Seeders\DevelopmentProgramSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Auth;

/**
 * Reference data first, then the demo scenario, exactly as `db:seed`
 * does outside production.
 *
 * @return list<class-string>
 */
function demoSeeders(): array
{
    return [DatabaseSeeder::class, DemoScenarioSeeder::class];
}

function demoUser(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

test('the demo company is complete and every login has its own point of view', function () {
    $this->seed(demoSeeders());

    expect(Role::query()->count())->toBe(count(RoleSeeder::definitions()) + 1)
        ->and(User::query()->count())->toBeGreaterThanOrEqual(count(UserSeeder::definitions()) + 1)
        ->and(Employee::query()->count())->toBeGreaterThan(200)
        ->and(ApprovalMatrix::query()->where('is_active', true)->count())->toBe(count(ApprovalMatrixSeeder::definitions()))
        ->and(DevelopmentProgram::query()->count())->toBeGreaterThanOrEqual(count(DevelopmentProgramSeeder::definitions()))
        ->and(DevelopmentEnrollment::query()->count())->toBeGreaterThan(100)
        ->and(BenefitEnrollment::query()->count())->toBeGreaterThan(140)
        ->and(BenefitClaim::query()->count())->toBeGreaterThan(80)
        ->and(ApprovalRequest::query()->count())->toBeGreaterThan(25);

    $permissions = app(PermissionService::class);

    foreach (UserSeeder::definitions() as $email => $definition) {
        $user = demoUser($email);

        expect($user->role?->slug)->toBe($definition['role'])
            ->and($permissions->sidebarForUser($user)->isNotEmpty())->toBeTrue();

        if ($definition['active'] ?? true) {
            expect(Auth::attempt(['email' => $email, 'password' => 'password']))->toBeTrue();
        }
    }

    $hrManager = demoUser('hr.manager@example.com');
    $employee = demoUser('staff.ops@example.com');
    $itSupport = demoUser('it.support@example.com');
    $commercial = demoUser('sales.jkt@example.com');

    expect(demoUser('ex.staff@example.com')->is_active)->toBeFalse()
        ->and($hrManager->canAccess('employees', 'create'))->toBeTrue()
        ->and($hrManager->canAccess('benefit-claims', 'update'))->toBeTrue()
        ->and($hrManager->canAccess('roles', 'update'))->toBeFalse()
        ->and($employee->canAccess('benefit-claims', 'create'))->toBeTrue()
        ->and($employee->canAccess('benefit-claims', 'update'))->toBeFalse()
        ->and($employee->canAccess('users', 'view'))->toBeFalse()
        ->and($itSupport->canAccess('users', 'create'))->toBeTrue()
        ->and($itSupport->canAccess('employees', 'delete'))->toBeFalse()
        ->and($commercial->canAccess('entities', 'create'))->toBeTrue()
        ->and($commercial->canAccess('sites', 'create'))->toBeFalse();
});

test('approval chains are complete and no pending request dead-ends', function () {
    $this->seed(demoSeeders());

    $workflow = app(ApprovalWorkflowService::class);
    $deciders = User::query()->where('is_active', true)->get();

    ApprovalMatrix::query()->with(['makerRoles', 'stages.roles'])->get()->each(function (ApprovalMatrix $matrix) {
        expect($matrix->is_active)->toBeTrue()
            ->and($matrix->makerRoles)->not->toBeEmpty()
            ->and($matrix->stages)->not->toBeEmpty();

        $matrix->stages->each(fn ($stage) => expect($stage->roles)->not->toBeEmpty());
    });

    ApprovalRequest::query()->with('stages.roles')->get()->each(function (ApprovalRequest $request) use ($workflow, $deciders) {
        expect($request->stages)->not->toBeEmpty();

        if ($request->isPending()) {
            expect($deciders->contains(fn (User $user): bool => $workflow->canDecide($request, $user)))->toBeTrue();

            return;
        }

        expect($request->completed_at)->not->toBeNull();
    });

    expect(ApprovalRequest::query()->where('status', ApprovalRequest::STATUS_PENDING)->count())->toBeGreaterThan(10)
        ->and(ApprovalRequest::query()->where('status', ApprovalRequest::STATUS_APPROVED)->count())->toBeGreaterThan(0)
        ->and(ApprovalRequest::query()->where('status', ApprovalRequest::STATUS_REJECTED)->count())->toBeGreaterThan(0)
        ->and(ApprovalRequest::query()->where('status', ApprovalRequest::STATUS_CANCELLED)->count())->toBeGreaterThan(0);
});

test('benefit claims stay inside the limits and rosters stay inside capacity', function () {
    $this->seed(demoSeeders());

    $service = app(BenefitEligibilityService::class);

    BenefitEnrollment::query()->with(['benefit', 'claims'])->get()->each(function (BenefitEnrollment $enrollment) use ($service) {
        expect($enrollment->benefit)->not->toBeNull();

        expect((float) $service->consumedAmount($enrollment))
            ->toBeLessThanOrEqual((float) $enrollment->benefit->limit_amount);

        $enrollment->claims->each(function (BenefitClaim $claim) use ($enrollment) {
            expect($claim->benefit_id)->toBe($enrollment->benefit_id)
                ->and($claim->employee_id)->toBe($enrollment->employee_id)
                ->and((float) $claim->amount)->toBeGreaterThan(0.0);
        });
    });

    expect(BenefitClaim::query()->whereIn('status', BenefitClaim::CONSUMING)->count())->toBeGreaterThan(0)
        ->and(BenefitClaim::query()->where('status', BenefitClaim::STATUS_PENDING)->count())->toBeGreaterThan(0)
        ->and(BenefitClaim::query()->where('status', BenefitClaim::STATUS_REJECTED)->count())->toBeGreaterThan(0);

    DevelopmentProgram::query()->with('enrollments')->get()->each(function (DevelopmentProgram $program) {
        if (! $program->is_active) {
            expect($program->enrollments)->toBeEmpty();

            return;
        }

        expect($program->enrollments)->not->toBeEmpty()
            ->and($program->enrollments->count())->toBeLessThanOrEqual((int) $program->capacity);
    });

    $pairs = DevelopmentEnrollment::query()
        ->get(['development_program_id', 'employee_id'])
        ->map(fn (DevelopmentEnrollment $enrollment): string => $enrollment->development_program_id.'-'.$enrollment->employee_id);

    expect(DevelopmentEnrollment::query()->where('status', 'completed')->whereNull('certificate_no')->count())->toBe(0)
        ->and(DevelopmentEnrollment::query()->where('status', 'completed')->whereNull('score')->count())->toBe(0)
        ->and(DevelopmentEnrollment::query()->where('status', 'failed')->whereNotNull('certificate_no')->count())->toBe(0)
        ->and($pairs->unique())->toHaveCount($pairs->count());
});

test('the audit trail covers the workflow and is spread over weeks', function () {
    $this->seed(demoSeeders());

    $events = Activity::query()->pluck('event')->unique()->all();
    $causers = Activity::query()->whereNotNull('causer_id')->distinct()->count('causer_id');

    expect(Activity::query()->count())->toBeGreaterThan(200)
        ->and($events)->toContain('created')
        ->and($events)->toContain('login')
        ->and($events)->toContain('failed_login')
        ->and($events)->toContain('submitted')
        ->and($events)->toContain('stage_approved')
        ->and($events)->toContain('approved')
        ->and($events)->toContain('rejected')
        ->and($events)->toContain('cancelled')
        ->and($events)->toContain('configuration_replaced')
        ->and($causers)->toBeGreaterThan(5)
        ->and(Activity::query()->where('created_at', '<', now()->subDays(7))->count())->toBeGreaterThan(0)
        ->and(Activity::query()->where('created_at', '>', now()->subDays(2))->count())->toBeGreaterThan(0);
});

test('re-seeding the demo scenario keeps the company stable', function () {
    $this->seed(demoSeeders());

    $snapshot = fn (): array => [
        'roles' => Role::query()->count(),
        'users' => User::query()->count(),
        'employees' => Employee::query()->count(),
        'benefit_enrollments' => BenefitEnrollment::query()->count(),
        'approval_requests' => ApprovalRequest::query()->count(),
        'approval_matrices' => ApprovalMatrix::query()->count(),
        'programs' => DevelopmentProgram::query()->count(),
        'enrollments' => DevelopmentEnrollment::query()->count(),
    ];

    $first = $snapshot();
    $this->seed(demoSeeders());

    expect($snapshot())->toBe($first);
});
