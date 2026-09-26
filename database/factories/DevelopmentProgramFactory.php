<?php

namespace Database\Factories;

use App\Models\DevelopmentProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevelopmentProgram>
 */
class DevelopmentProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'code' => strtoupper(fake()->unique()->bothify('DEV-#####')),
            'name' => fake()->sentence(3),
            'type' => fake()->randomElement(DevelopmentProgram::TYPES),
            'description' => fake()->sentence(),
            'organizer' => fake()->company(),
            'location' => fake()->city(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween($start, '+2 months')->format('Y-m-d'),
            'capacity' => fake()->numberBetween(5, 50),
            'cost' => fake()->randomFloat(2, 0, 10000000),
            'status' => 'planned',
            'is_active' => true,
        ];
    }

    /**
     * An inactive program (hidden from selection forms).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * A completed program with past dates.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'start_date' => fake()->dateTimeBetween('-3 months', '-2 months')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('-2 months', '-1 month')->format('Y-m-d'),
        ]);
    }
}
