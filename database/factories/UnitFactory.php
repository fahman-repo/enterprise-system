<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => Str::title($name),
            'abbreviation' => strtoupper(fake()->unique()->lexify('???')),
            'allows_decimal' => false,
            'is_active' => true,
        ];
    }

    /**
     * A unit that accepts fractional quantities (e.g. kilograms).
     */
    public function decimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'allows_decimal' => true,
        ]);
    }

    /**
     * An inactive unit (hidden from product forms).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
