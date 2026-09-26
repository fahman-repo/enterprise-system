<?php

namespace App\Services\Approvals;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UnitsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'units';
    }

    public function label(): string
    {
        return __('Units');
    }

    public function targetLabel(): string
    {
        return __('Unit');
    }

    protected function modelClass(): string
    {
        return Unit::class;
    }

    protected function fields(): array
    {
        return ['name', 'abbreviation', 'allows_decimal', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'name' => ['required'],
            'abbreviation' => ['required', Rule::unique('units', 'abbreviation')->ignore($target?->getKey())],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof Unit && $target->products()->exists()) {
            throw ValidationException::withMessages(['unit' => __('This unit is used by products and cannot be deleted.')]);
        }
    }
}
