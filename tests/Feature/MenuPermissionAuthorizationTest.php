<?php

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Cache;

function grantPermissions(Role $role, Menu $menu, array $actions): void
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

beforeEach(function () {
    $this->usersMenu = Menu::factory()->create(['slug' => 'users', 'name' => 'Users']);
    $this->rolesMenu = Menu::factory()->create(['slug' => 'roles', 'name' => 'Roles']);
});

test('guests are redirected to login from module routes', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
    $this->get(route('roles.index'))->assertRedirect(route('login'));
    $this->get(route('menus.index'))->assertRedirect(route('login'));
});

test('authenticated user without flags gets 403', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($user);

    $this->get(route('users.index'))->assertForbidden();
    $this->get(route('roles.index'))->assertForbidden();
});

test('user with no role gets 403 everywhere', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
});

test('user with view only can index but not mutate', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);
    grantPermissions($role, $this->usersMenu, ['view']);

    $this->actingAs($user);

    $this->get(route('users.index'))->assertOk();
    $this->get(route('users.create'))->assertForbidden();

    $target = User::factory()->create();

    $this->post(route('users.store'), [
        'name' => 'New',
        'email' => 'new@example.com',
        'password' => 'password123',
    ])->assertForbidden();

    $this->delete(route('users.destroy', $target))->assertForbidden();
});

test('permission change takes effect without manual cache clear', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($user);

    $this->get(route('users.index'))->assertForbidden();

    grantPermissions($role, $this->usersMenu, ['view']);

    expect(Cache::get("role.{$role->id}.permissions"))->toBeNull();
    $this->get(route('users.index'))->assertOk();
});

test('gate exposes slug action abilities for blade directives', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);
    grantPermissions($role, $this->usersMenu, ['view', 'create']);

    expect($user->can('users.view'))->toBeTrue()
        ->and($user->can('users.create'))->toBeTrue()
        ->and($user->can('users.update'))->toBeFalse()
        ->and($user->can('roles.view'))->toBeFalse();
});

test('sidebar shows only dashboard for user with no viewable menus', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('href="#"', false);
});

test('cannot delete the last user with administrative access', function () {
    $role = Role::factory()->create();
    grantPermissions($role, $this->rolesMenu, ['view', 'update']);
    grantPermissions($role, $this->usersMenu, ['view', 'delete']);

    $admin = User::factory()->create(['role_id' => $role->id]);
    $other = User::factory()->create();

    $this->actingAs($admin)->delete(route('users.destroy', $other))
        ->assertRedirect();

    $this->assertModelMissing($other);

    $this->delete(route('users.destroy', $admin))
        ->assertRedirect()
        ->assertSessionHasErrors('user');

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('admin user is deletable once another administrator exists', function () {
    $role = Role::factory()->create();
    grantPermissions($role, $this->rolesMenu, ['view', 'update']);
    grantPermissions($role, $this->usersMenu, ['view', 'delete']);

    $first = User::factory()->create(['role_id' => $role->id]);
    $second = User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($first)->delete(route('users.destroy', $second))
        ->assertRedirect(route('users.index'));

    $this->assertModelMissing($second);
});

test('cannot change own role in a way that removes management access', function () {
    $role = Role::factory()->create();
    grantPermissions($role, $this->rolesMenu, ['view', 'update']);
    grantPermissions($role, $this->usersMenu, ['view', 'update']);
    $noAccessRole = Role::factory()->create();

    $admin = User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($admin)->put(route('users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'role_id' => $noAccessRole->id,
    ])->assertSessionHasErrors('role_id');

    $this->assertDatabaseHas('users', ['id' => $admin->id, 'role_id' => $role->id]);
});
