<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsersController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of users.
     */
    public function index(Request $request): View
    {
        $query = User::query()
            ->select('users.*')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->with('role');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'users.name',
            'users.email',
            'roles.name',
        ]);

        $this->applyTableStatusFilter($query, $request, 'users.is_active');

        [$sort, $direction] = $this->applyTableSort($query, [
            'name' => 'users.name',
            'email' => 'users.email',
            'role' => 'roles.name',
            'is_active' => 'users.is_active',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $users = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('users.index', ['users' => $users, 'sort' => $sort, 'direction' => $direction]);
    }

    /**
     * Show the form for creating a user.
     */
    public function create(): View
    {
        return view('users.create', ['roles' => Role::query()->orderBy('name')->get()]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        User::create($this->validated($request));

        return redirect()->route('users.index')->with('status', __('User created.'));
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && $this->locksOut($request->user(), $data['role_id'] ?? null)) {
            return back()
                ->withErrors(['role_id' => __('You cannot remove your own administrative access.')])
                ->withInput();
        }

        $user->update($data);

        return redirect()->route('users.index')->with('status', __('User updated.'));
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => __('You cannot delete your own account.')]);
        }

        if ($this->isLastAdministrator($user)) {
            return back()->withErrors(['user' => __('You cannot delete the last user with administrative access.')]);
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', __('User deleted.'));
    }

    /**
     * Validate and normalize the user payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => $user === null
                ? ['required', 'string', 'min:8']
                : ['nullable', 'string', 'min:8'],
            'role_id' => ['nullable', Rule::exists('roles', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /**
     * Whether reassigning the actor's own role would strip the ability
     * to manage roles or menus, locking the actor out.
     */
    protected function locksOut(User $actor, ?int $newRoleId): bool
    {
        $currentlyManages = $actor->canAccess('roles', 'update') || $actor->canAccess('menus', 'update');

        if (! $currentlyManages) {
            return false;
        }

        if (! $newRoleId) {
            return true;
        }

        $permissions = app(PermissionService::class)->permissionsForRole($newRoleId);

        return ! (($permissions['roles']['update'] ?? false) || ($permissions['menus']['update'] ?? false));
    }

    /**
     * Whether deleting the user would leave no one able to manage
     * the roles or menus modules.
     */
    protected function isLastAdministrator(User $user): bool
    {
        if (! $user->role_id) {
            return false;
        }

        $service = app(PermissionService::class);

        $adminRoleIds = Role::query()->pluck('id')->filter(fn (int $roleId) => $service->can($roleId, 'roles', 'view')
            || $service->can($roleId, 'roles', 'update')
            || $service->can($roleId, 'menus', 'view')
            || $service->can($roleId, 'menus', 'update'));

        if (! $adminRoleIds->contains($user->role_id)) {
            return false;
        }

        return User::query()
            ->where('id', '!=', $user->id)
            ->whereIn('role_id', $adminRoleIds)
            ->doesntExist();
    }
}
