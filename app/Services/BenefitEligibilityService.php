<?php

namespace App\Services;

use App\Models\Benefit;
use App\Models\BenefitClaim;
use App\Models\BenefitEnrollment;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class BenefitEligibilityService
{
    /**
     * Whether the employee may be enrolled into the benefit on the given date.
     */
    public function isEnrollable(Employee $employee, Benefit $benefit, ?Carbon $on = null): bool
    {
        try {
            $this->assertEnrollable($employee, $benefit, $on);
        } catch (ValidationException) {
            return false;
        }

        return true;
    }

    /**
     * Throw a field-specific error when the employee may not be enrolled.
     */
    public function assertEnrollable(Employee $employee, Benefit $benefit, ?Carbon $on = null): void
    {
        if (! $benefit->is_active) {
            throw ValidationException::withMessages(['employee' => __('This benefit is not active.')]);
        }

        if (! $employee->is_active || $employee->trashed()) {
            throw ValidationException::withMessages(['employee' => __('This employee is not active.')]);
        }

        $reference = $on ? $on->copy() : Carbon::now();

        if (! $employee->join_date) {
            throw ValidationException::withMessages(['employee' => __('This employee does not meet the minimum tenure requirement.')]);
        }

        $joinDate = $employee->join_date instanceof Carbon
            ? $employee->join_date->copy()->startOfDay()
            : Carbon::parse($employee->join_date)->startOfDay();

        $tenureMonths = $reference->copy()->startOfDay()->lt($joinDate) ? 0 : (int) $joinDate->diffInMonths($reference);

        if ($tenureMonths < (int) $benefit->min_tenure_months) {
            throw ValidationException::withMessages(['employee' => __('This employee does not meet the minimum tenure of :months months.', ['months' => (int) $benefit->min_tenure_months])]);
        }

        if ($benefit->eligibleGrades()->exists()) {
            if ($employee->grade_id === null || ! $benefit->eligibleGrades()->whereKey($employee->grade_id)->exists()) {
                throw ValidationException::withMessages(['employee' => __("This employee's grade is not eligible for this benefit.")]);
            }
        }

        if ($benefit->eligibleEmploymentStatuses()->exists()) {
            if ($employee->employment_status_id === null || ! $benefit->eligibleEmploymentStatuses()->whereKey($employee->employment_status_id)->exists()) {
                throw ValidationException::withMessages(['employee' => __("This employee's employment status is not eligible for this benefit.")]);
            }
        }
    }

    /**
     * Ensure the employee has no other active enrollment for the benefit.
     */
    public function assertSingleActiveEnrollment(Benefit $benefit, Employee $employee, ?int $ignoreId = null): void
    {
        $query = BenefitEnrollment::query()
            ->where('benefit_id', $benefit->getKey())
            ->where('employee_id', $employee->getKey())
            ->where('status', BenefitEnrollment::STATUS_ACTIVE);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['enrollment' => __('This employee already has an active enrollment for this benefit.')]);
        }
    }

    /**
     * Calendar window bounding the benefit limit for the given date.
     *
     * @return array{start: ?Carbon, end: ?Carbon}
     */
    public function periodWindow(Benefit $benefit, Carbon $date): array
    {
        $reference = $date->copy();

        return match ($benefit->period) {
            'monthly' => [
                'start' => $reference->copy()->startOfMonth()->startOfDay(),
                'end' => $reference->copy()->endOfMonth()->endOfDay(),
            ],
            'quarterly' => [
                'start' => $reference->copy()->startOfQuarter()->startOfDay(),
                'end' => $reference->copy()->endOfQuarter()->endOfDay(),
            ],
            'yearly' => [
                'start' => $reference->copy()->startOfYear()->startOfDay(),
                'end' => $reference->copy()->endOfYear()->endOfDay(),
            ],
            default => ['start' => null, 'end' => null],
        };
    }

    /**
     * Total approved/paid amount consumed within the period window.
     */
    public function consumedAmount(BenefitEnrollment $enrollment, ?Carbon $date = null): string
    {
        return $this->sumConsumed($enrollment, $date ?? Carbon::now(), null);
    }

    /**
     * Remaining cover for the period window, floored at zero.
     */
    public function remainingAmount(BenefitEnrollment $enrollment, ?Carbon $date = null): string
    {
        $enrollment->loadMissing('benefit');

        $limit = bcadd((string) ($enrollment->benefit?->limit_amount ?? '0'), '0', 2);
        $remaining = bcsub($limit, $this->consumedAmount($enrollment, $date), 2);

        if (bccomp($remaining, '0.00', 2) < 0) {
            return '0.00';
        }

        return bcadd($remaining, '0', 2);
    }

    /**
     * Throw when the enrollment cannot back a claim for the amount and date.
     *
     * Pending claims never consume; callers re-run this check under a
     * row lock at apply time so a double submit fails on balance.
     */
    public function assertClaimable(BenefitEnrollment $enrollment, string $amount, Carbon $claimDate, ?int $ignoreClaimId = null): void
    {
        $enrollment->loadMissing(['benefit', 'employee']);

        if (! $enrollment->isUsable()) {
            throw ValidationException::withMessages(['enrollment' => __('This enrollment is not usable for claims.')]);
        }

        $from = $enrollment->effective_from instanceof Carbon
            ? $enrollment->effective_from->copy()->startOfDay()
            : Carbon::parse($enrollment->effective_from)->startOfDay();

        if ($claimDate->copy()->startOfDay()->lt($from)) {
            throw ValidationException::withMessages(['claim_date' => __('The claim date is outside the enrollment period.')]);
        }

        if ($enrollment->effective_to !== null) {
            $to = $enrollment->effective_to instanceof Carbon
                ? $enrollment->effective_to->copy()->endOfDay()
                : Carbon::parse($enrollment->effective_to)->endOfDay();

            if ($claimDate->copy()->endOfDay()->gt($to)) {
                throw ValidationException::withMessages(['claim_date' => __('The claim date is outside the enrollment period.')]);
            }
        }

        if (bccomp($amount, '0.01', 2) < 0) {
            throw ValidationException::withMessages(['amount' => __('The claim amount must be at least 0.01.')]);
        }

        $remaining = bcadd((string) ($enrollment->benefit?->limit_amount ?? '0'), '0', 2);
        $remaining = bcsub($remaining, $this->sumConsumed($enrollment, $claimDate, $ignoreClaimId), 2);

        if (bccomp($remaining, '0.00', 2) < 0) {
            $remaining = '0.00';
        }

        if (bccomp($amount, $remaining, 2) > 0) {
            throw ValidationException::withMessages(['amount' => __('The claim amount exceeds the remaining balance of :balance.', ['balance' => $remaining])]);
        }
    }

    /**
     * Sum consuming claims in the period window, optionally ignoring one row.
     */
    protected function sumConsumed(BenefitEnrollment $enrollment, Carbon $date, ?int $ignoreClaimId): string
    {
        $enrollment->loadMissing('benefit');

        $benefit = $enrollment->benefit;

        if (! $benefit) {
            return '0.00';
        }

        $window = $this->periodWindow($benefit, $date);

        $query = BenefitClaim::query()
            ->where('benefit_enrollment_id', $enrollment->getKey())
            ->whereIn('status', BenefitClaim::CONSUMING);

        if ($ignoreClaimId !== null) {
            $query->whereKeyNot($ignoreClaimId);
        }

        if ($window['start'] !== null && $window['end'] !== null) {
            $query->whereBetween('claim_date', [$window['start']->toDateString(), $window['end']->toDateString()]);
        }

        $total = '0.00';

        foreach ($query->pluck('amount') as $amount) {
            $total = bcadd($total, (string) $amount, 2);
        }

        return $total;
    }
}
