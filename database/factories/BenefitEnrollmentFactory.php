<?php

namespace Database\Factories;

use App\Models\Benefit;
use App\Models\BenefitEnrollment;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BenefitEnrollment>
 */
class BenefitEnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'benefit_id' => Benefit::factory(),
            'employee_id' => Employee::factory(),
            'status' => BenefitEnrollment::STATUS_ACTIVE,
            'effective_from' => now()->subYear()->toDateString(),
            'effective_to' => null,
            'notes' => null,
        ];
    }
}
