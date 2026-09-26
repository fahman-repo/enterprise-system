<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('users')) {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $workflow->submit($request->user(), 'users', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()->route('users.index')->with('status', __('User change request submitted for approval.'));
        }

        User::create($data);

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
    public function update(Request $request, User $user, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && $request->user()->locksOut($data['role_id'] ?? null)) {
            return back()
                ->withErrors(['role_id' => __('You cannot remove your own administrative access.')])
                ->withInput();
        }

        if ($workflow->isApprovalActive('users')) {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $workflow->submit($request->user(), 'users', ApprovalRequest::ACTION_UPDATE, $user->id, $data);

            return redirect()->route('users.index')->with('status', __('User change request submitted for approval.'));
        }

        $user->update($data);

        return redirect()->route('users.index')->with('status', __('User updated.'));
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => __('You cannot delete your own account.')]);
        }

        if ($user->isLastAdministrator()) {
            return back()->withErrors(['user' => __('You cannot delete the last user with administrative access.')]);
        }

        if ($workflow->isApprovalActive('users')) {
            $workflow->submit($request->user(), 'users', ApprovalRequest::ACTION_DELETE, $user->id, []);

            return redirect()->route('users.index')->with('status', __('User change request submitted for approval.'));
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
}
