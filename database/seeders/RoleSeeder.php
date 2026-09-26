<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class RoleSeeder extends Seeder
{
    /**
     * Seed the demo roles and their permission matrices. Roles are
     * upserted by slug and their matrices synced through the audited
     * model path, so re-runs stay idempotent.
     */
    public function run(): void
    {
        $menus = Menu::query()->get(['id', 'slug'])->keyBy('slug');

        foreach (self::definitions() as $slug => $definition) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $definition['name'], 'is_active' => true],
            );

            $role->withPermissions([], $this->permissions($menus, $definition['menus']));
        }

        app(PermissionService::class)->flush();
    }

    /**
     * Demo roles keyed by slug. Each role lists the menu slugs it may act
     * on with the allowed actions; roles deliberately differ per division
     * so every login shows a different sidebar and inbox. The "admin"
     * role is owned by the DatabaseSeeder and is never touched here.
     *
     * @return array<string, array{name: string, menus: array<string, string>}>
     */
    public static function definitions(): array
    {
        return [
            'hr-manager' => [
                'name' => 'HR Manager',
                'menus' => [
                    'hr-management' => 'view',
                    'employees' => 'view,create,update,delete',
                    'org-chart' => 'view',
                    'divisions' => 'view,create,update',
                    'departments' => 'view,create,update',
                    'org-units' => 'view,create,update',
                    'positions' => 'view,create,update',
                    'development-programs' => 'view,create,update,delete',
                    'benefits' => 'view,create,update,delete',
                    'benefit-enrollments' => 'view,create,update,delete',
                    'benefit-claims' => 'view,create,update,delete',
                    'hr-settings' => 'view',
                    'grades' => 'view,create,update',
                    'employment-statuses' => 'view,create,update',
                    'religions' => 'view,create,update',
                    'education-levels' => 'view,create,update',
                    'marital-statuses' => 'view,create,update',
                    'approvals' => 'view,update',
                    'approval-matrices' => 'view',
                    'audit-logs' => 'view',
                    'site-management' => 'view',
                    'sites' => 'view',
                    'product-management' => 'view',
                    'products' => 'view',
                    'entities' => 'view',
                    'users' => 'view',
                ],
            ],
            'hr-staff' => [
                'name' => 'HR Staff',
                'menus' => [
                    'hr-management' => 'view',
                    'employees' => 'view,create,update',
                    'org-chart' => 'view',
                    'divisions' => 'view',
                    'departments' => 'view',
                    'org-units' => 'view',
                    'positions' => 'view',
                    'development-programs' => 'view,create,update',
                    'benefits' => 'view',
                    'benefit-enrollments' => 'view,create,update',
                    'benefit-claims' => 'view,create,update',
                    'hr-settings' => 'view',
                    'grades' => 'view',
                    'employment-statuses' => 'view',
                    'religions' => 'view',
                    'education-levels' => 'view',
                    'marital-statuses' => 'view',
                    'approvals' => 'view',
                    'audit-logs' => 'view',
                    'site-management' => 'view',
                    'sites' => 'view',
                    'product-management' => 'view',
                    'products' => 'view',
                    'entities' => 'view,create',
                ],
            ],
            'finance-approver' => [
                'name' => 'Finance Approver',
                'menus' => [
                    'hr-management' => 'view',
                    'employees' => 'view',
                    'org-chart' => 'view',
                    'benefits' => 'view',
                    'benefit-enrollments' => 'view,update',
                    'benefit-claims' => 'view,update',
                    'development-programs' => 'view',
                    'hr-settings' => 'view',
                    'approvals' => 'view,update',
                    'approval-matrices' => 'view',
                    'audit-logs' => 'view',
                    'entities' => 'view,create,update',
                    'site-management' => 'view',
                    'sites' => 'view',
                    'product-management' => 'view',
                    'products' => 'view',
                ],
            ],
            'ops-manager' => [
                'name' => 'Operations Manager',
                'menus' => [
                    'hr-management' => 'view',
                    'employees' => 'view',
                    'org-chart' => 'view',
                    'divisions' => 'view',
                    'departments' => 'view',
                    'org-units' => 'view',
                    'development-programs' => 'view',
                    'hr-settings' => 'view',
                    'approvals' => 'view,update',
                    'audit-logs' => 'view',
                    'site-management' => 'view',
                    'sites' => 'view,create,update,delete',
                    'product-management' => 'view',
                    'products' => 'view,create,update,delete',
                    'categories' => 'view,create,update,delete',
                    'brands' => 'view,create,update,delete',
                    'units' => 'view,create,update,delete',
                    'entities' => 'view,create,update',
                ],
            ],
            'commercial-staff' => [
                'name' => 'Commercial Staff',
                'menus' => [
                    'hr-management' => 'view',
                    'employees' => 'view',
                    'org-chart' => 'view',
                    'development-programs' => 'view',
                    'benefit-enrollments' => 'view',
                    'benefit-claims' => 'view,create,update',
                    'site-management' => 'view',
                    'sites' => 'view',
                    'product-management' => 'view',
                    'products' => 'view',
                    'entities' => 'view,create',
                ],
            ],
            'it-support' => [
                'name' => 'IT Support',
                'menus' => [
                    'users' => 'view,create,update',
                    'roles' => 'view',
                    'menus' => 'view',
                    'audit-logs' => 'view',
                    'hr-management' => 'view',
                    'employees' => 'view',
                    'org-chart' => 'view',
                    'development-programs' => 'view,create',
                    'approvals' => 'view',
                    'site-management' => 'view',
                    'sites' => 'view',
                    'product-management' => 'view',
                    'products' => 'view',
                ],
            ],
            'branch-manager' => [
                'name' => 'Branch Manager',
                'menus' => [
                    'hr-management' => 'view',
                    'employees' => 'view',
                    'org-chart' => 'view',
                    'development-programs' => 'view',
                    'benefit-enrollments' => 'view',
                    'benefit-claims' => 'view,update',
                    'hr-settings' => 'view',
                    'approvals' => 'view,update',
                    'site-management' => 'view',
                    'sites' => 'view',
                    'product-management' => 'view',
                    'products' => 'view',
                ],
            ],
            'employee-self' => [
                'name' => 'Employee Self Service',
                'menus' => [
                    'hr-management' => 'view',
                    'employees' => 'view',
                    'org-chart' => 'view',
                    'development-programs' => 'view',
                    'benefit-enrollments' => 'view',
                    'benefit-claims' => 'view,create',
                ],
            ],
        ];
    }

    /**
     * Expand the compact action strings into the pivot payload keyed by
     * menu id, so unknown slugs are ignored instead of breaking the run.
     *
     * @param  Collection<int, Menu>  $menus
     * @param  array<string, string>  $matrix
     * @return array<int, array<string, bool>>
     */
    protected function permissions(Collection $menus, array $matrix): array
    {
        $permissions = [];

        foreach ($matrix as $slug => $actions) {
            $menuId = $menus->get($slug)?->id;

            if ($menuId === null) {
                continue;
            }

            $allowed = explode(',', $actions);

            $permissions[$menuId] = [
                'can_view' => in_array('view', $allowed, true),
                'can_create' => in_array('create', $allowed, true),
                'can_update' => in_array('update', $allowed, true),
                'can_delete' => in_array('delete', $allowed, true),
            ];
        }

        return $permissions;
    }
}
