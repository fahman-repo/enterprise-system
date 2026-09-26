<?php

namespace App\Services\Approvals;

use App\Models\EmploymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmploymentStatusesApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'employment-statuses';
    }

    public function label(): string
    {
        return __('Employment Statuses');
    }

    public function targetLabel(): string
    {
        return __('Employment Status');
    }

    protected function modelClass(): string
    {
        return EmploymentStatus::class;
    }

    protected function fields(): array
    {
        return ['code', 'name', 'description', 'sort_order', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'code' => ['required', Rule::unique('employment_statuses', 'code')->ignore($target?->getKey())],
            'name' => ['required', Rule::unique('employment_statuses', 'name')->ignore($target?->getKey())],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof EmploymentStatus && $target->employees()->exists()) {
            throw ValidationException::withMessages(['employment_status' => __('This employment status is assigned to employees and cannot be deleted.')]);
        }
    }
}
