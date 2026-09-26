<?php

namespace App\Services\Approvals;

use App\Models\OrgUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrgUnitsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'org-units';
    }

    public function label(): string
    {
        return __('Org Units');
    }

    public function targetLabel(): string
    {
        return __('Org Unit');
    }

    protected function modelClass(): string
    {
        return OrgUnit::class;
    }

    protected function fields(): array
    {
        return ['department_id', 'code', 'name', 'description', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'department_id' => ['required', Rule::exists('departments', 'id')],
            'code' => ['required', Rule::unique('org_units', 'code')->ignore($target?->getKey())],
            'name' => ['required'],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof OrgUnit && $target->employees()->exists()) {
            throw ValidationException::withMessages(['org_unit' => __('This org unit has employees and cannot be deleted.')]);
        }
    }
}
