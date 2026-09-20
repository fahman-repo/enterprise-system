<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Audit trail entry. Reads write nothing; records are appended by the
 * LogsActivity model hooks and the auth event listeners.
 *
 * @property-read Collection $changes
 */
class Activity extends SpatieActivity
{
    /**
     * Badge variant used to colour an event in the audit log UI.
     */
    public function variant(): string
    {
        return match ($this->event) {
            'created', 'login' => 'success',
            'updated', 'logout' => 'secondary',
            'deleted' => 'destructive',
            'failed_login' => 'destructive',
            default => 'muted',
        };
    }

    /**
     * Human readable event name for badges.
     */
    public function eventLabel(): string
    {
        return match ($this->event) {
            'created' => __('Created'),
            'updated' => __('Updated'),
            'deleted' => __('Deleted'),
            'login' => __('Logged in'),
            'logout' => __('Logged out'),
            'failed_login' => __('Failed login'),
            default => $this->event ?? __('Activity'),
        };
    }

    /**
     * Name of the person that caused the activity, falling back to the
     * snapshot stored on the log so it survives user deletion.
     */
    public function causerName(): string
    {
        if ($this->causer) {
            return $this->causer->name;
        }

        return $this->getExtraProperty('causer.name')
            ?? $this->getExtraProperty('email')
            ?? __('System');
    }

    /**
     * Short class name of the audited record, e.g. "User".
     */
    public function subjectTypeLabel(): string
    {
        return $this->subject_type ? class_basename($this->subject_type) : __('Unknown');
    }

    /**
     * Display label for the audited record, e.g. "Jane Doe". Prefers the
     * snapshot stored on the entry so it survives later renames and
     * deletions.
     */
    public function subjectLabel(): string
    {
        $snapshot = $this->subjectSnapshot();

        foreach (['name', 'slug', 'email'] as $attribute) {
            if (filled($snapshot[$attribute] ?? null)) {
                return (string) $snapshot[$attribute];
            }
        }

        return $this->subject_type
            ? __(':type #:id', ['type' => $this->subjectTypeLabel(), 'id' => $this->subject_id])
            : '—';
    }

    /**
     * Attribute snapshot from the entry; falls back to the loaded subject.
     *
     * @return array<string, mixed>
     */
    public function subjectSnapshot(): array
    {
        $properties = $this->properties instanceof Collection
            ? $this->properties->all()
            : (array) $this->properties;

        $snapshot = ($properties['attributes'] ?? []) + ($properties['old'] ?? []);

        if ($snapshot === [] && $this->subject) {
            $snapshot = $this->subject->getAttributes();
        }

        return $snapshot;
    }

    /**
     * Attribute changes formatted as [attribute => ['old' => mixed, 'new' => mixed]].
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function changesForDisplay(): array
    {
        $properties = $this->properties instanceof Collection
            ? $this->properties->all()
            : (array) $this->properties;

        $old = (array) ($properties['old'] ?? []);
        $new = (array) ($properties['attributes'] ?? []);

        // Manual entries such as a permission-matrix sync store their
        // snapshots as top-level properties instead of a package diff.
        if (array_key_exists('permissions_before', $properties) || array_key_exists('permissions_after', $properties)) {
            $old['permissions_before'] = $properties['permissions_before'] ?? [];
            $new['permissions_after'] = $properties['permissions_after'] ?? [];
        }

        $display = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $attribute) {
            $display[$attribute] = [
                'old' => $old[$attribute] ?? null,
                'new' => $new[$attribute] ?? null,
            ];
        }

        return $display;
    }

    /**
     * Short summary of what changed, e.g. "2 fields changed".
     */
    public function changesSummary(): string
    {
        $count = count($this->changesForDisplay());

        if ($count === 0) {
            return '—';
        }

        return trans_choice(':count field changed|:count fields changed', $count, ['count' => $count]);
    }

    /**
     * Render a change value for the detail table.
     */
    public function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        if (is_array($value)) {
            return json_encode($value) ?: '—';
        }

        return (string) $value;
    }

    /**
     * Render a permission matrix snapshot as readable lines.
     *
     * @return array<int, array{menu_id: int, actions: list<string>}>
     */
    public function permissionRows(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $actionLabels = [
            'view' => __('view'),
            'create' => __('create'),
            'update' => __('update'),
            'delete' => __('delete'),
        ];

        return collect($value)
            ->map(fn (array $flags, int|string $menuId): array => [
                'menu_id' => (int) $menuId,
                'actions' => collect($actionLabels)
                    ->filter(fn (string $label, string $action): bool => (bool) ($flags[$action] ?? false))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
