<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed a comprehensive product catalogue across the
     * category tree, brands and units of measure.
     */
    public function run(): void
    {
        if (Product::query()->withTrashed()->exists()) {
            return;
        }

        $brands = Brand::query()->where('is_active', true)->get();
        $units = Unit::query()->where('is_active', true)->get();
        $leaves = Category::query()->whereDoesntHave('children')->get();

        if ($leaves->isEmpty() || $brands->isEmpty() || $units->isEmpty()) {
            return;
        }

        $nouns = [
            'electronics' => ['Laptop', 'Smartphone', 'Headphones', 'Monitor', 'Keyboard', 'Speaker', 'Tablet', 'Webcam', 'Router', 'Camera'],
            'home-kitchen' => ['Cookware Set', 'Blender', 'Toaster', 'Coffee Maker', 'Dinner Plate', 'Storage Jar', 'Kettle', 'Cutlery Set', 'Table Lamp', 'Throw Blanket'],
            'office-supplies' => ['Notebook', 'Ballpoint Pen', 'Stapler', 'File Folder', 'Desk Organizer', 'Printer Paper', 'Whiteboard Marker', 'Binder', 'Label Roll', 'Desk Chair'],
            'apparel' => ['T-Shirt', 'Jeans', 'Jacket', 'Sneakers', 'Hoodie', 'Dress Shirt', 'Socks', 'Cap', 'Scarf', 'Belt'],
            'health-beauty' => ['Shampoo', 'Body Lotion', 'Toothpaste', 'Face Wash', 'Vitamin C Tablets', 'Hand Sanitizer', 'Sunscreen', 'Deodorant', 'Lip Balm', 'Hair Brush'],
            'grocery' => ['Coffee Beans', 'Green Tea', 'Olive Oil', 'Pasta', 'Granola Bars', 'Mineral Water', 'Orange Juice', 'Dark Chocolate', 'Rice', 'Honey'],
            'sports-outdoors' => ['Yoga Mat', 'Dumbbell Set', 'Water Bottle', 'Camping Tent', 'Sleeping Bag', 'Hiking Backpack', 'Resistance Bands', 'Trekking Poles', 'Cooler Box', 'Jump Rope'],
        ];

        $topLevelByLeaf = $leaves->mapWithKeys(fn (Category $leaf): array => [
            $leaf->getKey() => $this->topLevelSlug($leaf),
        ]);

        for ($index = 1; $index <= 250; $index++) {
            $category = $leaves->random();
            $brand = $brands->random();
            $unit = $units->random();

            $noun = fake()->randomElement($nouns[$topLevelByLeaf[$category->getKey()]] ?? ['Item']);
            $suffix = fake()->randomElement(['', ' Pro', ' Max', ' Mini', ' Classic', ' Plus', ' Lite', ' Ultra']);

            $factory = Product::factory()->state([
                'category_id' => $category->getKey(),
                'brand_id' => $brand->getKey(),
                'unit_id' => $unit->getKey(),
                'sku' => 'PRD-'.str_pad((string) $index, 5, '0', STR_PAD_LEFT),
                'barcode' => '200'.str_pad((string) $index, 10, '0', STR_PAD_LEFT),
                'name' => $brand->name.' '.$noun.$suffix,
            ]);

            $roll = fake()->numberBetween(1, 100);

            if ($roll > 95) {
                $factory = $factory->inactive();
            } elseif ($roll > 90) {
                $factory = $factory->untracked();
            } elseif ($roll > 80) {
                $factory = $factory->outOfStock();
            } elseif ($roll > 70) {
                $factory = $factory->lowStock();
            }

            $factory->create();
        }
    }

    /**
     * The slug of the top-level ancestor of the given category.
     */
    protected function topLevelSlug(Category $category): string
    {
        while ($category->parent_id !== null) {
            $category = $category->parent;
        }

        return $category->slug;
    }
}
