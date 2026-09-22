<?php

namespace Database\Factories;

use App\Models\Entity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entity>
 */
class EntityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('ENT-#####')),
            'name' => fake()->company(),
            'type' => 'company',
            'role' => fake()->randomElement(Entity::ROLES),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'identity_number' => null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'bank_name' => fake()->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri']),
            'bank_account_number' => fake()->unique()->numerify('##########'),
            'bank_account_name' => fake()->name(),
            'notes' => null,
            'is_active' => true,
        ];
    }

    /**
     * A personal (individual) entity with KTP identity number.
     */
    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'personal',
            'name' => fake()->name(),
            'identity_number' => fake()->unique()->numerify('################'),
            'npwp' => null,
        ]);
    }

    /**
     * A company entity without a KTP identity number.
     */
    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'company',
            'identity_number' => null,
        ]);
    }

    /**
     * A vendor entity.
     */
    public function vendor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'vendor',
        ]);
    }

    /**
     * A customer entity.
     */
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'customer',
        ]);
    }

    /**
     * An entity acting as both vendor and customer.
     */
    public function vendorAndCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'both',
        ]);
    }

    /**
     * An inactive entity (no longer traded with).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
