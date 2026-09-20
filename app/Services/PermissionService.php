<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Menu permission flags for a role, keyed by menu slug.
     *
     * @return array<string, array<string, bool>>
     */
    public function permissionsForRole(int $roleId): array
    {
        return Cache::rememberForever("role.{$roleId}.permissions", function () use ($roleId) {
            $role = Role::query()->with('menus')->find($roleId);

            if (! $role) {
                return [];
            }

            return $role->menus->mapWithKeys(fn (Menu $menu) => [
                $menu->slug => [
                    'view' => (bool) $menu->pivot->can_view,
                    'create' => (bool) $menu->pivot->can_create,
                    'update' => (bool) $menu->pivot->can_update,
                    'delete' => (bool) $menu->pivot->can_delete,
                ],
            ])->all();
        });
    }

    /**
     * Whether the role grants the given action on the given menu slug.
     */
    public function can(int $roleId, string $menuSlug, string $action): bool
    {
        return (bool) ($this->permissionsForRole($roleId)[$menuSlug][$action] ?? false);
    }

    /**
     * Sidebar menu tree the user's role may see.
     */
    public function sidebarForUser(?User $user): Collection
    {
        if (! $user?->role_id) {
            return collect();
        }

        return Cache::rememberForever("role.{$user->role_id}.sidebar", fn () => $this->buildSidebar($user->role_id));
    }

    protected function buildSidebar(int $roleId): Collection
    {
        $permissions = $this->permissionsForRole($roleId);

        $menus = Menu::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $viewableIds = $menus
            ->filter(fn (Menu $menu) => $permissions[$menu->slug]['view'] ?? false)
            ->pluck('id');

        $childrenByParent = $menus->whereNotNull('parent_id')->groupBy('parent_id');

        $tree = collect();

        foreach ($menus->whereNull('parent_id')->values() as $parent) {
            $children = $childrenByParent
                ->get($parent->id, collect())
                ->filter(fn (Menu $child) => $viewableIds->contains($child->id))
                ->values();

            if ($children->isEmpty() && ! $viewableIds->contains($parent->id)) {
                continue;
            }

            $parent->setRelation('children', $children);
            $tree->push($parent);
        }

        return $tree;
    }

    /**
     * Forget every cached permission map and sidebar tree.
     */
    public function flush(): void
    {
        foreach (Role::query()->pluck('id') as $roleId) {
            Cache::forget("role.{$roleId}.permissions");
            Cache::forget("role.{$roleId}.sidebar");
        }
    }
}
