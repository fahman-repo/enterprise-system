<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UsersApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'users';
    }

    public function label(): string
    {
        return __('Users');
    }

    public function targetLabel(): string
    {
        return __('User');
    }

    protected function modelClass(): string
    {
        return User::class;
    }

    protected function fields(): array
    {
        return ['name', 'email', 'role_id', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'name' => ['required'],
            'email' => ['required', Rule::unique('users', 'email')->ignore($target?->getKey())],
            'role_id' => ['nullable', Rule::exists('roles', 'id')],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof User && $target->isLastAdministrator()) {
            throw ValidationException::withMessages(['user' => __('You cannot delete the last user with administrative access.')]);
        }
    }

    protected function assertApplicable(?ApprovalRequest $request, ?Model $target, string $action, array $payload): void
    {
        if ($request === null || ! $target instanceof User) {
            return;
        }

        if ($action === ApprovalRequest::ACTION_DELETE && $request->maker_user_id === $target->getKey()) {
            throw ValidationException::withMessages(['user' => __('You cannot delete your own account.')]);
        }

        if ($action === ApprovalRequest::ACTION_UPDATE
            && $request->maker_user_id === $target->getKey()
            && $target->locksOut($payload['role_id'] ?? null)) {
            throw ValidationException::withMessages(['role_id' => __('You cannot remove your own administrative access.')]);
        }
    }
}
