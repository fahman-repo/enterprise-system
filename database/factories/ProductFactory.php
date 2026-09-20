<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $costPrice = fake()->randomFloat(2, 1, 500);

        return [
            'category_id' => null,
            'brand_id' => null,
            'unit_id' => null,
            'sku' => strtoupper(fake()->unique()->bothify('PRD-#####')),
            'barcode' => fake()->unique()->ean13(),
            'name' => Str::title(fake()->words(3, true)),
            'description' => fake()->paragraph(),
            'cost_price' => $costPrice,
            'selling_price' => round($costPrice * fake()->randomFloat(2, 1.1, 1.8), 2),
            'tax_rate' => fake()->randomElement([0, 5, 10, 15]),
            'track_stock' => true,
            'stock_quantity' => fake()->numberBetween(0, 500),
            'reorder_level' => fake()->numberBetween(5, 25),
            'image_path' => null,
            'is_active' => true,
        ];
    }

    /**
     * An inactive product (hidden from ordering screens).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * A tracked product with no stock on hand.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_stock' => true,
            'stock_quantity' => 0,
        ]);
    }

    /**
     * A tracked product at or below its reorder level.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_stock' => true,
            'stock_quantity' => fake()->numberBetween(1, 5),
            'reorder_level' => 10,
        ]);
    }

    /**
     * A product that does not track stock levels.
     */
    public function untracked(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_stock' => false,
            'stock_quantity' => 0,
            'reorder_level' => null,
        ]);
    }
}
