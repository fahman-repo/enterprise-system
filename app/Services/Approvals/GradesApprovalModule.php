<?php

namespace App\Services\Approvals;

use App\Models\Grade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GradesApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'grades';
    }

    public function label(): string
    {
        return __('Grades');
    }

    public function targetLabel(): string
    {
        return __('Grade');
    }

    protected function modelClass(): string
    {
        return Grade::class;
    }

    protected function fields(): array
    {
        return ['name', 'level', 'description', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('grades', 'name')->ignore($target?->getKey())],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof Grade && $target->employees()->exists()) {
            throw ValidationException::withMessages(['grade' => __('This grade is assigned to employees and cannot be deleted.')]);
        }
    }
}
