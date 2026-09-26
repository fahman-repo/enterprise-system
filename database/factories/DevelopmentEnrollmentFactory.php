<?php

namespace Database\Factories;

use App\Models\DevelopmentEnrollment;
use App\Models\DevelopmentProgram;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevelopmentEnrollment>
 */
class DevelopmentEnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'development_program_id' => DevelopmentProgram::factory(),
            'employee_id' => Employee::factory(),
            'status' => 'registered',
            'score' => null,
            'completed_at' => null,
            'certificate_no' => null,
            'notes' => null,
        ];
    }
}
