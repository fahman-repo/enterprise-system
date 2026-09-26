<?php

namespace App\Services\Approvals;

use App\Models\Division;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DivisionsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'divisions';
    }

    public function label(): string
    {
        return __('Divisions');
    }

    public function targetLabel(): string
    {
        return __('Division');
    }

    protected function modelClass(): string
    {
        return Division::class;
    }

    protected function fields(): array
    {
        return ['code', 'name', 'description', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'code' => ['required', Rule::unique('divisions', 'code')->ignore($target?->getKey())],
            'name' => ['required'],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof Division && $target->departments()->exists()) {
            throw ValidationException::withMessages(['division' => __('This division has departments and cannot be deleted.')]);
        }
    }
}
