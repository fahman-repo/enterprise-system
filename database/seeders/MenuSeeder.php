<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Seed the sidebar menu tree. Rows are upserted by slug so re-runs
     * stay idempotent and never disturb the roles' permission matrix.
     */
    public function run(): void
    {
        foreach (self::definitions() as $attributes) {
            $parentSlug = $attributes['parent'] ?? null;
            unset($attributes['parent']);

            $attributes['parent_id'] = $parentSlug === null
                ? null
                : Menu::query()->where('slug', $parentSlug)->value('id');

            Menu::query()->updateOrCreate(['slug' => $attributes['slug']], $attributes);
        }
    }

    /**
     * The module menus in sidebar order. A "parent" key links a child to
     * its section and is resolved to parent_id while seeding.
     *
     * @return list<array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'Users', 'slug' => 'users', 'icon' => 'users', 'route_name' => 'users.index', 'sort_order' => 1],
            ['name' => 'Roles', 'slug' => 'roles', 'icon' => 'settings', 'route_name' => 'roles.index', 'sort_order' => 2],
            ['name' => 'Menus', 'slug' => 'menus', 'icon' => 'menu', 'route_name' => 'menus.index', 'sort_order' => 3],
            ['name' => 'Audit Logs', 'slug' => 'audit-logs', 'icon' => 'alert-circle', 'route_name' => 'audit-logs.index', 'sort_order' => 4],
            ['name' => 'Entities', 'slug' => 'entities', 'icon' => 'building-2', 'route_name' => 'entities.index', 'sort_order' => 5],
            ['name' => 'Approval Matrix', 'slug' => 'approval-matrices', 'icon' => 'settings', 'route_name' => 'approval-matrices.index', 'sort_order' => 9],
            ['name' => 'Approvals', 'slug' => 'approvals', 'icon' => 'alert-circle', 'route_name' => 'approvals.index', 'sort_order' => 10],
            ['name' => 'Site Management', 'slug' => 'site-management', 'icon' => 'building', 'route_name' => null, 'sort_order' => 11],
            ['name' => 'Sites', 'slug' => 'sites', 'icon' => 'map-pin', 'route_name' => 'sites.index', 'sort_order' => 1, 'parent' => 'site-management'],
            ['name' => 'Product Management', 'slug' => 'product-management', 'icon' => 'package', 'route_name' => null, 'sort_order' => 6],
            ['name' => 'Products', 'slug' => 'products', 'icon' => 'package', 'route_name' => 'products.index', 'sort_order' => 1, 'parent' => 'product-management'],
            ['name' => 'Categories', 'slug' => 'categories', 'icon' => 'folder-tree', 'route_name' => 'categories.index', 'sort_order' => 2, 'parent' => 'product-management'],
            ['name' => 'Brands', 'slug' => 'brands', 'icon' => 'tag', 'route_name' => 'brands.index', 'sort_order' => 3, 'parent' => 'product-management'],
            ['name' => 'Units', 'slug' => 'units', 'icon' => 'ruler', 'route_name' => 'units.index', 'sort_order' => 4, 'parent' => 'product-management'],
            ['name' => 'HR Management', 'slug' => 'hr-management', 'icon' => 'briefcase', 'route_name' => null, 'sort_order' => 7],
            ['name' => 'Employees', 'slug' => 'employees', 'icon' => 'users', 'route_name' => 'employees.index', 'sort_order' => 1, 'parent' => 'hr-management'],
            ['name' => 'Divisions', 'slug' => 'divisions', 'icon' => 'building-2', 'route_name' => 'divisions.index', 'sort_order' => 2, 'parent' => 'hr-management'],
            ['name' => 'Departments', 'slug' => 'departments', 'icon' => 'building', 'route_name' => 'departments.index', 'sort_order' => 3, 'parent' => 'hr-management'],
            ['name' => 'Org Units', 'slug' => 'org-units', 'icon' => 'network', 'route_name' => 'org-units.index', 'sort_order' => 4, 'parent' => 'hr-management'],
            ['name' => 'Positions', 'slug' => 'positions', 'icon' => 'badge', 'route_name' => 'positions.index', 'sort_order' => 5, 'parent' => 'hr-management'],
            ['name' => 'Org Chart', 'slug' => 'org-chart', 'icon' => 'network', 'route_name' => 'org-chart.index', 'sort_order' => 6, 'parent' => 'hr-management'],
            ['name' => 'Development Programs', 'slug' => 'development-programs', 'icon' => 'graduation-cap', 'route_name' => 'development-programs.index', 'sort_order' => 7, 'parent' => 'hr-management'],
            ['name' => 'Benefits', 'slug' => 'benefits', 'icon' => 'package', 'route_name' => 'benefits.index', 'sort_order' => 8, 'parent' => 'hr-management'],
            ['name' => 'Benefit Enrollments', 'slug' => 'benefit-enrollments', 'icon' => 'users', 'route_name' => 'benefit-enrollments.index', 'sort_order' => 9, 'parent' => 'hr-management'],
            ['name' => 'Benefit Claims', 'slug' => 'benefit-claims', 'icon' => 'badge', 'route_name' => 'benefit-claims.index', 'sort_order' => 10, 'parent' => 'hr-management'],
            ['name' => 'HR Settings', 'slug' => 'hr-settings', 'icon' => 'settings', 'route_name' => null, 'sort_order' => 8],
            ['name' => 'Grades', 'slug' => 'grades', 'icon' => 'layers', 'route_name' => 'grades.index', 'sort_order' => 1, 'parent' => 'hr-settings'],
            ['name' => 'Employment Statuses', 'slug' => 'employment-statuses', 'icon' => 'toggle-right', 'route_name' => 'employment-statuses.index', 'sort_order' => 2, 'parent' => 'hr-settings'],
            ['name' => 'Religions', 'slug' => 'religions', 'icon' => 'book-open', 'route_name' => 'religions.index', 'sort_order' => 3, 'parent' => 'hr-settings'],
            ['name' => 'Education Levels', 'slug' => 'education-levels', 'icon' => 'graduation-cap', 'route_name' => 'education-levels.index', 'sort_order' => 4, 'parent' => 'hr-settings'],
            ['name' => 'Marital Statuses', 'slug' => 'marital-statuses', 'icon' => 'heart', 'route_name' => 'marital-statuses.index', 'sort_order' => 5, 'parent' => 'hr-settings'],
        ];
    }
}
