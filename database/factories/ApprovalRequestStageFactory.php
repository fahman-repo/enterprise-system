<?php

namespace Database\Factories;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalRequestStage>
 */
class ApprovalRequestStageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approval_request_id' => ApprovalRequest::factory(),
            'stage_number' => 1,
            'name' => 'Manager Approval',
            'status' => ApprovalRequestStage::STATUS_PENDING,
        ];
    }
}
