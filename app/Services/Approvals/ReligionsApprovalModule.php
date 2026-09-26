<?php

namespace App\Services\Approvals;

use App\Models\Religion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReligionsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'religions';
    }

    public function label(): string
    {
        return __('Religions');
    }

    public function targetLabel(): string
    {
        return __('Religion');
    }

    protected function modelClass(): string
    {
        return Religion::class;
    }

    protected function fields(): array
    {
        return ['name', 'sort_order', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'name' => ['required', Rule::unique('religions', 'name')->ignore($target?->getKey())],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof Religion && $target->employees()->exists()) {
            throw ValidationException::withMessages(['religion' => __('This religion is assigned to employees and cannot be deleted.')]);
        }
    }
}
