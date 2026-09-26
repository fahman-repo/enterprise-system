<?php

namespace App\Services\Approvals;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepartmentsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'departments';
    }

    public function label(): string
    {
        return __('Departments');
    }

    public function targetLabel(): string
    {
        return __('Department');
    }

    protected function modelClass(): string
    {
        return Department::class;
    }

    protected function fields(): array
    {
        return ['division_id', 'code', 'name', 'description', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'division_id' => ['required', Rule::exists('divisions', 'id')],
            'code' => ['required', Rule::unique('departments', 'code')->ignore($target?->getKey())],
            'name' => ['required'],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof Department && ($target->orgUnits()->exists() || $target->employees()->exists())) {
            throw ValidationException::withMessages(['department' => __('This department has org units or employees and cannot be deleted.')]);
        }
    }
}
