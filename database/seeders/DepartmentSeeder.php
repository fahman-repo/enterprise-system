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
            ['code' => 'DEP-PAY', 'name' => 'Payroll & Benefits', 'division' => 'DIV-HR', 'description' => 'Salary processing and employee benefits.'],
            ['code' => 'DEP-ERD', 'name' => 'Employee Relations', 'division' => 'DIV-HR', 'description' => 'Industrial relations and employee welfare.'],
            ['code' => 'DEP-TAX', 'name' => 'Tax & Treasury', 'division' => 'DIV-FIN', 'description' => 'Tax compliance and cash management.'],
            ['code' => 'DEP-BUD', 'name' => 'Budgeting & Control', 'division' => 'DIV-FIN', 'description' => 'Budget planning and financial control.'],
            ['code' => 'DEP-PLN', 'name' => 'Production Planning', 'division' => 'DIV-OPS', 'description' => 'Scheduling and material planning.'],
            ['code' => 'DEP-MNT', 'name' => 'Maintenance', 'division' => 'DIV-OPS', 'description' => 'Equipment upkeep and reliability.'],
            ['code' => 'DEP-CHN', 'name' => 'Channel Sales', 'division' => 'DIV-COM', 'description' => 'Distributor and retail channel sales.'],
            ['code' => 'DEP-CUS', 'name' => 'Customer Service', 'division' => 'DIV-COM', 'description' => 'Customer care and after-sales service.'],
            ['code' => 'DEP-DAT', 'name' => 'Data & Analytics', 'division' => 'DIV-IT', 'description' => 'Business intelligence and data platforms.'],
            ['code' => 'DEP-SEC', 'name' => 'Information Security', 'division' => 'DIV-IT', 'description' => 'Security operations and governance.'],
            ['code' => 'DEP-ASM', 'name' => 'Assembly', 'division' => 'DIV-MFG', 'description' => 'Product assembly operations.'],
            ['code' => 'DEP-FAB', 'name' => 'Fabrication', 'division' => 'DIV-MFG', 'description' => 'Machining, welding and forming.'],
            ['code' => 'DEP-PRC', 'name' => 'Process Engineering', 'division' => 'DIV-MFG', 'description' => 'Process design and improvement.'],
            ['code' => 'DEP-QAC', 'name' => 'Quality Control', 'division' => 'DIV-QA', 'description' => 'Incoming and outgoing inspection.'],
            ['code' => 'DEP-QAS', 'name' => 'Quality Systems', 'division' => 'DIV-QA', 'description' => 'Quality management systems and audits.'],
            ['code' => 'DEP-HSE', 'name' => 'Health, Safety & Environment', 'division' => 'DIV-QA', 'description' => 'Workplace safety and environmental care.'],
            ['code' => 'DEP-PRV', 'name' => 'Public Relations', 'division' => 'DIV-COR', 'description' => 'Corporate communications and events.'],
            ['code' => 'DEP-LGL', 'name' => 'Legal & Compliance', 'division' => 'DIV-COR', 'description' => 'Legal advisory and regulatory compliance.'],
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
