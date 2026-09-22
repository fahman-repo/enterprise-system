<?php

use App\Models\Entity;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

function grantEntityPermissions(Role $role, Menu $menu, array $actions): void
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

function entityMenu(): Menu
{
    return Menu::factory()->create([
        'slug' => 'entities',
        'name' => 'Entities',
        'route_name' => 'entities.index',
    ]);
}

test('guests are redirected to login from entity routes', function () {
    $entity = Entity::factory()->create();

    $this->get(route('entities.index'))->assertRedirect(route('login'));
    $this->get(route('entities.create'))->assertRedirect(route('login'));
    $this->get(route('entities.show', $entity))->assertRedirect(route('login'));
    $this->get(route('entities.edit', $entity))->assertRedirect(route('login'));
});

test('authenticated user without flags gets 403 on entity routes', function () {
    entityMenu();
    $user = User::factory()->create();

    $entity = Entity::factory()->create();

    $this->actingAs($user);

    $this->get(route('entities.index'))->assertForbidden();
    $this->get(route('entities.create'))->assertForbidden();
    $this->get(route('entities.show', $entity))->assertForbidden();
    $this->get(route('entities.edit', $entity))->assertForbidden();
});

test('user with view only can index and show but not mutate entities', function () {
    $menu = entityMenu();
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);
    grantEntityPermissions($role, $menu, ['view']);

    $this->actingAs($user);

    $this->get(route('entities.index'))->assertOk();
    $this->get(route('entities.show', Entity::factory()->create()))->assertOk();
    $this->get(route('entities.create'))->assertForbidden();
    $this->post(route('entities.store'), [])->assertForbidden();
    $this->put(route('entities.update', Entity::factory()->create()))->assertForbidden();
    $this->delete(route('entities.destroy', Entity::factory()->create()))->assertForbidden();
});

test('gate exposes entities slug action abilities', function () {
    $menu = entityMenu();
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);
    grantEntityPermissions($role, $menu, ['view', 'create']);

    expect($user->can('entities.view'))->toBeTrue()
        ->and($user->can('entities.create'))->toBeTrue()
        ->and($user->can('entities.update'))->toBeFalse()
        ->and($user->can('entities.delete'))->toBeFalse();
});

test('sidebar lists entities as a top-level entry', function () {
    $menu = entityMenu();
    $menu->update(['sort_order' => 5, 'parent_id' => null]);

    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);
    grantEntityPermissions($role, $menu, ['view']);

    $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

    expect($html)->toContain('Entities')
        ->and($html)->toContain(route('entities.index'));
});
