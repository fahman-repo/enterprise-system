<?php

namespace App\Services\Approvals;

use App\Models\Position;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PositionsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'positions';
    }

    public function label(): string
    {
        return __('Positions');
    }

    public function targetLabel(): string
    {
        return __('Position');
    }

    protected function modelClass(): string
    {
        return Position::class;
    }

    protected function fields(): array
    {
        return ['department_id', 'code', 'name', 'description', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'code' => ['required', Rule::unique('positions', 'code')->ignore($target?->getKey())],
            'name' => ['required'],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof Position && $target->employees()->exists()) {
            throw ValidationException::withMessages(['position' => __('This position is assigned to employees and cannot be deleted.')]);
        }
    }
}
