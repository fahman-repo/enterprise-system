<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the demo logins, one per persona, each linked to the employee
     * record that person owns. Users are upserted by email and the
     * password comes from DEMO_PASSWORD so switching environment does
     * not silently rotate credentials.
     */
    public function run(): void
    {
        $roles = Role::query()->get(['id', 'slug'])->keyBy('slug');
        $password = (string) env('DEMO_PASSWORD', 'password');
        $credentials = [];

        foreach (self::definitions() as $email => $definition) {
            $employee = $this->resolveEmployee($definition['employee'] ?? null);

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $employee?->name ?? $definition['name'],
                    'password' => $password,
                    'role_id' => $roles->get($definition['role'])?->id,
                    'is_active' => $definition['active'] ?? true,
                ],
            );

            if ($employee !== null) {
                $employee->update(['user_id' => $user->id]);
            }

            $credentials[] = [$email, $definition['role'], $employee?->employee_number ?? '—'];
        }

        $this->command?->table(['Demo login', 'Role', 'Employee'], $credentials);
        $this->command?->info('Demo password: '.$password);
    }

    /**
     * Demo personas keyed by email. "employee" locates the workforce row
     * the persona owns; the user then inherits that person's name so the
     * audit trail and org chart line up.
     *
     * @return array<string, array{name: string, role: string, active?: bool, employee?: array{type: string, code?: string, index?: int}}>
     */
    public static function definitions(): array
    {
        return [
            'superadmin@example.com' => [
                'name' => 'Board Director',
                'role' => 'admin',
                'employee' => ['type' => 'position', 'code' => 'POS-CEO'],
            ],
            'hr.manager@example.com' => [
                'name' => 'HR Manager',
                'role' => 'hr-manager',
                'employee' => ['type' => 'division', 'code' => 'DIV-HR'],
            ],
            'hr.staff@example.com' => [
                'name' => 'HR Officer',
                'role' => 'hr-staff',
                'employee' => ['type' => 'position', 'code' => 'POS-HRO'],
            ],
            'hr.payroll@example.com' => [
                'name' => 'Payroll Staff',
                'role' => 'hr-staff',
                'employee' => ['type' => 'position', 'code' => 'POS-PAY-STAFF'],
            ],
            'finance.head@example.com' => [
                'name' => 'Finance Head',
                'role' => 'finance-approver',
                'employee' => ['type' => 'division', 'code' => 'DIV-FIN'],
            ],
            'ops.manager@example.com' => [
                'name' => 'Operations Manager',
                'role' => 'ops-manager',
                'employee' => ['type' => 'division', 'code' => 'DIV-OPS'],
            ],
            'manager.sby@example.com' => [
                'name' => 'Surabaya Plant Manager',
                'role' => 'branch-manager',
                'employee' => ['type' => 'department', 'code' => 'DEP-PRD'],
            ],
            'manager.jkt@example.com' => [
                'name' => 'Jakarta Sales Manager',
                'role' => 'branch-manager',
                'employee' => ['type' => 'department', 'code' => 'DEP-SLS'],
            ],
            'manager.qa@example.com' => [
                'name' => 'Quality Manager',
                'role' => 'branch-manager',
                'employee' => ['type' => 'department', 'code' => 'DEP-QAC'],
            ],
            'sales.jkt@example.com' => [
                'name' => 'Sales Executive',
                'role' => 'commercial-staff',
                'employee' => ['type' => 'position', 'code' => 'POS-SLS', 'index' => 0],
            ],
            'sales.sby@example.com' => [
                'name' => 'Channel Sales',
                'role' => 'commercial-staff',
                'employee' => ['type' => 'position', 'code' => 'POS-CHN-STAFF', 'index' => 0],
            ],
            'it.support@example.com' => [
                'name' => 'IT Support',
                'role' => 'it-support',
                'employee' => ['type' => 'position', 'code' => 'POS-ITS', 'index' => 0],
            ],
            'staff.sales@example.com' => [
                'name' => 'Sales Staff',
                'role' => 'employee-self',
                'employee' => ['type' => 'position', 'code' => 'POS-SLS', 'index' => 1],
            ],
            'staff.ops@example.com' => [
                'name' => 'Production Operator',
                'role' => 'employee-self',
                'employee' => ['type' => 'position', 'code' => 'POS-PRD', 'index' => 0],
            ],
            'staff.qa@example.com' => [
                'name' => 'Quality Inspector',
                'role' => 'employee-self',
                'employee' => ['type' => 'position', 'code' => 'POS-QAC-STAFF', 'index' => 0],
            ],
            'ex.staff@example.com' => [
                'name' => 'Former Employee',
                'role' => 'employee-self',
                'active' => false,
                'employee' => ['type' => 'inactive'],
            ],
        ];
    }

    /**
     * Resolve the employee a persona owns, by division head, department
     * head, nth holder of a position, or as an already-inactive record.
     *
     * @param  array{type: string, code?: string, index?: int}|null  $locator
     */
    protected function resolveEmployee(?array $locator): ?Employee
    {
        if ($locator === null) {
            return null;
        }

        $query = Employee::query();

        return match ($locator['type']) {
            'division' => $query->where('division_id', Division::query()->where('code', $locator['code'] ?? '')->value('id'))
                ->whereHas('position', fn ($position) => $position->where('code', 'POS-DIV-HEAD'))
                ->first(),
            'department' => $query->where('department_id', Department::query()->where('code', $locator['code'] ?? '')->value('id'))
                ->whereHas('position', fn ($position) => $position->where('code', 'POS-DEP-HEAD'))
                ->first(),
            'position' => $query->whereHas('position', fn ($position) => $position->where('code', $locator['code'] ?? ''))
                ->orderBy('employee_number')
                ->skip($locator['index'] ?? 0)
                ->first(),
            'inactive' => $query->where('is_active', false)->orderBy('employee_number')->first(),
            default => null,
        };
    }
}
