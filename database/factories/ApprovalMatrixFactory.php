<?php

namespace Database\Factories;

use App\Models\ApprovalMatrix;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalMatrix>
 */
class ApprovalMatrixFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_key' => 'grades',
            'is_active' => false,
            'configuration_version' => 1,
        ];
    }
}
