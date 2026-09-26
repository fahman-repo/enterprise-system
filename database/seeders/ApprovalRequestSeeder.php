<?php

namespace Database\Seeders;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStage;
use App\Models\Benefit;
use App\Models\BenefitClaim;
use App\Models\BenefitEnrollment;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Department;
use App\Models\DevelopmentProgram;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Entity;
use App\Models\Grade;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\Product;
use App\Models\Role;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\BenefitEligibilityService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApprovalRequestSeeder extends Seeder
{
    protected ApprovalWorkflowService $workflow;

    /** @var array<string, User> */
    protected array $users = [];

    /**
     * Replay a realistic approval history: requests waiting on each
     * approver, requests already advanced to a later stage, and the
     * approved, rejected and cancelled outcomes of the last months.
     *
     * Every row is produced through ApprovalWorkflowService, so stage
     * roles, payload snapshots, decided-by snapshots and audit entries
     * are exactly what the UI writes. The seeder only runs on a database
     * without approval requests, which keeps re-runs idempotent and
     * leaves live requests untouched.
     */
    public function run(): void
    {
        if (ApprovalRequest::query()->exists()) {
            return;
        }

        $this->workflow = app(ApprovalWorkflowService::class);
        $this->users = User::query()
            ->whereIn('email', self::makerEmails())
            ->get()
            ->keyBy('email')
            ->all();

        if ($this->users === []) {
            return;
        }

        $this->seedDevelopmentPrograms();
        $this->seedEmployees();
        $this->seedBenefitEnrollments();
        $this->seedBenefitClaims();
        $this->seedEntities();
        $this->seedProducts();
        $this->seedUsers();
        $this->seedMasterData();
        $this->seedSites();
    }

    /**
     * Emails of the demo personas that act as makers.
     *
     * @return list<string>
     */
    public static function makerEmails(): array
    {
        return [
            'hr.staff@example.com',
            'it.support@example.com',
            'sales.jkt@example.com',
            'sales.sby@example.com',
            'staff.sales@example.com',
            'staff.ops@example.com',
            'ops.manager@example.com',
        ];
    }

    protected function submit(string $makerEmail, string $module, string $action, ?int $targetId = null, array $payload = []): ApprovalRequest
    {
        return $this->workflow->submit($this->users[$makerEmail], $module, $action, $targetId, $payload);
    }

    /**
     * Approve stage after stage until the request is completed.
     *
     * @param  list<string>  $comments
     */
    protected function approveAll(ApprovalRequest $request, array $comments = []): void
    {
        $this->advance($request, 10, $comments);
    }

    /**
     * Approve the given number of stages, leaving the remainder pending.
     *
     * @param  list<string>  $comments
     */
    protected function advance(ApprovalRequest $request, int $stages, array $comments = []): void
    {
        for ($index = 0; $index < $stages; $index++) {
            $request->refresh();

            if (! $request->isPending()) {
                return;
            }

            $actor = $this->approverFor($request);

            if ($actor === null) {
                return;
            }

            $this->workflow->approve($request, $actor, $comments[$index] ?? 'Reviewed and approved.');
        }
    }

    protected function reject(ApprovalRequest $request, string $comment): void
    {
        $actor = $this->approverFor($request);

        if ($actor === null) {
            return;
        }

        $this->workflow->reject($request, $actor, $comment);
    }

    protected function cancel(ApprovalRequest $request): void
    {
        $maker = User::query()->find($request->maker_user_id);

        if ($maker === null) {
            return;
        }

        $this->workflow->cancel($request, $maker, 'Withdrawn by the maker.');
    }

    /**
     * First active user holding a role of the stage the request is
     * waiting on, excluding the maker.
     */
    protected function approverFor(ApprovalRequest $request): ?User
    {
        $stage = $request->stages()->where('stage_number', $request->current_stage)->first();

        if (! $stage instanceof ApprovalRequestStage) {
            return null;
        }

        return User::query()
            ->where('is_active', true)
            ->where('id', '!=', $request->maker_user_id)
            ->whereIn('role_id', $stage->roles()->pluck('role_id'))
            ->orderBy('id')
            ->first();
    }

    protected function id(string $modelClass, string $column, string $value): ?int
    {
        return $modelClass::query()->where($column, $value)->value('id');
    }

    /**
     * Training calendar requests: new programmes waiting on HR, one
     * already applied, and the rejected and withdrawn attempts of the
     * current planning cycle.
     */
    protected function seedDevelopmentPrograms(): void
    {
        $base = [
            'type' => 'workshop',
            'description' => 'Requested from the annual development plan.',
            'organizer' => 'Internal HR Academy',
            'location' => 'Jakarta Tower',
            'start_date' => Carbon::today()->addDays(30)->toDateString(),
            'end_date' => Carbon::today()->addDays(31)->toDateString(),
            'capacity' => 24,
            'cost' => '20000000.00',
            'status' => 'planned',
            'is_active' => true,
        ];

        // Waiting on the HR manager.
        $this->submit('hr.staff@example.com', 'development-programs', ApprovalRequest::ACTION_CREATE, null, [
            ...$base,
            'name' => 'Change Management Workshop',
        ]);

        // Fully approved: the programme is now in the calendar.
        $this->approveAll($this->submit('hr.staff@example.com', 'development-programs', ApprovalRequest::ACTION_CREATE, null, [
            ...$base,
            'name' => 'Supply Chain Fundamentals',
            'type' => 'training',
            'cost' => '12000000.00',
        ]));

        // Rejected at the first stage.
        $this->reject($this->submit('hr.staff@example.com', 'development-programs', ApprovalRequest::ACTION_CREATE, null, [
            ...$base,
            'name' => 'Vendor Negotiation Refresher',
            'start_date' => Carbon::today()->addDays(75)->toDateString(),
            'end_date' => Carbon::today()->addDays(76)->toDateString(),
            'cost' => '35000000.00',
        ]), 'External vendor budget is frozen this quarter.');

        $planned = DevelopmentProgram::query()->where('status', 'planned')->orderBy('code')->first();

        if ($planned !== null) {
            // Room and location change withdrawn before review.
            $this->cancel($this->submit(
                'hr.staff@example.com',
                'development-programs',
                ApprovalRequest::ACTION_UPDATE,
                $planned->getKey(),
                $this->programPayload($planned, ['capacity' => 18, 'location' => 'Surabaya Plant']),
            ));
        }

        $ongoing = DevelopmentProgram::query()->where('status', 'ongoing')->orderBy('code')->first();

        if ($ongoing !== null) {
            // Closing a running programme, waiting on HR.
            $this->submit(
                'hr.staff@example.com',
                'development-programs',
                ApprovalRequest::ACTION_UPDATE,
                $ongoing->getKey(),
                $this->programPayload($ongoing, ['status' => 'completed', 'is_active' => false]),
            );
        }
    }

    /**
     * Employee requests: two new hires mid-flight, one fully approved
     * hire, a data correction, and a termination the HR manager refused.
     */
    protected function seedEmployees(): void
    {
        // Waiting on the HR manager.
        $this->submit('hr.staff@example.com', 'employees', ApprovalRequest::ACTION_CREATE, null, $this->newHirePayload(
            'Rizky Pratama', 'male', 'ORG-LINE', 'POS-PRD', 'FAC-SBY', 'PROB', 'rizky.pratama@example.test', '9610000001',
        ));

        // HR approved, waiting on Finance.
        $this->advance($this->submit('hr.staff@example.com', 'employees', ApprovalRequest::ACTION_CREATE, null, $this->newHirePayload(
            'Sari Melati', 'female', 'ORG-INS', 'POS-SLS', 'BR-JKT', 'PROB', 'sari.melati@example.test', '9610000002',
        )), 1);

        // Fully approved: the approval created the employee record.
        $this->approveAll($this->submit('hr.staff@example.com', 'employees', ApprovalRequest::ACTION_CREATE, null, $this->newHirePayload(
            'Bayu Kurniawan', 'male', 'ORG-DEV', 'POS-ENG', 'BLD-BDG', 'CONT', 'bayu.kurniawan@example.test', '9610000003',
        )));

        $officer = Employee::query()
            ->whereHas('position', fn ($query) => $query->where('code', 'POS-HRO'))
            ->orderBy('employee_number')
            ->first();

        if ($officer !== null) {
            // Contact detail correction, approved.
            $this->approveAll($this->submit(
                'hr.staff@example.com',
                'employees',
                ApprovalRequest::ACTION_UPDATE,
                $officer->getKey(),
                $this->employeePayload($officer, ['phone' => '081234567890', 'city' => 'Jakarta Selatan']),
            ));
        }

        $operator = Employee::query()
            ->whereHas('position', fn ($query) => $query->where('code', 'POS-PRD'))
            ->orderBy('employee_number')
            ->first();

        if ($operator !== null) {
            // Termination refused: the handover is not complete.
            $this->reject($this->submit(
                'hr.staff@example.com',
                'employees',
                ApprovalRequest::ACTION_UPDATE,
                $operator->getKey(),
                $this->employeePayload($operator, ['is_active' => false, 'end_date' => Carbon::today()->toDateString()]),
            ), 'Resignation is not effective until the handover checklist is signed off.');
        }
    }

    /**
     * New-hire payload for one of the demo placements, ready to submit as
     * an employees create request.
     *
     * @return array<string, mixed>
     */
    protected function newHirePayload(string $name, string $gender, string $unitCode, string $positionCode, string $siteCode, string $statusCode, string $email, string $identity): array
    {
        $unit = OrgUnit::query()->where('code', $unitCode)->first();
        $department = Department::query()->find($unit?->department_id);
        $site = Site::query()->where('code', $siteCode)->first();

        return [
            'name' => $name,
            'gender' => $gender,
            'birth_place' => $site?->city ?? 'Jakarta',
            'birth_date' => Carbon::today()->subYears(26)->toDateString(),
            'email' => $email,
            'phone' => '08'.$identity,
            'identity_number' => $identity,
            'address' => 'Jl. Melati No. 12',
            'city' => $site?->city ?? 'Jakarta',
            'province' => $site?->province ?? 'DKI Jakarta',
            'postal_code' => $site?->postal_code ?? '12190',
            'emergency_contact_name' => 'Keluarga '.$name,
            'emergency_contact_relationship' => 'Spouse',
            'emergency_contact_phone' => '08'.(string) ((int) $identity + 7),
            'bank_name' => 'BCA',
            'bank_account_number' => (string) (10000000 + (int) $identity),
            'bank_account_name' => $name,
            'division_id' => $department?->division_id,
            'department_id' => $department?->getKey(),
            'org_unit_id' => $unit?->getKey(),
            'position_id' => $this->id(Position::class, 'code', $positionCode),
            'grade_id' => $this->id(Grade::class, 'name', 'Staff'),
            'site_id' => $site?->getKey(),
            'employment_status_id' => $this->id(EmploymentStatus::class, 'code', $statusCode),
            'join_date' => Carbon::today()->toDateString(),
            'is_active' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    protected function employeePayload(Employee $employee, array $changes = []): array
    {
        $payload = $employee->only([
            'user_id', 'manager_id', 'employee_number', 'name', 'gender', 'birth_place', 'birth_date',
            'religion_id', 'marital_status_id', 'education_level_id', 'email', 'phone', 'identity_number',
            'npwp', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'address', 'city', 'province', 'postal_code',
            'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone',
            'bank_name', 'bank_account_number', 'bank_account_name', 'division_id', 'department_id',
            'org_unit_id', 'position_id', 'grade_id', 'site_id', 'employment_status_id', 'join_date',
            'end_date', 'probation_end_date', 'photo_path', 'is_active',
        ]);

        foreach (['birth_date', 'join_date', 'end_date', 'probation_end_date'] as $date) {
            $payload[$date] = $employee->{$date}?->toDateString();
        }

        return [...$payload, ...$changes];
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    protected function programPayload(DevelopmentProgram $program, array $changes = []): array
    {
        return [...[
            'code' => $program->code,
            'name' => $program->name,
            'type' => $program->type,
            'description' => $program->description,
            'organizer' => $program->organizer,
            'location' => $program->location,
            'start_date' => $program->start_date?->toDateString(),
            'end_date' => $program->end_date?->toDateString(),
            'capacity' => $program->capacity,
            'cost' => (string) $program->cost,
            'status' => $program->status,
            'is_active' => $program->is_active,
        ], ...$changes];
    }

    /**
     * Benefit enrollments: one waiting on the line manager, one waiting on
     * Finance, and one already active for the employee.
     */
    protected function seedBenefitEnrollments(): void
    {
        $pending = $this->enrollablePair('BEN-WELLNESS');

        if ($pending !== null) {
            $this->submit('hr.staff@example.com', 'benefit-enrollments', ApprovalRequest::ACTION_CREATE, null, $this->enrollmentPayload($pending['benefit'], $pending['employee']));
        }

        $advanced = $this->enrollablePair('BEN-DENTAL', 2);

        if ($advanced !== null) {
            $this->advance($this->submit(
                'hr.staff@example.com',
                'benefit-enrollments',
                ApprovalRequest::ACTION_CREATE,
                null,
                $this->enrollmentPayload($advanced['benefit'], $advanced['employee']),
            ), 1);
        }

        $approved = $this->enrollablePair('BEN-TRANSPORT', 4);

        if ($approved !== null) {
            $this->approveAll($this->submit(
                'sales.sby@example.com',
                'benefit-enrollments',
                ApprovalRequest::ACTION_CREATE,
                null,
                $this->enrollmentPayload($approved['benefit'], $approved['employee']),
            ));
        }
    }

    /**
     * Benefit claims: submissions from the self-service and commercial
     * personas, one advanced to Finance, one already applied, and one the
     * line manager rejected.
     */
    protected function seedBenefitClaims(): void
    {
        $submissions = [
            ['maker' => 'staff.sales@example.com', 'description' => 'Outpatient treatment at the partner clinic.', 'outcome' => 'pending'],
            ['maker' => 'sales.jkt@example.com', 'description' => 'Annual dental cleaning and filling.', 'outcome' => 'advance'],
            ['maker' => 'sales.sby@example.com', 'description' => 'Monthly commuting reimbursement.', 'outcome' => 'approve'],
            ['maker' => 'staff.ops@example.com', 'description' => 'Glasses for screen work.', 'outcome' => 'reject'],
        ];

        foreach ($submissions as $index => $submission) {
            $enrollment = $this->claimableEnrollment($index);

            if ($enrollment === null) {
                continue;
            }

            $payload = $this->claimPayload($enrollment, 0.12, $submission['description']);

            if ($payload === null) {
                continue;
            }

            $request = $this->submit($submission['maker'], 'benefit-claims', ApprovalRequest::ACTION_CREATE, null, $payload);

            match ($submission['outcome']) {
                'advance' => $this->advance($request, 1),
                'approve' => $this->approveAll($request),
                'reject' => $this->reject($request, 'The receipt does not match the claimed treatment.'),
                default => null,
            };
        }
    }

    /**
     * Vendor and customer onboarding: two requests in flight, one
     * approved counterparty and one rejection for a duplicate.
     */
    protected function seedEntities(): void
    {
        $base = [
            'type' => 'company',
            'role' => 'vendor',
            'address' => 'Kawasan Industri Jababeka Blok F',
            'city' => 'Cikarang',
            'province' => 'Jawa Barat',
            'postal_code' => '17530',
            'bank_name' => 'Mandiri',
            'bank_account_name' => 'PT Sinar Logam Nusantara',
            'notes' => 'Local supplier for steel plate and fasteners.',
            'is_active' => true,
        ];

        // Waiting on Finance.
        $this->submit('sales.sby@example.com', 'entities', ApprovalRequest::ACTION_CREATE, null, [
            ...$base,
            'code' => null,
            'name' => 'PT Sinar Logam Nusantara',
            'npwp' => '01.234.567.8-901.000',
            'email' => 'procurement@sinarlogam.co.id',
            'phone' => '021-5566778',
            'bank_account_number' => '1370009988776',
        ]);

        // Finance approved, waiting on the executive.
        $this->advance($this->submit('sales.sby@example.com', 'entities', ApprovalRequest::ACTION_CREATE, null, [
            ...$base,
            'code' => null,
            'name' => 'CV Mitra Teknik Cikarang',
            'npwp' => '02.345.678.9-012.000',
            'email' => 'sales@mitrateknik.co.id',
            'phone' => '021-8934111',
            'bank_account_number' => '1370009988777',
            'bank_account_name' => 'CV Mitra Teknik Cikarang',
        ]), 1);

        // Fully approved customer account.
        $this->approveAll($this->submit('sales.jkt@example.com', 'entities', ApprovalRequest::ACTION_CREATE, null, [
            ...$base,
            'code' => null,
            'name' => 'PT Prima Kemasan Indonesia',
            'role' => 'customer',
            'email' => 'purchasing@primakemasan.co.id',
            'phone' => '021-5150222',
            'bank_account_number' => '1370009988778',
            'bank_account_name' => 'PT Prima Kemasan Indonesia',
        ]));

        // Rejected: the counterparty already exists.
        $this->reject($this->submit('sales.jkt@example.com', 'entities', ApprovalRequest::ACTION_CREATE, null, [
            ...$base,
            'code' => null,
            'name' => 'PT Sinar Logam (duplicate submission)',
            'npwp' => '01.234.567.8-901.000',
            'email' => 'admin@sinarlogam.co.id',
            'phone' => '021-5566779',
            'bank_account_number' => '1370009988779',
        ]), 'A vendor with the same NPWP is already registered.');

        $existing = Entity::query()->where('is_active', true)->orderBy('id')->first();

        if ($existing !== null) {
            // Contact update, approved.
            $this->approveAll($this->submit('sales.jkt@example.com', 'entities', ApprovalRequest::ACTION_UPDATE, $existing->getKey(), [
                ...$existing->only([
                    'code', 'name', 'type', 'role', 'npwp', 'identity_number', 'email', 'phone',
                    'address', 'city', 'province', 'postal_code', 'bank_name', 'bank_account_number',
                    'bank_account_name', 'notes', 'is_active',
                ]),
                'phone' => '021-5150999',
                'notes' => 'Preferred supplier, payment terms 30 days.',
            ]));
        }
    }

    /**
     * Catalogue requests: a new product waiting on the executive, one
     * already listed, a price revision and a withdrawn one.
     */
    protected function seedProducts(): void
    {
        $base = [
            'barcode' => null,
            'description' => 'Requested for the warehouse modernisation project.',
            'category_id' => Category::query()->orderBy('id')->value('id'),
            'brand_id' => Brand::query()->orderBy('id')->value('id'),
            'unit_id' => Unit::query()->orderBy('id')->value('id'),
            'cost_price' => '1250000.00',
            'selling_price' => '1750000.00',
            'tax_rate' => '11.00',
            'stock_quantity' => 0,
            'reorder_level' => 5,
            'track_stock' => true,
            'is_active' => true,
            'image_path' => null,
        ];

        // Waiting on the executive.
        $this->submit('ops.manager@example.com', 'products', ApprovalRequest::ACTION_CREATE, null, [
            ...$base,
            'sku' => 'PRD-90211',
            'name' => 'Industrial Barcode Scanner',
        ]);

        // Fully approved: now on the shelf.
        $this->approveAll($this->submit('ops.manager@example.com', 'products', ApprovalRequest::ACTION_CREATE, null, [
            ...$base,
            'sku' => 'PRD-90212',
            'name' => 'Thermal Label Printer',
            'cost_price' => '2400000.00',
            'selling_price' => '3150000.00',
        ]));

        $existing = Product::query()->where('is_active', true)->orderBy('id')->first();

        if ($existing !== null) {
            // Price revision approved.
            $this->approveAll($this->submit(
                'ops.manager@example.com',
                'products',
                ApprovalRequest::ACTION_UPDATE,
                $existing->getKey(),
                $this->productPayload($existing, ['selling_price' => '1990000.00']),
            ));
        }

        $second = Product::query()->where('is_active', true)->orderByDesc('id')->first();

        if ($second !== null && $second->getKey() !== $existing?->getKey()) {
            // Reorder level change withdrawn before review.
            $this->cancel($this->submit(
                'ops.manager@example.com',
                'products',
                ApprovalRequest::ACTION_UPDATE,
                $second->getKey(),
                $this->productPayload($second, ['reorder_level' => 40]),
            ));
        }
    }

    /**
     * Account requests from IT: one waiting on HR, one already active, and
     * a deactivation the HR manager refused.
     */
    protected function seedUsers(): void
    {
        $password = Hash::make('password');
        $roleId = $this->id(Role::class, 'slug', 'commercial-staff');

        // Waiting on the HR manager.
        $this->submit('it.support@example.com', 'users', ApprovalRequest::ACTION_CREATE, null, [
            'name' => 'Nabila Puspita',
            'email' => 'nabila.puspita@example.com',
            'role_id' => $roleId,
            'is_active' => true,
            'password' => $password,
        ]);

        // Fully approved: the account can sign in.
        $this->approveAll($this->submit('it.support@example.com', 'users', ApprovalRequest::ACTION_CREATE, null, [
            'name' => 'Yusuf Ramadhan',
            'email' => 'yusuf.ramadhan@example.com',
            'role_id' => $roleId,
            'is_active' => true,
            'password' => $password,
        ]));

        $target = User::query()->where('email', 'sales.sby@example.com')->first();

        if ($target !== null) {
            // Deactivation refused while the handover is open.
            $this->reject($this->submit('it.support@example.com', 'users', ApprovalRequest::ACTION_UPDATE, $target->getKey(), [
                'name' => $target->name,
                'email' => $target->email,
                'role_id' => $target->role_id,
                'is_active' => false,
            ]), 'Keep the account active until the channel handover is complete.');
        }
    }

    /**
     * HR master data: grades, a proposed division, a department, an org
     * unit, a position and two benefit proposals at different stages.
     */
    protected function seedMasterData(): void
    {
        // Grade waiting on HR.
        $this->submit('hr.staff@example.com', 'grades', ApprovalRequest::ACTION_CREATE, null, [
            'name' => 'Principal Engineer',
            'level' => 8,
            'description' => 'Technical track above Senior Staff.',
            'is_active' => true,
        ]);

        // Grade approved.
        $this->approveAll($this->submit('hr.staff@example.com', 'grades', ApprovalRequest::ACTION_CREATE, null, [
            'name' => 'Lead Technician',
            'level' => 3,
            'description' => 'Shift lead for the fabrication crew.',
            'is_active' => true,
        ]));

        // Grade rejected.
        $this->reject($this->submit('hr.staff@example.com', 'grades', ApprovalRequest::ACTION_CREATE, null, [
            'name' => 'Intern Grade',
            'level' => 1,
            'description' => 'Proposed intern band.',
            'is_active' => true,
        ]), 'Interns stay on the existing Staff band.');

        // New division waiting on the executive.
        $this->submit('hr.staff@example.com', 'divisions', ApprovalRequest::ACTION_CREATE, null, [
            'code' => 'DIV-RND',
            'name' => 'Research & Development',
            'description' => 'Product research and prototyping.',
            'is_active' => true,
        ]);

        // New department waiting on HR.
        $this->submit('hr.staff@example.com', 'departments', ApprovalRequest::ACTION_CREATE, null, [
            'division_id' => $this->id(Division::class, 'code', 'DIV-MFG'),
            'code' => 'DEP-TLS',
            'name' => 'Tooling',
            'description' => 'Dies, jigs and fixtures.',
            'is_active' => true,
        ]);

        // New org unit waiting on HR.
        $this->submit('hr.staff@example.com', 'org-units', ApprovalRequest::ACTION_CREATE, null, [
            'department_id' => $this->id(Department::class, 'code', 'DEP-MNT'),
            'code' => 'ORG-PDM',
            'name' => 'Predictive Maintenance',
            'description' => 'Condition monitoring and maintenance planning.',
            'is_active' => true,
        ]);

        // Position approved.
        $this->approveAll($this->submit('hr.staff@example.com', 'positions', ApprovalRequest::ACTION_CREATE, null, [
            'department_id' => $this->id(Department::class, 'code', 'DEP-DAT'),
            'code' => 'POS-ANL',
            'name' => 'Data Analyst',
            'description' => 'Reporting and dashboard delivery.',
            'is_active' => true,
        ]));

        // Benefit waiting on HR.
        $this->submit('hr.staff@example.com', 'benefits', ApprovalRequest::ACTION_CREATE, null, [
            'code' => 'BEN-RELO',
            'name' => 'Relocation Assistance',
            'type' => 'allowance',
            'description' => 'Support for employees moving to a new site.',
            'limit_amount' => '15000000.00',
            'period' => 'once',
            'min_tenure_months' => 6,
            'requires_receipt' => true,
            'is_active' => true,
        ]);

        // Benefit advanced to Finance.
        $this->advance($this->submit('hr.staff@example.com', 'benefits', ApprovalRequest::ACTION_CREATE, null, [
            'code' => 'BEN-CHILD',
            'name' => 'Child Education Support',
            'type' => 'education',
            'description' => 'Yearly support for dependant school fees.',
            'limit_amount' => '8000000.00',
            'period' => 'yearly',
            'min_tenure_months' => 12,
            'requires_receipt' => true,
            'is_active' => true,
        ]), 1);
    }

    /**
     * Site requests: an overflow warehouse waiting on the executive, a
     * withdrawn cold storage plan, a new workshop and a notes update.
     */
    protected function seedSites(): void
    {
        // Waiting on the executive.
        $this->submit('ops.manager@example.com', 'sites', ApprovalRequest::ACTION_CREATE, null, [
            'parent_id' => $this->id(Site::class, 'code', 'CMP-NUSANTARA'),
            'code' => null,
            'name' => 'Cikarang Overflow Warehouse',
            'type' => 'warehouse',
            'description' => 'Overflow storage for the distribution centre.',
            'address' => 'Kawasan Industri Jababeka Blok G',
            'city' => 'Cikarang',
            'province' => 'Jawa Barat',
            'postal_code' => '17530',
            'phone' => '021-8934001',
            'email' => 'wh.ckr.overflow@nusantaraindustri.co.id',
            'notes' => null,
            'is_active' => true,
        ]);

        // Withdrawn before review.
        $this->cancel($this->submit('ops.manager@example.com', 'sites', ApprovalRequest::ACTION_CREATE, null, [
            'parent_id' => $this->id(Site::class, 'code', 'CMP-NUSANTARA'),
            'code' => null,
            'name' => 'Cikarang Cold Storage',
            'type' => 'warehouse',
            'description' => 'Chilled storage for temperature-sensitive goods.',
            'address' => 'Kawasan Industri Jababeka Blok G',
            'city' => 'Cikarang',
            'province' => 'Jawa Barat',
            'postal_code' => '17530',
            'phone' => '021-8934002',
            'email' => 'cold.ckr@nusantaraindustri.co.id',
            'notes' => null,
            'is_active' => true,
        ]));

        // Approved: the paint shop exists after the approval.
        $this->approveAll($this->submit('ops.manager@example.com', 'sites', ApprovalRequest::ACTION_CREATE, null, [
            'parent_id' => $this->id(Site::class, 'code', 'FAC-SBY'),
            'code' => null,
            'name' => 'Surabaya Paint Shop',
            'type' => 'workshop',
            'description' => 'Surface preparation and painting.',
            'address' => 'Jl. Rungkut Industri III Blok C',
            'city' => 'Surabaya',
            'province' => 'Jawa Timur',
            'postal_code' => '60293',
            'phone' => '031-8410250',
            'email' => 'paint.sby@nusantaraindustri.co.id',
            'notes' => null,
            'is_active' => true,
        ]));

        $site = Site::query()->where('code', 'BR-MKS')->first();

        if ($site !== null) {
            // Notes update approved on an existing site.
            $this->approveAll($this->submit(
                'ops.manager@example.com',
                'sites',
                ApprovalRequest::ACTION_UPDATE,
                $site->getKey(),
                $this->sitePayload($site, ['notes' => 'Fleet vehicle renewal scheduled for next quarter.']),
            ));
        }
    }

    /**
     * Enrollment that can back a new claim, skipping the first ones so
     * each scenario uses a different employee.
     */
    protected function claimableEnrollment(int $skip): ?BenefitEnrollment
    {
        $service = app(BenefitEligibilityService::class);

        return BenefitEnrollment::query()
            ->with(['benefit', 'employee'])
            ->where('status', BenefitEnrollment::STATUS_ACTIVE)
            ->orderBy('id')
            ->get()
            ->filter(fn (BenefitEnrollment $enrollment): bool => $enrollment->isUsable()
                && (float) $service->remainingAmount($enrollment) > 500000)
            ->values()
            ->skip($skip)
            ->first();
    }

    /**
     * A claim request sized to the remaining cover of the enrollment, so
     * the submission passes the same balance guard the form applies.
     *
     * @return array<string, mixed>|null
     */
    protected function claimPayload(BenefitEnrollment $enrollment, float $share, string $description): ?array
    {
        $remaining = (float) app(BenefitEligibilityService::class)->remainingAmount($enrollment);
        $amount = round($remaining * $share, 2);

        if ($amount < 10000) {
            return null;
        }

        return [
            'benefit_id' => $enrollment->benefit_id,
            'benefit_enrollment_id' => $enrollment->getKey(),
            'employee_id' => $enrollment->employee_id,
            'claim_date' => Carbon::today()->toDateString(),
            'amount' => number_format($amount, 2, '.', ''),
            'description' => $description,
            'receipt_path' => $enrollment->benefit->requires_receipt ? 'benefit-claims/demo/claim-request.pdf' : null,
            'status' => BenefitClaim::STATUS_PENDING,
        ];
    }

    /**
     * A benefit and employee that may be enrolled today and are not
     * already enrolled, skipping the first candidates.
     *
     * @return array{benefit: Benefit, employee: Employee}|null
     */
    protected function enrollablePair(string $benefitCode, int $skip = 0): ?array
    {
        $benefit = Benefit::query()->where('code', $benefitCode)->first();

        if ($benefit === null) {
            return null;
        }

        $service = app(BenefitEligibilityService::class);
        $enrolled = BenefitEnrollment::query()->where('benefit_id', $benefit->getKey())->pluck('employee_id');

        $employee = Employee::query()
            ->where('is_active', true)
            ->whereNotIn('id', $enrolled)
            ->orderBy('employee_number')
            ->get()
            ->filter(fn (Employee $candidate): bool => $service->isEnrollable($candidate, $benefit))
            ->values()
            ->skip($skip)
            ->first();

        return $employee === null ? null : ['benefit' => $benefit, 'employee' => $employee];
    }

    /**
     * @return array<string, mixed>
     */
    protected function enrollmentPayload(Benefit $benefit, Employee $employee): array
    {
        return [
            'benefit_id' => $benefit->getKey(),
            'employee_id' => $employee->getKey(),
            'status' => BenefitEnrollment::STATUS_ACTIVE,
            'effective_from' => Carbon::today()->toDateString(),
            'effective_to' => null,
            'notes' => 'Requested by the employee during the benefits window.',
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    protected function productPayload(Product $product, array $changes = []): array
    {
        return [...$product->only([
            'sku', 'barcode', 'name', 'description', 'category_id', 'brand_id', 'unit_id',
            'cost_price', 'selling_price', 'tax_rate', 'stock_quantity', 'reorder_level',
            'track_stock', 'is_active', 'image_path',
        ]), ...$changes];
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    protected function sitePayload(Site $site, array $changes = []): array
    {
        return [...$site->only([
            'parent_id', 'code', 'name', 'type', 'description', 'address', 'city',
            'province', 'postal_code', 'phone', 'email', 'notes', 'is_active',
        ]), ...$changes];
    }
}
