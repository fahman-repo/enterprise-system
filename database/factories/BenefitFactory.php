<?php

namespace Database\Factories;

use App\Models\Benefit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Benefit>
 */
class BenefitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'BEN-'.fake()->unique()->numerify('#####'),
            'name' => fake()->unique()->words(3, true),
            'type' => fake()->randomElement(Benefit::TYPES),
            'description' => fake()->sentence(),
            'limit_amount' => fake()->randomElement([500000, 1500000, 2000000, 3000000, 5000000, 10000000, 20000000]),
            'period' => fake()->randomElement(Benefit::PERIODS),
            'min_tenure_months' => fake()->randomElement([0, 0, 3, 6, 12]),
            'requires_receipt' => true,
            'is_active' => true,
        ];
    }

    /**
     * An inactive benefit (hidden from selection forms).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
