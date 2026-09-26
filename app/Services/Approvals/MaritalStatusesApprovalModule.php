<?php

namespace App\Services\Approvals;

use App\Models\MaritalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaritalStatusesApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'marital-statuses';
    }

    public function label(): string
    {
        return __('Marital Statuses');
    }

    public function targetLabel(): string
    {
        return __('Marital Status');
    }

    protected function modelClass(): string
    {
        return MaritalStatus::class;
    }

    protected function fields(): array
    {
        return ['name', 'sort_order', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'name' => ['required', Rule::unique('marital_statuses', 'name')->ignore($target?->getKey())],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof MaritalStatus && $target->employees()->exists()) {
            throw ValidationException::withMessages(['marital_status' => __('This marital status is assigned to employees and cannot be deleted.')]);
        }
    }
}
