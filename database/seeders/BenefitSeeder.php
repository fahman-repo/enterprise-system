<?php

namespace Database\Seeders;

use App\Models\Benefit;
use App\Models\EmploymentStatus;
use App\Models\Grade;
use Illuminate\Database\Seeder;

class BenefitSeeder extends Seeder
{
    /**
     * Seed the canonical benefit catalogue.
     *
     * Single currency, no conversion or proration. A zero limit means zero
     * cover, not unlimited. Pivots stay empty (all eligible) except where
     * noted.
     */
    public function run(): void
    {
        $benefits = [
            [
                'code' => 'BEN-MEDICAL',
                'name' => 'Medical Reimbursement',
                'type' => 'medical',
                'description' => 'Yearly reimbursement for medical expenses.',
                'limit_amount' => '20000000.00',
                'period' => 'yearly',
                'min_tenure_months' => 0,
                'requires_receipt' => true,
                'is_active' => true,
            ],
            [
                'code' => 'BEN-DENTAL',
                'name' => 'Dental Care',
                'type' => 'medical',
                'description' => 'Yearly dental treatment cover.',
                'limit_amount' => '5000000.00',
                'period' => 'yearly',
                'min_tenure_months' => 0,
                'requires_receipt' => true,
                'is_active' => true,
            ],
            [
                'code' => 'BEN-MEAL',
                'name' => 'Meal Allowance',
                'type' => 'meal',
                'description' => 'Monthly meal allowance.',
                'limit_amount' => '1500000.00',
                'period' => 'monthly',
                'min_tenure_months' => 0,
                'requires_receipt' => false,
                'is_active' => true,
            ],
            [
                'code' => 'BEN-TRANSPORT',
                'name' => 'Transport Allowance',
                'type' => 'transport',
                'description' => 'Monthly transport allowance.',
                'limit_amount' => '2000000.00',
                'period' => 'monthly',
                'min_tenure_months' => 0,
                'requires_receipt' => false,
                'is_active' => true,
            ],
            [
                'code' => 'BEN-EDUCATION',
                'name' => 'Education Assistance',
                'type' => 'education',
                'description' => 'One-time education assistance after one year of service.',
                'limit_amount' => '10000000.00',
                'period' => 'once',
                'min_tenure_months' => 12,
                'requires_receipt' => true,
                'is_active' => true,
            ],
            [
                'code' => 'BEN-WELLNESS',
                'name' => 'Wellness Program',
                'type' => 'wellness',
                'description' => 'Yearly wellness and fitness cover.',
                'limit_amount' => '3000000.00',
                'period' => 'yearly',
                'min_tenure_months' => 0,
                'requires_receipt' => true,
                'is_active' => true,
            ],
            [
                'code' => 'BEN-EXEC-HEALTH',
                'name' => 'Executive Health Plan',
                'type' => 'insurance',
                'description' => 'Premium health cover for management grades after one year of service.',
                'limit_amount' => '60000000.00',
                'period' => 'yearly',
                'min_tenure_months' => 12,
                'requires_receipt' => true,
                'is_active' => true,
            ],
        ];

        foreach ($benefits as $attributes) {
            Benefit::query()->updateOrCreate(
                ['code' => $attributes['code']],
                $attributes,
            );
        }

        $this->syncEligibility();
    }

    /**
     * Eligibility windows that make the catalogue realistic: education
     * assistance only reaches supervisory grades and above, the meal
     * allowance excludes interns and outsourced staff, and the executive
     * plan is limited to management grades. Benefits without a window
     * stay open to every employee, which is what the pivots encode.
     */
    protected function syncEligibility(): void
    {
        $grades = Grade::query()->pluck('id', 'name');
        $statuses = EmploymentStatus::query()->pluck('id', 'code');
        $benefits = Benefit::query()->get()->keyBy('code');

        $gradeWindows = [
            'BEN-EDUCATION' => ['Supervisor', 'Assistant Manager', 'Manager', 'General Manager', 'Director'],
            'BEN-EXEC-HEALTH' => ['Manager', 'General Manager', 'Director'],
        ];

        foreach ($gradeWindows as $code => $names) {
            $benefit = $benefits->get($code);

            if ($benefit === null) {
                continue;
            }

            $benefit->eligibleGrades()->sync(
                collect($names)->map(fn (string $name): ?int => $grades->get($name))->filter()->values()->all(),
            );
        }

        $meal = $benefits->get('BEN-MEAL');

        if ($meal !== null) {
            $meal->eligibleEmploymentStatuses()->sync(
                collect(['PERM', 'CONT', 'PROB'])->map(fn (string $code): ?int => $statuses->get($code))->filter()->values()->all(),
            );
        }
    }
}
