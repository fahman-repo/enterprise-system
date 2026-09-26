<?php

use App\Models\Menu;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $parentId = Menu::query()->where('slug', 'hr-management')->value('id');

        $menu = Menu::query()->updateOrCreate(
            ['slug' => 'development-programs'],
            [
                'name' => 'Development Programs',
                'icon' => 'graduation-cap',
                'route_name' => 'development-programs.index',
                'sort_order' => 7,
                'parent_id' => $parentId,
            ],
        );

        $admin = Role::query()->where('slug', 'admin')->first();

        if ($admin !== null) {
            $admin->menus()->syncWithoutDetaching([
                $menu->id => [
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                ],
            ]);
        }

        app(PermissionService::class)->flush();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::query()->where('slug', 'development-programs')->delete();
    }
};
