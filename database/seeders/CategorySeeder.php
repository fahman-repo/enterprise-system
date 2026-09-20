<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Seed a three-level product category tree.
     */
    public function run(): void
    {
        $tree = [
            ['name' => 'Electronics', 'children' => [
                ['name' => 'Computers', 'children' => [
                    ['name' => 'Laptops'],
                    ['name' => 'Desktops & Monitors'],
                    ['name' => 'Computer Accessories'],
                ]],
                ['name' => 'Mobile & Tablets', 'children' => [
                    ['name' => 'Smartphones'],
                    ['name' => 'Tablets & E-Readers'],
                ]],
                ['name' => 'Audio & Video', 'children' => [
                    ['name' => 'Headphones'],
                    ['name' => 'Speakers'],
                    ['name' => 'Cameras'],
                ]],
            ]],
            ['name' => 'Home & Kitchen', 'children' => [
                ['name' => 'Cookware'],
                ['name' => 'Small Appliances'],
                ['name' => 'Home Décor'],
            ]],
            ['name' => 'Office Supplies', 'children' => [
                ['name' => 'Stationery'],
                ['name' => 'Paper Products'],
                ['name' => 'Filing & Storage'],
                ['name' => 'Office Furniture'],
            ]],
            ['name' => 'Apparel', 'children' => [
                ['name' => "Men's Clothing"],
                ['name' => "Women's Clothing"],
                ['name' => 'Footwear'],
            ]],
            ['name' => 'Health & Beauty', 'children' => [
                ['name' => 'Personal Care'],
                ['name' => 'Vitamins & Supplements'],
            ]],
            ['name' => 'Grocery', 'children' => [
                ['name' => 'Beverages'],
                ['name' => 'Snacks'],
                ['name' => 'Pantry Staples'],
            ]],
            ['name' => 'Sports & Outdoors', 'children' => [
                ['name' => 'Fitness Equipment'],
                ['name' => 'Camping & Hiking'],
            ]],
        ];

        $this->seedBranch($tree);
    }

    /**
     * Recursively create categories, linking each level to its parent.
     *
     * @param  list<array<string, mixed>>  $branches
     */
    protected function seedBranch(array $branches, ?Category $parent = null): void
    {
        $sort = 0;

        foreach ($branches as $branch) {
            $category = Category::query()->updateOrCreate(
                ['slug' => Str::slug($branch['name'])],
                [
                    'parent_id' => $parent?->getKey(),
                    'name' => $branch['name'],
                    'sort_order' => ++$sort,
                    'is_active' => true,
                ],
            );

            if (! empty($branch['children'])) {
                $this->seedBranch($branch['children'], $category);
            }
        }
    }
}
