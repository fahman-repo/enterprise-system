<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'division_id' => Division::factory(),
            'code' => strtoupper(fake()->unique()->bothify('DEP-###')),
            'name' => Str::title(fake()->unique()->words(2, true)),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * An inactive department (hidden from selection forms).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Place the department under the given division.
     */
    public function forDivision(Division $division): static
    {
        return $this->state(fn (array $attributes) => [
            'division_id' => $division->id,
        ]);
    }
}
