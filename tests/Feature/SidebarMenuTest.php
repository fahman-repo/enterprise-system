<?php

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

beforeEach(function () {
    $this->usersMenu = Menu::factory()->create(['slug' => 'users', 'name' => 'Users', 'sort_order' => 1, 'route_name' => 'users.index']);
    $this->rolesMenu = Menu::factory()->create(['slug' => 'roles', 'name' => 'Roles', 'sort_order' => 2, 'route_name' => 'roles.index']);
});

function sidebarUserWithViewOn(Menu $menu): User
{
    $role = Role::factory()->create();
    $role->menus()->syncWithoutDetaching([$menu->id => ['can_view' => true]]);
    app(PermissionService::class)->flush();

    return User::factory()->create(['role_id' => $role->id]);
}

test('sidebar shows menus the role can view', function () {
    $user = sidebarUserWithViewOn($this->usersMenu);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Users')
        ->assertDontSee('Roles</', false);
});

test('sidebar omits menus the role cannot view', function () {
    $user = sidebarUserWithViewOn($this->usersMenu);

    $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

    expect(str_contains($html, 'Users'))->toBeTrue()
        ->and(str_contains($html, '>Roles<'))->toBeFalse();
});

test('sidebar shows parent group when only a child is viewable', function () {
    $group = Menu::factory()->create(['slug' => 'system', 'name' => 'System', 'sort_order' => 0]);
    $child = Menu::factory()->create(['slug' => 'menus', 'name' => 'Menus', 'parent_id' => $group->id, 'sort_order' => 1]);

    $role = Role::factory()->create();
    $role->menus()->syncWithoutDetaching([$child->id => ['can_view' => true]]);
    app(PermissionService::class)->flush();

    $user = User::factory()->create(['role_id' => $role->id]);

    $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

    expect(str_contains($html, 'System'))->toBeTrue()
        ->and(str_contains($html, 'Menus'))->toBeTrue();
});

test('sidebar hides parent group with no viewable children and no view', function () {
    $group = Menu::factory()->create(['slug' => 'system', 'name' => 'System']);
    Menu::factory()->create(['slug' => 'menus', 'name' => 'Menus', 'parent_id' => $group->id]);

    $user = sidebarUserWithViewOn($this->usersMenu);

    $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

    expect(str_contains($html, 'System'))->toBeFalse()
        ->and(str_contains($html, '>Menus<'))->toBeFalse();
});

test('user without role sees only the dashboard link', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

    expect(str_contains($html, 'Dashboard'))->toBeTrue()
        ->and(str_contains($html, '>Users<'))->toBeFalse()
        ->and(str_contains($html, '>Roles<'))->toBeFalse();
});

test('menu links resolve through named routes', function () {
    $user = sidebarUserWithViewOn($this->usersMenu);

    $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

    expect(str_contains($html, 'href="'.route('users.index').'"'))->toBeTrue();
});
