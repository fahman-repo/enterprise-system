<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use App\Models\Benefit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BenefitsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'benefits';
    }

    public function label(): string
    {
        return __('Benefits');
    }

    public function targetLabel(): string
    {
        return __('Benefit');
    }

    protected function modelClass(): string
    {
        return Benefit::class;
    }

    protected function fields(): array
    {
        return ['code', 'name', 'type', 'description', 'limit_amount', 'period', 'min_tenure_months', 'requires_receipt', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'code' => ['required', Rule::unique('benefits', 'code')->ignore($target?->getKey())],
            'name' => ['required', Rule::unique('benefits', 'name')->ignore($target?->getKey())],
            'type' => ['required', Rule::in(Benefit::TYPES)],
            'period' => ['required', Rule::in(Benefit::PERIODS)],
            'limit_amount' => ['required', 'numeric', 'min:0'],
            'min_tenure_months' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof Benefit && ($target->enrollments()->exists() || $target->claims()->exists())) {
            throw ValidationException::withMessages(['benefit' => __('This benefit has enrollments or claims and cannot be deleted.')]);
        }
    }

    /**
     * Persist the benefit and sync its eligibility pivots.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function prepareCreate(ApprovalRequest $request, array $payload): array
    {
        unset($payload['eligible_grade_ids'], $payload['eligible_employment_status_ids']);

        return $payload;
    }

    public function apply(ApprovalRequest $request, ?Model $target): ?Model
    {
        $payload = $request->proposed_payload;
        $gradeIds = $payload['eligible_grade_ids'] ?? null;
        $statusIds = $payload['eligible_employment_status_ids'] ?? null;

        unset($payload['eligible_grade_ids'], $payload['eligible_employment_status_ids']);

        if ($request->action === ApprovalRequest::ACTION_CREATE) {
            $modelClass = $this->modelClass();
            $benefit = $modelClass::query()->create($this->prepareCreate($request, $payload));

            $this->syncPivots($benefit, $gradeIds, $statusIds);

            return $benefit;
        }

        if ($request->action === ApprovalRequest::ACTION_UPDATE && $this->isTarget($target)) {
            $this->applyUpdate($request, $target, $payload);
            $this->syncPivots($target, $gradeIds, $statusIds);

            return $target;
        }

        return parent::apply($request, $target);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function applyUpdate(ApprovalRequest $request, Model $target, array $payload): void
    {
        unset($payload['eligible_grade_ids'], $payload['eligible_employment_status_ids']);

        $target->update($payload);
    }

    /**
     * @param  list<int>|null  $gradeIds
     * @param  list<int>|null  $statusIds
     */
    protected function syncPivots(Model $benefit, mixed $gradeIds, mixed $statusIds): void
    {
        if (! $benefit instanceof Benefit) {
            return;
        }

        if (is_array($gradeIds)) {
            $benefit->eligibleGrades()->sync($gradeIds);
        }

        if (is_array($statusIds)) {
            $benefit->eligibleEmploymentStatuses()->sync($statusIds);
        }
    }
}
