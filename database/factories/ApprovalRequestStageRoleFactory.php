<?php

namespace Database\Factories;

use App\Models\ApprovalRequestStage;
use App\Models\ApprovalRequestStageRole;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalRequestStageRole>
 */
class ApprovalRequestStageRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approval_request_stage_id' => ApprovalRequestStage::factory(),
            'role_id' => Role::factory(),
            'role_name' => 'Approver',
            'role_slug' => 'approver',
        ];
    }
}
