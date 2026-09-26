<?php

namespace App\Services\Approvals;

use App\Models\WorkLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkLocationsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'work-locations';
    }

    public function label(): string
    {
        return __('Work Locations');
    }

    public function targetLabel(): string
    {
        return __('Work Location');
    }

    protected function modelClass(): string
    {
        return WorkLocation::class;
    }

    protected function fields(): array
    {
        return ['code', 'name', 'address', 'city', 'province', 'postal_code', 'phone', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'code' => ['required', Rule::unique('work_locations', 'code')->ignore($target?->getKey())],
            'name' => ['required'],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof WorkLocation && $target->employees()->exists()) {
            throw ValidationException::withMessages(['work_location' => __('This work location is assigned to employees and cannot be deleted.')]);
        }
    }
}
