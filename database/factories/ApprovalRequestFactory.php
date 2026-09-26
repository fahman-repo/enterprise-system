<?php

namespace Database\Factories;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalRequest>
 */
class ApprovalRequestFactory extends Factory
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
            'action' => ApprovalRequest::ACTION_CREATE,
            'target_id' => null,
            'proposed_payload' => ['name' => 'Requested Grade', 'level' => 1, 'is_active' => true],
            'before_payload' => null,
            'maker_user_id' => User::factory(),
            'maker_snapshot' => ['name' => 'Maker', 'email' => 'maker@example.com'],
            'status' => ApprovalRequest::STATUS_PENDING,
            'current_stage' => 1,
            'matrix_configuration_version' => 1,
            'approval_mode' => ApprovalRequest::MODE_SEQUENTIAL,
            'submitted_at' => now(),
        ];
    }

    public function forAction(string $action, ?int $targetId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => $action,
            'target_id' => $targetId,
        ]);
    }
}
