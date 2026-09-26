<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use App\Models\BenefitClaim;
use App\Models\BenefitEnrollment;
use App\Services\BenefitEligibilityService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BenefitClaimsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'benefit-claims';
    }

    public function label(): string
    {
        return __('Benefit Claims');
    }

    public function targetLabel(): string
    {
        return __('Benefit Claim');
    }

    protected function modelClass(): string
    {
        return BenefitClaim::class;
    }

    protected function fields(): array
    {
        return ['benefit_id', 'benefit_enrollment_id', 'employee_id', 'claim_date', 'amount', 'description', 'receipt_path', 'status'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'benefit_id' => ['required', Rule::exists('benefits', 'id')],
            'benefit_enrollment_id' => ['required', Rule::exists('benefit_enrollments', 'id')],
            'employee_id' => ['required', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'claim_date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'status' => ['required', Rule::in(BenefitClaim::STATUSES)],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function assertApplicable(?ApprovalRequest $request, ?Model $target, string $action, array $payload): void
    {
        if ($action === ApprovalRequest::ACTION_DELETE) {
            return;
        }

        $status = $payload['status'] ?? BenefitClaim::STATUS_PENDING;

        if (in_array($status, [BenefitClaim::STATUS_PENDING], true) === false) {
            $message = __('Only pending claims can be submitted.');

            if ($request !== null) {
                throw ValidationException::withMessages(['approval' => $message]);
            }

            throw ValidationException::withMessages(['status' => $message]);
        }

        $enrollmentId = $payload['benefit_enrollment_id'] ?? ($target instanceof BenefitClaim ? $target->benefit_enrollment_id : null);

        $enrollmentQuery = BenefitEnrollment::query()->with(['benefit', 'employee']);

        if ($request !== null) {
            $enrollmentQuery->lockForUpdate();
        }

        $enrollment = $enrollmentQuery->find($enrollmentId);

        if (! $enrollment) {
            return;
        }

        try {
            if ((int) ($payload['benefit_id'] ?? $enrollment->benefit_id) !== (int) $enrollment->benefit_id) {
                throw ValidationException::withMessages(['benefit_id' => __('The benefit does not match the enrollment.')]);
            }

            if ((int) ($payload['employee_id'] ?? $enrollment->employee_id) !== (int) $enrollment->employee_id) {
                throw ValidationException::withMessages(['employee_id' => __('The employee does not match the enrollment.')]);
            }

            if ($enrollment->benefit?->requires_receipt && blank($payload['receipt_path'] ?? ($target instanceof BenefitClaim ? $target->receipt_path : null))) {
                throw ValidationException::withMessages(['receipt_path' => __('A receipt is required for this benefit.')]);
            }

            $claimDate = isset($payload['claim_date']) ? Carbon::parse($payload['claim_date']) : Carbon::today();
            $ignoreId = $action === ApprovalRequest::ACTION_UPDATE && $target instanceof BenefitClaim ? $target->getKey() : null;

            app(BenefitEligibilityService::class)->assertClaimable($enrollment, (string) ($payload['amount'] ?? '0'), $claimDate, $ignoreId);
        } catch (ValidationException $exception) {
            if ($request !== null) {
                throw ValidationException::withMessages([
                    'approval' => collect($exception->errors())->flatten()->first(),
                ]);
            }

            throw $exception;
        }
    }

    public function apply(ApprovalRequest $request, ?Model $target): ?Model
    {
        // Approval of a claim-create request only materializes the pending
        // row; approvers then decide it from the claim show page.
        if ($request->action === ApprovalRequest::ACTION_CREATE) {
            $payload = $request->proposed_payload;
            $payload['status'] = BenefitClaim::STATUS_PENDING;

            unset($payload['eligible_grade_ids'], $payload['eligible_employment_status_ids']);

            return BenefitClaim::query()->create($payload);
        }

        return parent::apply($request, $target);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function applyUpdate(ApprovalRequest $request, Model $target, array $payload): void
    {
        /** @var BenefitClaim $target */
        $old = $target->receipt_path;

        if (($payload['status'] ?? BenefitClaim::STATUS_PENDING) !== BenefitClaim::STATUS_PENDING) {
            $payload['status'] = BenefitClaim::STATUS_PENDING;
        }

        $target->update($payload);

        if (array_key_exists('receipt_path', $payload) && $old && $old !== $payload['receipt_path']) {
            Storage::disk('public')->delete($old);
        }
    }

    /**
     * Delete the receipt stored when the request was submitted but never applied.
     */
    public function discard(ApprovalRequest $request): void
    {
        $new = $request->proposed_payload['receipt_path'] ?? null;
        $old = $request->before_payload['receipt_path'] ?? null;

        if ($new && $new !== $old) {
            Storage::disk('public')->delete($new);
        }
    }
}
