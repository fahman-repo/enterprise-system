<?php

namespace Database\Factories;

use App\Models\Benefit;
use App\Models\BenefitClaim;
use App\Models\BenefitEnrollment;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BenefitClaim>
 */
class BenefitClaimFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Builds a consistent benefit/enrollment/employee triple so claims
     * never reference mismatched foreign keys.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'benefit_id' => Benefit::factory(),
            'employee_id' => Employee::factory(),
            'benefit_enrollment_id' => function (array $attributes): int {
                return BenefitEnrollment::factory()->create([
                    'benefit_id' => $attributes['benefit_id'],
                    'employee_id' => $attributes['employee_id'],
                ])->getKey();
            },
            'claim_date' => now()->toDateString(),
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'description' => fake()->sentence(),
            'receipt_path' => null,
            'status' => BenefitClaim::STATUS_PENDING,
            'resolution_comment' => null,
            'decided_at' => null,
            'paid_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (BenefitClaim $claim): void {
            $enrollment = null;

            if ($claim->benefit_enrollment_id instanceof BenefitEnrollment) {
                $enrollment = $claim->benefit_enrollment_id;
            } elseif (is_numeric($claim->benefit_enrollment_id)) {
                $enrollment = BenefitEnrollment::query()->find($claim->benefit_enrollment_id);
            }

            if ($enrollment) {
                $claim->benefit_id = $enrollment->benefit_id;
                $claim->employee_id = $enrollment->employee_id;
            }
        });
    }
}
