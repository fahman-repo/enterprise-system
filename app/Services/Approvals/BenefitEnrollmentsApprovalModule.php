<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use App\Models\Benefit;
use App\Models\BenefitEnrollment;
use App\Models\Employee;
use App\Services\BenefitEligibilityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BenefitEnrollmentsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'benefit-enrollments';
    }

    public function label(): string
    {
        return __('Benefit Enrollments');
    }

    public function targetLabel(): string
    {
        return __('Benefit Enrollment');
    }

    protected function modelClass(): string
    {
        return BenefitEnrollment::class;
    }

    protected function fields(): array
    {
        return ['benefit_id', 'employee_id', 'status', 'effective_from', 'effective_to', 'notes'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'benefit_id' => ['required', Rule::exists('benefits', 'id')],
            'employee_id' => ['required', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'status' => ['required', Rule::in(BenefitEnrollment::STATUSES)],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date'],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof BenefitEnrollment && $target->claims()->exists()) {
            throw ValidationException::withMessages(['enrollment' => __('This enrollment has claims and cannot be deleted.')]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function assertApplicable(?ApprovalRequest $request, ?Model $target, string $action, array $payload): void
    {
        if ($action === ApprovalRequest::ACTION_DELETE) {
            return;
        }

        $benefitId = $payload['benefit_id'] ?? ($target instanceof BenefitEnrollment ? $target->benefit_id : null);
        $employeeId = $payload['employee_id'] ?? ($target instanceof BenefitEnrollment ? $target->employee_id : null);

        $benefit = Benefit::query()->find($benefitId);
        $employee = Employee::query()->withTrashed()->find($employeeId);

        if (! $benefit || ! $employee) {
            return;
        }

        $service = app(BenefitEligibilityService::class);

        try {
            $service->assertEnrollable($employee, $benefit);
            $ignoreId = $action === ApprovalRequest::ACTION_UPDATE && $target instanceof BenefitEnrollment ? $target->getKey() : null;
            $service->assertSingleActiveEnrollment($benefit, $employee, $ignoreId);
        } catch (ValidationException $exception) {
            if ($request !== null) {
                throw ValidationException::withMessages([
                    'approval' => collect($exception->errors())->flatten()->first(),
                ]);
            }

            throw $exception;
        }
    }
}
