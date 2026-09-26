<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use App\Models\Entity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class EntitiesApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'entities';
    }

    public function label(): string
    {
        return __('Entities');
    }

    public function targetLabel(): string
    {
        return __('Entity');
    }

    protected function modelClass(): string
    {
        return Entity::class;
    }

    protected function fields(): array
    {
        return [
            'code',
            'name',
            'type',
            'role',
            'npwp',
            'identity_number',
            'email',
            'phone',
            'address',
            'city',
            'province',
            'postal_code',
            'bank_name',
            'bank_account_number',
            'bank_account_name',
            'notes',
            'is_active',
        ];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'code' => ['nullable', Rule::unique('entities', 'code')->ignore($target?->getKey())],
            'name' => ['required'],
            'type' => ['required', Rule::in(Entity::TYPES)],
            'role' => ['required', Rule::in(Entity::ROLES)],
            'identity_number' => ['nullable', Rule::unique('entities', 'identity_number')->ignore($target?->getKey())],
        ];
    }

    protected function prepareCreate(ApprovalRequest $request, array $payload): array
    {
        if (($payload['code'] ?? '') === '') {
            $payload['code'] = Entity::generateCode();
        }

        return $payload;
    }
}
