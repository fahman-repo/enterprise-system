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
     * admin role (full permission matrix), the first admin user,
     * the reference data and catalogue, and — outside production —
     * the demo company used to simulate every module's workflow.
     */
    public function run(): void
    {
        $this->call(MenuSeeder::class);

        $menus = Menu::query()->get();

        $admin = Role::query()->updateOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'is_active' => true],
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
                'is_active' => true,
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
            BenefitSeeder::class,
            SiteSeeder::class,
            ReligionSeeder::class,
            EducationLevelSeeder::class,
            MaritalStatusSeeder::class,
            EmployeeSeeder::class,
            EntitySeeder::class,
        ]);

        if (app()->environment('local') || env('DEMO_SEED', false)) {
            $this->call(DemoScenarioSeeder::class);
        }
    }
}
