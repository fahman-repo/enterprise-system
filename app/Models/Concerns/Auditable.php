<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Opts a model into the audit trail.
 *
 * Tracks created/updated/deleted events, records only changed attributes,
 * and stamps each entry with request context (IP, user agent) and a causer
 * snapshot so entries stay readable after a user is deleted.
 */
trait Auditable
{
    use LogsActivity;

    /**
     * Log name used to group this model's entries, e.g. "user".
     */
    abstract public function auditLogName(): string;

    /**
     * Attributes written by the model's trait or framework that must
     * never appear in the audit trail.
     *
     * @return list<string>
     */
    public function auditExcept(): array
    {
        return [];
    }

    /**
     * Extra properties merged into every audit entry for this model.
     *
     * @return array<string, mixed>
     */
    public function auditProperties(): array
    {
        return [];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName($this->auditLogName())
            ->logOnly(['*'])
            ->logExcept([...$this->auditExcept(), 'created_at', 'updated_at'])
            ->logOnlyDirty();
    }

    public function tapActivity(ActivityContract $activity, string $eventName): void
    {
        $request = request();

        $activity->properties = $activity->properties->merge([
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'causer' => $this->auditCauserSnapshot(),
            ...$this->auditProperties(),
        ]);
    }

    /**
     * Snapshot of the acting user, stored on the entry so it remains
     * readable after the causing user is deleted.
     *
     * @return array<string, mixed>|null
     */
    protected function auditCauserSnapshot(): ?array
    {
        $causer = Auth::user();

        if (! $causer instanceof Model) {
            return null;
        }

        return [
            'id' => $causer->getKey(),
            'name' => $causer->getAttribute('name'),
            'email' => $causer->getAttribute('email'),
        ];
    }
}
