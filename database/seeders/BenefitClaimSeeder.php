<?php

namespace Database\Seeders;

use App\Models\Benefit;
use App\Models\BenefitClaim;
use App\Models\BenefitEnrollment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BenefitClaimSeeder extends Seeder
{
    /**
     * Build the claim history behind every usable enrollment. Amounts are
     * capped against the period window as they are generated, so the sum
     * of approved and paid claims never exceeds the benefit limit — the
     * same invariant BenefitEligibilityService enforces at submit time.
     *
     * Receipt paths are recorded for benefits that require one but no
     * file is written: the views only link a receipt that exists on disk.
     * Enrollments that already have claims are skipped, which keeps the
     * seeder idempotent and respects claims added through the UI.
     */
    public function run(): void
    {
        $today = Carbon::today();
        $sequence = 0;

        $enrollments = BenefitEnrollment::query()
            ->with(['benefit', 'employee'])
            ->where('status', BenefitEnrollment::STATUS_ACTIVE)
            ->orderBy('id')
            ->get()
            ->filter(fn (BenefitEnrollment $enrollment): bool => $enrollment->isUsable());

        foreach ($enrollments as $index => $enrollment) {
            if ($enrollment->benefit === null || $enrollment->claims()->exists()) {
                continue;
            }

            foreach ($this->plan($enrollment, $index, $today) as $claim) {
                $sequence++;

                BenefitClaim::query()->create([
                    'benefit_id' => $enrollment->benefit_id,
                    'benefit_enrollment_id' => $enrollment->getKey(),
                    'employee_id' => $enrollment->employee_id,
                    'claim_date' => $claim['date']->toDateString(),
                    'amount' => $claim['amount'],
                    'description' => $enrollment->benefit->name.' claim #'.$claim['number'],
                    'receipt_path' => $enrollment->benefit->requires_receipt ? $this->receiptPath($claim['date'], $sequence) : null,
                    'status' => $claim['status'],
                    'resolution_comment' => $claim['comment'],
                    'decided_at' => $claim['decided_at'],
                    'paid_at' => $claim['status'] === BenefitClaim::STATUS_PAID ? $claim['decided_at']?->copy()->addDays(5) : null,
                ]);
            }
        }
    }

    /**
     * Every claim for one enrollment, oldest first, with the amount
     * already clamped to the remaining cover of its own period window.
     *
     * @return list<array{number: int, date: Carbon, amount: string, status: string, decided_at: ?Carbon, comment: ?string}>
     */
    protected function plan(BenefitEnrollment $enrollment, int $index, Carbon $today): array
    {
        $benefit = $enrollment->benefit;
        $limit = (float) $benefit->limit_amount;
        $dates = $this->claimDates($benefit, $enrollment, 1 + ($index % 3), $today);
        $consumed = [];
        $claims = [];

        foreach ($dates as $position => $date) {
            $status = $this->status($index, $position);
            $key = $this->windowKey($benefit, $date);
            $remaining = $limit - ($consumed[$key] ?? 0.0);
            $amount = min($limit * [0.18, 0.12, 0.22][$position], $remaining);

            if ($amount < 10000) {
                continue;
            }

            if (in_array($status, BenefitClaim::CONSUMING, true)) {
                $consumed[$key] = ($consumed[$key] ?? 0.0) + $amount;
            }

            $claims[] = [
                'number' => $position + 1,
                'date' => $date,
                'amount' => number_format($amount, 2, '.', ''),
                'status' => $status,
                'decided_at' => $status === BenefitClaim::STATUS_PENDING
                    ? null
                    : $date->copy()->addDays(3)->min($today),
                'comment' => match ($status) {
                    BenefitClaim::STATUS_REJECTED => 'Receipt did not match the claimed treatment.',
                    BenefitClaim::STATUS_CANCELLED => 'Withdrawn by the employee.',
                    default => null,
                },
            ];
        }

        return $claims;
    }

    /**
     * Claim dates inside the enrollment window. Monthly benefits claim
     * once per month, longer periods spread their claims across the year.
     *
     * @return list<Carbon>
     */
    protected function claimDates(Benefit $benefit, BenefitEnrollment $enrollment, int $count, Carbon $today): array
    {
        $earliest = Carbon::parse($enrollment->effective_from)->startOfDay()->addDays(7);
        $latest = $enrollment->effective_to !== null
            ? Carbon::parse($enrollment->effective_to)->endOfDay()->min($today)
            : $today->copy();

        $dates = [];

        for ($position = 0; $position < $count; $position++) {
            $date = match ($benefit->period) {
                'monthly' => $today->copy()->subMonthsNoOverflow($position)->startOfMonth()->addDays(4),
                'quarterly' => $today->copy()->subMonthsNoOverflow($position * 3)->startOfMonth()->addDays(9),
                default => $today->copy()->subDays(17 + $position * 41),
            };

            $dates[] = $date->lt($earliest) ? $earliest->copy() : ($date->gt($latest) ? $latest->copy() : $date);
        }

        usort($dates, fn (Carbon $left, Carbon $right): int => $left->timestamp <=> $right->timestamp);

        return $dates;
    }

    /**
     * Weighted status for one claim: history covers every lifecycle
     * state, and paid claims dominate the way real claim runs do.
     */
    protected function status(int $index, int $position): string
    {
        $roll = ($index * 7 + $position * 5) % 20;

        return match (true) {
            $roll < 2 => BenefitClaim::STATUS_REJECTED,
            $roll < 3 => BenefitClaim::STATUS_CANCELLED,
            $roll < 8 => BenefitClaim::STATUS_PENDING,
            $roll < 11 => BenefitClaim::STATUS_APPROVED,
            default => BenefitClaim::STATUS_PAID,
        };
    }

    /**
     * Limit window the claim falls into: its calendar month for monthly
     * benefits, its quarter or year otherwise, and a single bucket for
     * one-time benefits that are never reset.
     */
    protected function windowKey(Benefit $benefit, Carbon $date): string
    {
        return match ($benefit->period) {
            'monthly' => $date->format('Y-m'),
            'quarterly' => $date->format('Y').'-Q'.$date->quarter,
            'yearly' => $date->format('Y'),
            default => 'once',
        };
    }

    protected function receiptPath(Carbon $date, int $sequence): string
    {
        return sprintf('benefit-claims/%s/%s/claim-%04d.pdf', $date->format('Y'), $date->format('m'), $sequence);
    }
}
