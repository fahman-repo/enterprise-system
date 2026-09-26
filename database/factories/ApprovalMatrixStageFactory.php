<?php

namespace Database\Factories;

use App\Models\ApprovalMatrix;
use App\Models\ApprovalMatrixStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalMatrixStage>
 */
class ApprovalMatrixStageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approval_matrix_id' => ApprovalMatrix::factory(),
            'stage_number' => 1,
            'name' => 'Manager Approval',
        ];
    }
}
