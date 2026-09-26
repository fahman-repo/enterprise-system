<?php

namespace App\Services\Approvals;

use App\Models\EducationLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EducationLevelsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'education-levels';
    }

    public function label(): string
    {
        return __('Education Levels');
    }

    public function targetLabel(): string
    {
        return __('Education Level');
    }

    protected function modelClass(): string
    {
        return EducationLevel::class;
    }

    protected function fields(): array
    {
        return ['name', 'level', 'sort_order', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'name' => ['required', Rule::unique('education_levels', 'name')->ignore($target?->getKey())],
            'level' => ['required'],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof EducationLevel && $target->employees()->exists()) {
            throw ValidationException::withMessages(['education_level' => __('This education level is assigned to employees and cannot be deleted.')]);
        }
    }
}
