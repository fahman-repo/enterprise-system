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
            ['code' => 'ORG-GSV', 'name' => 'General Services', 'department' => 'DEP-HRO', 'description' => 'Office services and facilities administration.'],
            ['code' => 'ORG-TRN', 'name' => 'Training Unit', 'department' => 'DEP-REC', 'description' => 'Learning programs and competency development.'],
            ['code' => 'ORG-TRS', 'name' => 'Treasury Unit', 'department' => 'DEP-FIN', 'description' => 'Cash management and banking.'],
            ['code' => 'ORG-BUD', 'name' => 'Budgeting Unit', 'department' => 'DEP-FIN', 'description' => 'Budget preparation and monitoring.'],
            ['code' => 'ORG-AR', 'name' => 'Receivables Unit', 'department' => 'DEP-ACC', 'description' => 'Billing, collections and receivables.'],
            ['code' => 'ORG-LNB', 'name' => 'Production Line B', 'department' => 'DEP-PRD', 'description' => 'Second assembly line operations.'],
            ['code' => 'ORG-DIS', 'name' => 'Distribution Unit', 'department' => 'DEP-LOG', 'description' => 'Outbound distribution and delivery.'],
            ['code' => 'ORG-OUT', 'name' => 'Outside Sales', 'department' => 'DEP-SLS', 'description' => 'Field sales and account visits.'],
            ['code' => 'ORG-BRD', 'name' => 'Brand & Creative', 'department' => 'DEP-MKT', 'description' => 'Brand management and creative production.'],
            ['code' => 'ORG-INF', 'name' => 'Infrastructure Unit', 'department' => 'DEP-ITS', 'description' => 'Servers, networks and cloud platforms.'],
            ['code' => 'ORG-DEV', 'name' => 'Development Unit', 'department' => 'DEP-ENG', 'description' => 'Application design and implementation.'],
            ['code' => 'ORG-PRL', 'name' => 'Payroll Processing', 'department' => 'DEP-PAY', 'description' => 'Monthly payroll runs and payslips.'],
            ['code' => 'ORG-BEN', 'name' => 'Benefits Administration', 'department' => 'DEP-PAY', 'description' => 'Insurance, allowances and claims.'],
            ['code' => 'ORG-ERL', 'name' => 'Employee Relations', 'department' => 'DEP-ERD', 'description' => 'Employee engagement and welfare.'],
            ['code' => 'ORG-DSC', 'name' => 'Discipline & Grievance', 'department' => 'DEP-ERD', 'description' => 'Disciplinary cases and grievance handling.'],
            ['code' => 'ORG-TAX', 'name' => 'Tax Compliance', 'department' => 'DEP-TAX', 'description' => 'Tax filing and statutory compliance.'],
            ['code' => 'ORG-TCR', 'name' => 'Tax Reporting', 'department' => 'DEP-TAX', 'description' => 'Tax provisions and corporate reporting.'],
            ['code' => 'ORG-BPL', 'name' => 'Budget Planning', 'department' => 'DEP-BUD', 'description' => 'Annual budget cycles and forecasts.'],
            ['code' => 'ORG-FPA', 'name' => 'Financial Analysis', 'department' => 'DEP-BUD', 'description' => 'Variance analysis and management reports.'],
            ['code' => 'ORG-PPS', 'name' => 'Production Scheduling', 'department' => 'DEP-PLN', 'description' => 'Master schedules and work orders.'],
            ['code' => 'ORG-PMC', 'name' => 'Material Control', 'department' => 'DEP-PLN', 'description' => 'Material requirements and procurement planning.'],
            ['code' => 'ORG-MEC', 'name' => 'Mechanical Maintenance', 'department' => 'DEP-MNT', 'description' => 'Mechanical equipment upkeep.'],
            ['code' => 'ORG-ELE', 'name' => 'Electrical Maintenance', 'department' => 'DEP-MNT', 'description' => 'Electrical and controls upkeep.'],
            ['code' => 'ORG-DSR', 'name' => 'Distributor Sales', 'department' => 'DEP-CHN', 'description' => 'Distributor accounts and sell-out.'],
            ['code' => 'ORG-RTL', 'name' => 'Retail Sales', 'department' => 'DEP-CHN', 'description' => 'Modern trade and retail accounts.'],
            ['code' => 'ORG-CSG', 'name' => 'Service Desk', 'department' => 'DEP-CUS', 'description' => 'Customer enquiries and complaints.'],
            ['code' => 'ORG-CLM', 'name' => 'Claims & Returns', 'department' => 'DEP-CUS', 'description' => 'Warranty claims and product returns.'],
            ['code' => 'ORG-BIA', 'name' => 'Business Intelligence', 'department' => 'DEP-DAT', 'description' => 'Dashboards and decision analytics.'],
            ['code' => 'ORG-DEN', 'name' => 'Data Engineering', 'department' => 'DEP-DAT', 'description' => 'Data pipelines and warehouses.'],
            ['code' => 'ORG-SOC', 'name' => 'Security Operations', 'department' => 'DEP-SEC', 'description' => 'Monitoring and incident response.'],
            ['code' => 'ORG-SGV', 'name' => 'Security Governance', 'department' => 'DEP-SEC', 'description' => 'Policies, risk and compliance.'],
            ['code' => 'ORG-AS1', 'name' => 'Assembly Line 1', 'department' => 'DEP-ASM', 'description' => 'First final-assembly line.'],
            ['code' => 'ORG-AS2', 'name' => 'Assembly Line 2', 'department' => 'DEP-ASM', 'description' => 'Second final-assembly line.'],
            ['code' => 'ORG-CNC', 'name' => 'Machining Unit', 'department' => 'DEP-FAB', 'description' => 'CNC machining and cutting.'],
            ['code' => 'ORG-WLD', 'name' => 'Welding Unit', 'department' => 'DEP-FAB', 'description' => 'Welding and metal forming.'],
            ['code' => 'ORG-IND', 'name' => 'Industrial Engineering', 'department' => 'DEP-PRC', 'description' => 'Line layout and work standards.'],
            ['code' => 'ORG-PIP', 'name' => 'Process Improvement', 'department' => 'DEP-PRC', 'description' => 'Continuous improvement programs.'],
            ['code' => 'ORG-QCI', 'name' => 'Incoming Quality', 'department' => 'DEP-QAC', 'description' => 'Supplier and incoming inspection.'],
            ['code' => 'ORG-QCO', 'name' => 'Outgoing Quality', 'department' => 'DEP-QAC', 'description' => 'Final inspection and release.'],
            ['code' => 'ORG-QMS', 'name' => 'Quality Management System', 'department' => 'DEP-QAS', 'description' => 'ISO systems and documentation.'],
            ['code' => 'ORG-QAU', 'name' => 'Quality Audit', 'department' => 'DEP-QAS', 'description' => 'Internal and supplier audits.'],
            ['code' => 'ORG-OSO', 'name' => 'Occupational Safety', 'department' => 'DEP-HSE', 'description' => 'Workplace safety and incident prevention.'],
            ['code' => 'ORG-ENV', 'name' => 'Environmental Unit', 'department' => 'DEP-HSE', 'description' => 'Waste, emissions and environmental compliance.'],
            ['code' => 'ORG-CPR', 'name' => 'Corporate Communications', 'department' => 'DEP-PRV', 'description' => 'Media relations and internal communications.'],
            ['code' => 'ORG-EVT', 'name' => 'Events & Sponsorship', 'department' => 'DEP-PRV', 'description' => 'Corporate events and sponsorships.'],
            ['code' => 'ORG-LAW', 'name' => 'Legal Advisory', 'department' => 'DEP-LGL', 'description' => 'Contracts and legal counsel.'],
            ['code' => 'ORG-CPL', 'name' => 'Corporate Compliance', 'department' => 'DEP-LGL', 'description' => 'Regulatory compliance and ethics.'],
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
