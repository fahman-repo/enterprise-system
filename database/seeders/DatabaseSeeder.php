<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with the module menus, the
     * admin role (full permission matrix), the first admin user
     * and the product catalogue.
     */
    public function run(): void
    {
        $menus = collect([
            ['name' => 'Users', 'slug' => 'users', 'icon' => 'users', 'route_name' => 'users.index', 'sort_order' => 1],
            ['name' => 'Roles', 'slug' => 'roles', 'icon' => 'settings', 'route_name' => 'roles.index', 'sort_order' => 2],
            ['name' => 'Menus', 'slug' => 'menus', 'icon' => 'menu', 'route_name' => 'menus.index', 'sort_order' => 3],
            ['name' => 'Audit Logs', 'slug' => 'audit-logs', 'icon' => 'alert-circle', 'route_name' => 'audit-logs.index', 'sort_order' => 4],
            ['name' => 'Product Management', 'slug' => 'product-management', 'icon' => 'package', 'route_name' => null, 'sort_order' => 5],
            ['name' => 'Products', 'slug' => 'products', 'icon' => 'package', 'route_name' => 'products.index', 'sort_order' => 1, 'parent' => 'product-management'],
            ['name' => 'Categories', 'slug' => 'categories', 'icon' => 'folder-tree', 'route_name' => 'categories.index', 'sort_order' => 2, 'parent' => 'product-management'],
            ['name' => 'Brands', 'slug' => 'brands', 'icon' => 'tag', 'route_name' => 'brands.index', 'sort_order' => 3, 'parent' => 'product-management'],
            ['name' => 'Units', 'slug' => 'units', 'icon' => 'ruler', 'route_name' => 'units.index', 'sort_order' => 4, 'parent' => 'product-management'],
            ['name' => 'HR Management', 'slug' => 'hr-management', 'icon' => 'briefcase', 'route_name' => null, 'sort_order' => 6],
            ['name' => 'Employees', 'slug' => 'employees', 'icon' => 'users', 'route_name' => 'employees.index', 'sort_order' => 1, 'parent' => 'hr-management'],
            ['name' => 'Divisions', 'slug' => 'divisions', 'icon' => 'building-2', 'route_name' => 'divisions.index', 'sort_order' => 2, 'parent' => 'hr-management'],
            ['name' => 'Departments', 'slug' => 'departments', 'icon' => 'building', 'route_name' => 'departments.index', 'sort_order' => 3, 'parent' => 'hr-management'],
            ['name' => 'Org Units', 'slug' => 'org-units', 'icon' => 'network', 'route_name' => 'org-units.index', 'sort_order' => 4, 'parent' => 'hr-management'],
            ['name' => 'Positions', 'slug' => 'positions', 'icon' => 'badge', 'route_name' => 'positions.index', 'sort_order' => 5, 'parent' => 'hr-management'],
            ['name' => 'HR Settings', 'slug' => 'hr-settings', 'icon' => 'settings', 'route_name' => null, 'sort_order' => 7],
            ['name' => 'Grades', 'slug' => 'grades', 'icon' => 'layers', 'route_name' => 'grades.index', 'sort_order' => 1, 'parent' => 'hr-settings'],
            ['name' => 'Employment Statuses', 'slug' => 'employment-statuses', 'icon' => 'toggle-right', 'route_name' => 'employment-statuses.index', 'sort_order' => 2, 'parent' => 'hr-settings'],
            ['name' => 'Work Locations', 'slug' => 'work-locations', 'icon' => 'map-pin', 'route_name' => 'work-locations.index', 'sort_order' => 3, 'parent' => 'hr-settings'],
            ['name' => 'Religions', 'slug' => 'religions', 'icon' => 'book-open', 'route_name' => 'religions.index', 'sort_order' => 4, 'parent' => 'hr-settings'],
            ['name' => 'Education Levels', 'slug' => 'education-levels', 'icon' => 'graduation-cap', 'route_name' => 'education-levels.index', 'sort_order' => 5, 'parent' => 'hr-settings'],
            ['name' => 'Marital Statuses', 'slug' => 'marital-statuses', 'icon' => 'heart', 'route_name' => 'marital-statuses.index', 'sort_order' => 6, 'parent' => 'hr-settings'],
        ])->map(function (array $attributes): Menu {
            $parentSlug = $attributes['parent'] ?? null;
            unset($attributes['parent']);

            $attributes['parent_id'] = $parentSlug === null
                ? null
                : Menu::query()->where('slug', $parentSlug)->value('id');

            return Menu::query()->updateOrCreate(['slug' => $attributes['slug']], $attributes);
        });

        $admin = Role::query()->updateOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin'],
        );

        $admin->menus()->sync($menus->mapWithKeys(fn (Menu $menu) => [
            $menu->id => [
                'can_view' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
            ],
        ]));

        app(PermissionService::class)->flush();

        User::query()->updateOrCreate(
            ['email' => env('FIRST_ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => 'Administrator',
                'password' => env('FIRST_ADMIN_PASSWORD', 'password'),
                'role_id' => $admin->id,
            ],
        );

        $this->call([
            UnitSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            DivisionSeeder::class,
            DepartmentSeeder::class,
            OrgUnitSeeder::class,
            GradeSeeder::class,
            PositionSeeder::class,
            EmploymentStatusSeeder::class,
            WorkLocationSeeder::class,
            ReligionSeeder::class,
            EducationLevelSeeder::class,
            MaritalStatusSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
