<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Seed the departments under each division.
     */
    public function run(): void
    {
        $departments = [
            ['code' => 'DEP-HRO', 'name' => 'HR Operations', 'division' => 'DIV-HR', 'description' => 'Employee administration, payroll and HR services.'],
            ['code' => 'DEP-REC', 'name' => 'Recruitment & Training', 'division' => 'DIV-HR', 'description' => 'Hiring, onboarding and people development.'],
            ['code' => 'DEP-FIN', 'name' => 'Finance', 'division' => 'DIV-FIN', 'description' => 'Treasury, budgeting and financial control.'],
            ['code' => 'DEP-ACC', 'name' => 'Accounting', 'division' => 'DIV-FIN', 'description' => 'Bookkeeping, tax and reporting.'],
            ['code' => 'DEP-PRD', 'name' => 'Production', 'division' => 'DIV-OPS', 'description' => 'Manufacturing and quality control.'],
            ['code' => 'DEP-LOG', 'name' => 'Logistics', 'division' => 'DIV-OPS', 'description' => 'Warehousing, inventory and distribution.'],
            ['code' => 'DEP-SLS', 'name' => 'Sales', 'division' => 'DIV-COM', 'description' => 'Direct and channel sales.'],
            ['code' => 'DEP-MKT', 'name' => 'Marketing', 'division' => 'DIV-COM', 'description' => 'Brand, campaign and market research.'],
            ['code' => 'DEP-ITS', 'name' => 'IT Support', 'division' => 'DIV-IT', 'description' => 'Infrastructure, networks and end-user support.'],
            ['code' => 'DEP-ENG', 'name' => 'Software Engineering', 'division' => 'DIV-IT', 'description' => 'Application development and delivery.'],
        ];

        foreach ($departments as $attributes) {
            $divisionCode = $attributes['division'];
            unset($attributes['division']);

            $attributes['division_id'] = Division::query()->where('code', $divisionCode)->value('id');

            Department::query()->updateOrCreate(
                ['code' => $attributes['code']],
                $attributes,
            );
        }
    }
}
