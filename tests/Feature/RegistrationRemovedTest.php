<?php

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

beforeEach(function () {
    $role = Role::factory()->create();
    $menus = collect([
        Menu::factory()->create(['slug' => 'users']),
        Menu::factory()->create(['slug' => 'roles']),
        Menu::factory()->create(['slug' => 'menus']),
    ]);

    $role->menus()->sync($menus->mapWithKeys(fn (Menu $menu) => [
        $menu->id => ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true],
    ]));

    app(PermissionService::class)->flush();

    $this->admin = User::factory()->create(['role_id' => $role->id]);
});

test('registration routes are gone', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});

test('seeder creates admin role with full matrix and first admin user', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'admin@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->role->slug)->toBe('admin')
        ->and($admin->canAccess('users', 'view'))->toBeTrue()
        ->and($admin->canAccess('roles', 'delete'))->toBeTrue()
        ->and($admin->canAccess('menus', 'update'))->toBeTrue();
});

test('login and logout still work', function () {
    $this->get(route('login'))->assertOk();

    $response = $this->post(route('login'), [
        'email' => $this->admin->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($this->admin);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});
