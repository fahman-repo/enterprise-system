<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RolesController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of roles.
     */
    public function index(Request $request): View
    {
        $query = Role::query()->withCount('users');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'roles.name',
            'roles.slug',
        ]);

        $this->applyTableStatusFilter($query, $request, 'roles.is_active');

        [$sort, $direction] = $this->applyTableSort($query, [
            'name' => 'roles.name',
            'slug' => 'roles.slug',
            'users_count' => 'users_count',
            'is_active' => 'roles.is_active',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $roles = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('roles.index', ['roles' => $roles, 'sort' => $sort, 'direction' => $direction]);
    }

    /**
     * Show the form for creating a role.
     */
    public function create(): View
    {
        return view('roles.create', [
            'menus' => $this->activeMenus(),
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:roles,slug'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        app(Role::class)->withPermissions($data, $this->permissionsFrom($request));

        return redirect()->route('roles.index')->with('status', __('Role created.'));
    }

    /**
     * Show the form for editing a role, including its permission matrix.
     */
    public function edit(Role $role): View
    {
        $role->load('menus');

        return view('roles.edit', [
            'role' => $role,
            'menus' => $this->activeMenus(),
        ]);
    }

    /**
     * Update the role and sync its permission matrix.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('roles', 'slug')->ignore($role->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $permissions = $this->permissionsFrom($request);

        if ($request->user()?->role_id === $role->id && $this->removesSelfAccess($request->user(), $permissions)) {
            return back()
                ->withErrors(['permissions' => __('You cannot remove your own administrative access.')])
                ->withInput();
        }

        $role->withPermissions($data, $permissions);

        return redirect()->route('roles.index')->with('status', __('Role updated.'));
    }

    /**
     * Remove the role if no users are still assigned to it.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            return back()->withErrors(['role' => __('This role is still assigned to users. Reassign them first.')]);
        }

        $role->delete();

        return redirect()->route('roles.index')->with('status', __('Role deleted.'));
    }

    /**
     * Active menus available for the permission matrix.
     */
    protected function activeMenus()
    {
        return Menu::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Build the pivot payload from the permission matrix input,
     * ignoring unknown menu ids and rows with no flags.
     *
     * @return array<int, array<string, bool>>
     */
    protected function permissionsFrom(Request $request): array
    {
        return collect($request->input('permissions', []))
            ->only(Menu::query()->where('is_active', true)->pluck('id')->all())
            ->map(fn (mixed $flags) => [
                'can_view' => (bool) ($flags['can_view'] ?? false),
                'can_create' => (bool) ($flags['can_create'] ?? false),
                'can_update' => (bool) ($flags['can_update'] ?? false),
                'can_delete' => (bool) ($flags['can_delete'] ?? false),
            ])
            ->filter(fn (array $flags) => $flags['can_view'] || $flags['can_create'] || $flags['can_update'] || $flags['can_delete'])
            ->all();
    }

    /**
     * Whether saving the matrix would strip the actor's own
     * roles.update or menus.update access.
     */
    protected function removesSelfAccess(?object $actor, array $permissions): bool
    {
        $loses = fn (string $slug): bool => $actor->canAccess($slug, 'update')
            && ! ($permissions[$slug]['update'] ?? false);

        return $loses('roles') || $loses('menus');
    }
}
