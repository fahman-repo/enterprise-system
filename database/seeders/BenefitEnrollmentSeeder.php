<?php

namespace Database\Seeders;

use App\Models\Benefit;
use App\Models\BenefitEnrollment;
use App\Models\Employee;
use App\Services\BenefitEligibilityService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BenefitEnrollmentSeeder extends Seeder
{
    /**
     * Enroll a deterministic slice of the workforce into each benefit.
     * Candidates are filtered through the same eligibility service the
     * forms use, so tenure, grade and employment-status windows are
     * respected and the seeded data can never contradict the rules.
     * One enrollment per employee and benefit keeps re-runs idempotent.
     */
    public function run(): void
    {
        $employees = Employee::query()
            ->where('is_active', true)
            ->orderBy('employee_number')
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        $service = app(BenefitEligibilityService::class);
        $today = Carbon::today();
        $window = 0;

        foreach (self::definitions() as $code => $statusCounts) {
            $benefit = Benefit::query()->where('code', $code)->first();

            if ($benefit === null) {
                continue;
            }

            $eligible = $employees
                ->filter(fn (Employee $employee): bool => $service->isEnrollable($employee, $benefit))
                ->values();

            if ($eligible->isEmpty()) {
                continue;
            }

            $statuses = $this->statusSequence($statusCounts);
            $offset = ($window * 17) % $eligible->count();
            $window++;

            foreach ($statuses as $seat => $status) {
                $employee = $eligible[($offset + $seat) % $eligible->count()];

                BenefitEnrollment::query()->updateOrCreate(
                    ['benefit_id' => $benefit->getKey(), 'employee_id' => $employee->getKey()],
                    $this->window($status, $seat, $today),
                );
            }
        }
    }

    /**
     * Enrollments per benefit: how many rows carry each lifecycle status.
     * The mix guarantees the index filters, balance checks and eligibility
     * badges all have data on both sides of every rule.
     *
     * @return array<string, array<string, int>>
     */
    public static function definitions(): array
    {
        return [
            'BEN-MEDICAL' => ['active' => 36, 'suspended' => 3, 'expired' => 3, 'cancelled' => 3],
            'BEN-DENTAL' => ['active' => 22, 'expired' => 2, 'cancelled' => 1],
            'BEN-MEAL' => ['active' => 26, 'suspended' => 2],
            'BEN-TRANSPORT' => ['active' => 22, 'expired' => 2, 'cancelled' => 1],
            'BEN-WELLNESS' => ['active' => 14, 'suspended' => 1, 'cancelled' => 2],
            'BEN-EDUCATION' => ['active' => 10, 'cancelled' => 1],
            'BEN-EXEC-HEALTH' => ['active' => 10, 'suspended' => 1],
        ];
    }

    /**
     * Flatten the status counts into one status per seat.
     *
     * @param  array<string, int>  $counts
     * @return list<string>
     */
    protected function statusSequence(array $counts): array
    {
        $statuses = [];

        foreach ($counts as $status => $count) {
            $statuses = [...$statuses, ...array_fill(0, $count, $status)];
        }

        return $statuses;
    }

    /**
     * Coverage window for one enrollment. Active rows start inside the
     * current plan year and mostly run open-ended; every third one ends
     * in the future so expiry-based badges have live examples. Expired
     * and cancelled rows closed in the past, suspended rows stay open
     * but unusable.
     *
     * @return array<string, mixed>
     */
    protected function window(string $status, int $seat, Carbon $today): array
    {
        $from = match ($status) {
            'active' => $today->copy()->subDays(45 + ($seat % 9) * 15),
            'suspended' => $today->copy()->subDays(200 + ($seat % 5) * 20),
            'expired' => $today->copy()->subDays(420 + ($seat % 4) * 30),
            default => $today->copy()->subDays(300 + ($seat % 6) * 25),
        };

        $to = match ($status) {
            'active' => $seat % 3 === 0 ? $today->copy()->addDays(90 + $seat) : null,
            'expired' => $today->copy()->subDays(30 + $seat),
            'cancelled' => $today->copy()->subDays(20 + $seat),
            default => null,
        };

        return [
            'status' => $status,
            'effective_from' => $from->toDateString(),
            'effective_to' => $to?->toDateString(),
            'notes' => match ($status) {
                'suspended' => 'Under review by HR Operations.',
                'expired' => 'Coverage lapsed at the end of the previous plan year.',
                'cancelled' => 'Cancelled at the request of the employee.',
                default => null,
            },
        ];
    }
}
