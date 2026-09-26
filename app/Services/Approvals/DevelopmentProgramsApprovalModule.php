<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use App\Models\DevelopmentProgram;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DevelopmentProgramsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'development-programs';
    }

    public function label(): string
    {
        return __('Development Programs');
    }

    public function targetLabel(): string
    {
        return __('Development Program');
    }

    protected function modelClass(): string
    {
        return DevelopmentProgram::class;
    }

    protected function fields(): array
    {
        return ['code', 'name', 'type', 'description', 'organizer', 'location', 'start_date', 'end_date', 'capacity', 'cost', 'status', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'code' => ['nullable', Rule::unique('development_programs', 'code')->ignore($target?->getKey())],
            'name' => ['required'],
            'type' => ['required', Rule::in(DevelopmentProgram::TYPES)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(DevelopmentProgram::STATUSES)],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof DevelopmentProgram && $target->enrollments()->exists()) {
            throw ValidationException::withMessages(['development_program' => __('This program has participants and cannot be deleted.')]);
        }
    }

    /**
     * Generate the program code at final approval when the maker left
     * it blank, mirroring the controller's in-transaction generation.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function prepareCreate(ApprovalRequest $request, array $payload): array
    {
        if (blank($payload['code'] ?? null)) {
            $payload['code'] = DevelopmentProgram::generateCode();
        }

        return $payload;
    }
}
