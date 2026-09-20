<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\OrgUnit;
use Illuminate\Database\Seeder;

class OrgUnitSeeder extends Seeder
{
    /**
     * Seed the organizational units under each department.
     */
    public function run(): void
    {
        $orgUnits = [
            ['code' => 'ORG-PAY', 'name' => 'Payroll Unit', 'department' => 'DEP-HRO', 'description' => 'Salary processing and statutory reporting.'],
            ['code' => 'ORG-REC', 'name' => 'Recruitment Unit', 'department' => 'DEP-REC', 'description' => 'Sourcing, screening and onboarding.'],
            ['code' => 'ORG-GL', 'name' => 'General Ledger Unit', 'department' => 'DEP-ACC', 'description' => 'Journals, reconciliation and closing.'],
            ['code' => 'ORG-LINE', 'name' => 'Production Line A', 'department' => 'DEP-PRD', 'description' => 'Assembly line operations.'],
            ['code' => 'ORG-WHS', 'name' => 'Warehouse Unit', 'department' => 'DEP-LOG', 'description' => 'Inbound, storage and outbound goods.'],
            ['code' => 'ORG-INS', 'name' => 'Inside Sales', 'department' => 'DEP-SLS', 'description' => 'Inbound enquiries and order taking.'],
            ['code' => 'ORG-DIG', 'name' => 'Digital Marketing', 'department' => 'DEP-MKT', 'description' => 'Online campaigns and content.'],
            ['code' => 'ORG-HLP', 'name' => 'Helpdesk', 'department' => 'DEP-ITS', 'description' => 'First-line technical support.'],
            ['code' => 'ORG-QA', 'name' => 'Quality Assurance', 'department' => 'DEP-ENG', 'description' => 'Testing and release verification.'],
        ];

        foreach ($orgUnits as $attributes) {
            $departmentCode = $attributes['department'];
            unset($attributes['department']);

            $attributes['department_id'] = Department::query()->where('code', $departmentCode)->value('id');

            OrgUnit::query()->updateOrCreate(
                ['code' => $attributes['code']],
                $attributes,
            );
        }
    }
}
