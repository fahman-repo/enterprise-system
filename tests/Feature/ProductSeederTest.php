<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Menu;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use Database\Seeders\DatabaseSeeder;

test('database seeder builds a comprehensive product catalogue', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Product::query()->count())->toBe(250)
        ->and(Unit::query()->count())->toBe(12)
        ->and(Brand::query()->count())->toBe(15)
        ->and(Category::query()->count())->toBe(35)
        ->and(Product::query()->whereNotNull('category_id')->count())->toBe(250)
        ->and(Product::query()->whereNotNull('brand_id')->count())->toBe(250)
        ->and(Product::query()->whereNotNull('unit_id')->count())->toBe(250);
});

test('database seeder creates the product management menus and admin permissions', function () {
    $this->seed(DatabaseSeeder::class);

    $parent = Menu::query()->where('slug', 'product-management')->sole();

    expect($parent->parent_id)->toBeNull()
        ->and(Menu::query()->where('parent_id', $parent->id)->count())->toBe(4);

    $admin = Role::query()->where('slug', 'admin')->sole();
    $slugs = $admin->menus()->pluck('slug');

    expect($slugs)->toContain('products')
        ->toContain('categories')
        ->toContain('brands')
        ->toContain('units');
});

test('database seeder creates the hr management menus and admin permissions', function () {
    $this->seed(DatabaseSeeder::class);

    $management = Menu::query()->where('slug', 'hr-management')->sole();
    $settings = Menu::query()->where('slug', 'hr-settings')->sole();

    expect($management->parent_id)->toBeNull()
        ->and($settings->parent_id)->toBeNull()
        ->and(Menu::query()->where('parent_id', $management->id)->count())->toBe(6)
        ->and(Menu::query()->where('parent_id', $settings->id)->count())->toBe(6);

    $admin = Role::query()->where('slug', 'admin')->sole();
    $slugs = $admin->menus()->pluck('slug');

    expect($slugs)->toContain('employees')
        ->toContain('divisions')
        ->toContain('departments')
        ->toContain('org-units')
        ->toContain('positions')
        ->toContain('org-chart')
        ->toContain('grades')
        ->toContain('employment-statuses')
        ->toContain('work-locations')
        ->toContain('religions')
        ->toContain('education-levels')
        ->toContain('marital-statuses');
});

test('database seeder is idempotent', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Product::query()->count())->toBe(250)
        ->and(Unit::query()->count())->toBe(12)
        ->and(Brand::query()->count())->toBe(15)
        ->and(Category::query()->count())->toBe(35)
        ->and(Division::query()->count())->toBe(8)
        ->and(Department::query()->count())->toBe(28)
        ->and(Employee::query()->count())->toBe(260)
        ->and(Menu::query()->count())->toBe(26);
});
