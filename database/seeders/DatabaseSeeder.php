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
     * admin role (full permission matrix) and the first admin user.
     */
    public function run(): void
    {
        $menus = collect([
            ['name' => 'Users', 'slug' => 'users', 'icon' => 'users', 'route_name' => 'users.index', 'sort_order' => 1],
            ['name' => 'Roles', 'slug' => 'roles', 'icon' => 'settings', 'route_name' => 'roles.index', 'sort_order' => 2],
            ['name' => 'Menus', 'slug' => 'menus', 'icon' => 'menu', 'route_name' => 'menus.index', 'sort_order' => 3],
        ])->map(fn (array $attributes) => Menu::query()->updateOrCreate(
            ['slug' => $attributes['slug']],
            $attributes,
        ));

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
    }
}
