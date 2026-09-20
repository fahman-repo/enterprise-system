<?php

use App\Models\Menu;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Collection;

function grantProductPermissions(Role $role, Menu $menu, array $actions): void
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
function productModuleMenus(): Collection
{
    return collect(['products', 'categories', 'brands', 'units'])
        ->map(fn (string $slug) => Menu::factory()->create([
            'slug' => $slug,
            'name' => ucfirst($slug),
            'route_name' => $slug.'.index',
        ]));
}

test('guests are redirected to login from product module routes', function () {
    $this->get(route('products.index'))->assertRedirect(route('login'));
    $this->get(route('categories.index'))->assertRedirect(route('login'));
    $this->get(route('brands.index'))->assertRedirect(route('login'));
    $this->get(route('units.index'))->assertRedirect(route('login'));
});

test('authenticated user without flags gets 403 on product modules', function () {
    $menus = productModuleMenus();
    $user = User::factory()->create();

    $this->actingAs($user);

    foreach ($menus as $menu) {
        $this->get(route($menu->slug.'.index'))->assertForbidden();
    }
});

test('user with view only can index but not mutate products', function () {
    $menus = productModuleMenus();
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);
    grantProductPermissions($role, $menus->firstWhere('slug', 'products'), ['view']);

    $this->actingAs($user);

    $this->get(route('products.index'))->assertOk();
    $this->get(route('products.create'))->assertForbidden();
    $this->post(route('products.store'), [])->assertForbidden();
    $this->get(route('products.edit', Product::factory()->create()))->assertForbidden();
    $this->delete(route('products.destroy', Product::factory()->create()))->assertForbidden();
});

test('gate exposes product slug action abilities', function () {
    $menus = productModuleMenus();
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);
    grantProductPermissions($role, $menus->firstWhere('slug', 'products'), ['view', 'create']);

    expect($user->can('products.view'))->toBeTrue()
        ->and($user->can('products.create'))->toBeTrue()
        ->and($user->can('products.update'))->toBeFalse()
        ->and($user->can('categories.view'))->toBeFalse();
});

test('sidebar groups product modules under the product management heading', function () {
    $menus = productModuleMenus();
    $parent = Menu::factory()->create(['slug' => 'product-management', 'name' => 'Product Management', 'sort_order' => 5]);

    $menus->each(fn (Menu $menu) => $menu->update(['parent_id' => $parent->id]));

    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);

    foreach ($menus as $menu) {
        grantProductPermissions($role, $menu, ['view']);
    }

    $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

    expect($html)->toContain('Product Management')
        ->and($html)->toContain(route('products.index'))
        ->and($html)->toContain(route('categories.index'))
        ->and($html)->toContain(route('brands.index'))
        ->and($html)->toContain(route('units.index'));
});
