<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ActivityLogSeeder extends Seeder
{
    /**
     * Minutes covered by the seeded audit timeline: the entries written
     * while seeding are spread over roughly the last two months so the
     * audit log reads like a real timeline instead of one burst, and the
     * authentication history of every demo account is added on top.
     */
    private const SPAN_MINUTES = 60 * 24 * 58;

    public function run(): void
    {
        $this->backdateEntries();
        $this->seedAuthHistory();
        $this->syncApprovalTimestamps();
    }

    /**
     * Reassign the created_at of every existing entry so the ids stay in
     * order while the timestamps spread over the seeded window. Entries
     * are grouped into one bucket per day to keep the update count small.
     */
    protected function backdateEntries(): void
    {
        $ids = Activity::query()->orderBy('id')->pluck('id');
        $count = $ids->count();

        if ($count === 0) {
            return;
        }

        $days = (int) ceil(self::SPAN_MINUTES / (60 * 24));
        $perBucket = max(1, (int) ceil($count / $days));
        $buckets = $ids->chunk($perBucket)->values();
        $bucketCount = $buckets->count();

        foreach ($buckets as $bucket => $chunk) {
            $daysAgo = $bucketCount <= 1
                ? 0
                : (int) round(($bucketCount - 1 - $bucket) * ($days / ($bucketCount - 1)));

            Activity::query()
                ->whereIn('id', $chunk->all())
                ->update(['created_at' => now()->subDays($daysAgo)]);
        }
    }

    /**
     * Sign-in history for the demo accounts and the failed attempts
     * against the disabled account, so the audit filters and the
     * dashboard have something to show on a fresh install.
     */
    protected function seedAuthHistory(): void
    {
        if (Activity::query()->where('log_name', 'auth')->whereIn('event', ['login', 'logout'])->exists()) {
            return;
        }

        $users = User::query()->orderBy('id')->get();

        foreach ($users as $index => $user) {
            if (! $user->is_active) {
                continue;
            }

            for ($session = 0; $session < 3; $session++) {
                $loginAt = now()
                    ->subDays(21 - ($session * 6))
                    ->setTime(8 + ($index % 3), 12 + ($session * 9));

                $this->entry('login', 'Logged in', $loginAt, [
                    'email' => $user->email,
                    'guard' => 'web',
                    'remember' => false,
                ], $user);

                $this->entry('logout', 'Logged out', $loginAt->copy()->addHours(8)->addMinutes(20), [
                    'email' => $user->email,
                    'guard' => 'web',
                ], $user);
            }
        }

        foreach (User::query()->where('is_active', false)->get() as $user) {
            foreach ([1, 2, 3] as $attempt) {
                $this->entry('failed_login', 'Failed login', now()->subDays(9 - $attempt)->setTime(7, 45 + ($attempt * 4)), [
                    'email' => $user->email,
                    'guard' => 'web',
                    'ip' => '10.20.30.'.(40 + $attempt),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ]);
            }
        }
    }

    /**
     * Align approval request timestamps with the audit trail, so the
     * approval list shows submissions and decisions spread over the same
     * weeks as their entries instead of a single seeding moment.
     */
    protected function syncApprovalTimestamps(): void
    {
        $requests = ApprovalRequest::query()->get();

        foreach ($requests as $request) {
            $entries = Activity::query()
                ->where('subject_type', $request->getMorphClass())
                ->where('subject_id', $request->getKey())
                ->orderBy('created_at')
                ->get();

            $submitted = $entries->firstWhere('event', 'submitted');
            $decided = $entries->last(fn (Activity $activity): bool => in_array($activity->event, ['approved', 'rejected', 'cancelled'], true));

            if ($submitted === null) {
                continue;
            }

            $timestamps = [
                'created_at' => $submitted->created_at,
                'submitted_at' => $submitted->created_at,
            ];

            if ($decided !== null) {
                $timestamps['completed_at'] = $decided->created_at;
                $timestamps['updated_at'] = $decided->created_at;
                $timestamps[match ($decided->event) {
                    'approved' => 'approved_at',
                    'rejected' => 'rejected_at',
                    default => 'cancelled_at',
                }] = $decided->created_at;
            }

            ApprovalRequest::query()->whereKey($request->getKey())->update($timestamps);

            $this->syncStageTimestamps($request, $entries);
        }
    }

    /**
     * Stamp each decided stage with the moment its decision was recorded.
     *
     * @param  Collection<int, Activity>  $entries
     */
    protected function syncStageTimestamps(ApprovalRequest $request, Collection $entries): void
    {
        foreach ($entries as $entry) {
            if (! in_array($entry->event, ['approved', 'stage_approved', 'rejected'], true)) {
                continue;
            }

            $stageNumber = $entry->getExtraProperty('stage_number');

            if ($stageNumber === null) {
                continue;
            }

            ApprovalRequestStage::query()
                ->where('approval_request_id', $request->getKey())
                ->where('stage_number', (int) $stageNumber)
                ->update(['decided_at' => $entry->created_at]);
        }
    }

    /**
     * Write one authentication entry and stamp it at the given moment.
     *
     * @param  array<string, mixed>  $properties
     */
    protected function entry(string $event, string $description, Carbon $at, array $properties, ?User $causer = null): void
    {
        $logger = activity('auth')->event($event);

        if ($causer !== null) {
            $logger->causedBy($causer);
        } else {
            $logger->causedByAnonymous();
        }

        $activity = $logger
            ->withProperties([
                ...$properties,
                'ip' => $properties['ip'] ?? '10.20.30.'.((($causer?->getKey() ?? 0) % 40) + 60),
                'user_agent' => $properties['user_agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])
            ->log($description);

        Activity::query()->whereKey($activity->getKey())->update(['created_at' => $at]);
    }
}
