<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'code' => strtoupper(fake()->unique()->bothify('SIT-#####')),
            'name' => Str::title(fake()->unique()->city()),
            'type' => 'branch',
            'description' => null,
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'phone' => fake()->numerify('021-#######'),
            'email' => fake()->unique()->safeEmail(),
            'notes' => null,
            'is_active' => true,
        ];
    }

    /**
     * A company site, the root of the hierarchy.
     */
    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'company',
            'parent_id' => null,
        ]);
    }

    /**
     * A building site.
     */
    public function building(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'building',
        ]);
    }

    /**
     * A branch site.
     */
    public function branch(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'branch',
        ]);
    }

    /**
     * A warehouse site.
     */
    public function warehouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'warehouse',
        ]);
    }

    /**
     * A workshop site.
     */
    public function workshop(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'workshop',
        ]);
    }

    /**
     * A factory site.
     */
    public function factory(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'factory',
        ]);
    }

    /**
     * Nest the site under the given parent.
     */
    public function under(Site $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
        ]);
    }

    /**
     * An inactive site (hidden from selection forms).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
