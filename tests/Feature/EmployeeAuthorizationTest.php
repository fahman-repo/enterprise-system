<?php

use App\Models\Employee;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Collection;

function grantEmployeePermissions(Role $role, Menu $menu, array $actions): void
{
    $flags = [
        'can_view' => false,
        'can_create' => false,
        'can_update' => false,
        'can_delete' => false,
    ];

    foreach ($actions as $action) {
        $flags['can_'.$action] = true;
    }

    $role->menus()->syncWithoutDetaching([$menu->id => $flags]);
    app(PermissionService::class)->flush();
}

/**
 * @return Collection<int, Menu>
 */
function hrModuleMenus(): Collection
{
    return collect([
        ['slug' => 'employees', 'name' => 'Employees'],
        ['slug' => 'divisions', 'name' => 'Divisions'],
        ['slug' => 'departments', 'name' => 'Departments'],
        ['slug' => 'org-units', 'name' => 'Org Units'],
        ['slug' => 'positions', 'name' => 'Positions'],
    ])->map(fn (array $menu) => Menu::factory()->create([
        'slug' => $menu['slug'],
        'name' => $menu['name'],
        'route_name' => $menu['slug'].'.index',
    ]));
}

test('guests are redirected to login from employee routes', function () {
    $employee = Employee::factory()->create();

    $this->get(route('employees.index'))->assertRedirect(route('login'));
    $this->get(route('employees.create'))->assertRedirect(route('login'));
    $this->get(route('employees.show', $employee))->assertRedirect(route('login'));
    $this->get(route('employees.edit', $employee))->assertRedirect(route('login'));
});

test('authenticated user without flags gets 403 on hr module routes', function () {
    $menus = hrModuleMenus();
    $user = User::factory()->create();

    $this->actingAs($user);

    foreach ($menus as $menu) {
        $this->get(route($menu->slug.'.index'))->assertForbidden();
    }

    $this->get(route('employees.create'))->assertForbidden();
});

test('user with view only can index and show employees but not mutate them', function () {
    $menus = hrModuleMenus();
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);
    grantEmployeePermissions($role, $menus->firstWhere('slug', 'employees'), ['view']);

    $this->actingAs($user);

    $this->get(route('employees.index'))->assertOk();
    $this->get(route('employees.show', Employee::factory()->create()))->assertOk();
    $this->get(route('employees.create'))->assertForbidden();
    $this->post(route('employees.store'), [])->assertForbidden();
    $this->get(route('employees.edit', Employee::factory()->create()))->assertForbidden();
    $this->delete(route('employees.destroy', Employee::factory()->create()))->assertForbidden();
});

test('gate exposes the employee slug action abilities', function () {
    $menus = hrModuleMenus();
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);
    grantEmployeePermissions($role, $menus->firstWhere('slug', 'employees'), ['view', 'create']);

    expect($user->can('employees.view'))->toBeTrue()
        ->and($user->can('employees.create'))->toBeTrue()
        ->and($user->can('employees.update'))->toBeFalse()
        ->and($user->can('divisions.view'))->toBeFalse();
});

test('sidebar groups hr modules under the hr management heading', function () {
    $menus = hrModuleMenus();
    $parent = Menu::factory()->create(['slug' => 'hr-management', 'name' => 'HR Management', 'sort_order' => 6]);

    $menus->each(fn (Menu $menu) => $menu->update(['parent_id' => $parent->id]));

    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);

    foreach ($menus as $menu) {
        grantEmployeePermissions($role, $menu, ['view']);
    }

    $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

    expect($html)->toContain('HR Management')
        ->and($html)->toContain(route('employees.index'))
        ->and($html)->toContain(route('divisions.index'))
        ->and($html)->toContain(route('departments.index'))
        ->and($html)->toContain(route('org-units.index'))
        ->and($html)->toContain(route('positions.index'));
});
