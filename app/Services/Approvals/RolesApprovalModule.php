<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RolesApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'roles';
    }

    public function label(): string
    {
        return __('Roles');
    }

    public function targetLabel(): string
    {
        return __('Role');
    }

    protected function modelClass(): string
    {
        return Role::class;
    }

    protected function fields(): array
    {
        return ['name', 'slug', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'name' => ['required', Rule::unique('roles', 'name')->ignore($target?->getKey())],
            'slug' => ['required', Rule::unique('roles', 'slug')->ignore($target?->getKey())],
            'permissions' => [
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_array($value)) {
                        $fail(__('The permission matrix payload is invalid.'));

                        return;
                    }

                    // Mirrors Role::permissionsFromInput(): every submitted
                    // menu id must still pass its active-menu filter.
                    $submitted = collect(array_keys($value))
                        ->map(fn (mixed $menuId): int => (int) $menuId)
                        ->sort()
                        ->values()
                        ->all();
                    $accepted = collect(array_keys(Role::permissionsFromInput($value)))
                        ->map(fn (mixed $menuId): int => (int) $menuId)
                        ->sort()
                        ->values()
                        ->all();

                    if ($submitted !== $accepted) {
                        $fail(__('The permission matrix contains menus that are no longer active.'));
                    }
                },
            ],
        ];
    }

    protected function snapshot(Model $target): array
    {
        $permissions = $target instanceof Role ? $target->permissionsSnapshot() : [];

        $normalized = collect($permissions)
            ->map(fn (array $flags): array => [
                'can_view' => (bool) ($flags['view'] ?? false),
                'can_create' => (bool) ($flags['create'] ?? false),
                'can_update' => (bool) ($flags['update'] ?? false),
                'can_delete' => (bool) ($flags['delete'] ?? false),
            ])
            ->all();

        ksort($normalized);

        return [...parent::snapshot($target), 'permissions' => $normalized];
    }

    public function apply(ApprovalRequest $request, ?Model $target): ?Model
    {
        if ($request->action === ApprovalRequest::ACTION_CREATE) {
            return app(Role::class)->withPermissions(...$this->permissionPayload($request));
        }

        if ($request->action === ApprovalRequest::ACTION_UPDATE && $target instanceof Role) {
            return $target->withPermissions(...$this->permissionPayload($request));
        }

        return parent::apply($request, $target);
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof Role && $target->users()->exists()) {
            throw ValidationException::withMessages(['role' => __('This role is still assigned to users. Reassign them first.')]);
        }
    }

    protected function assertApplicable(?ApprovalRequest $request, ?Model $target, string $action, array $payload): void
    {
        if ($request === null || ! $target instanceof Role) {
            return;
        }

        if ($action !== ApprovalRequest::ACTION_UPDATE) {
            return;
        }

        $maker = User::query()->find($request->maker_user_id);

        if ($maker === null || $maker->role_id !== $target->getKey()) {
            return;
        }

        if ($target->removesSelfAccess($maker, $payload['permissions'] ?? [])) {
            throw ValidationException::withMessages(['permissions' => __('You cannot remove your own administrative access.')]);
        }
    }

    /**
     * Split a payload into role attributes and its permission matrix.
     *
     * @return array{0: array<string, mixed>, 1: array<int, array<string, bool>>|null}
     */
    protected function permissionPayload(ApprovalRequest $request): array
    {
        $payload = $request->proposed_payload ?? [];
        $permissions = $payload['permissions'] ?? null;

        return [
            Arr::except($payload, ['permissions']),
            is_array($permissions) ? $permissions : null,
        ];
    }
}
