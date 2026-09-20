<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Event types written to the audit trail.
     *
     * @var list<string>
     */
    public const EVENTS = ['created', 'updated', 'deleted', 'login', 'logout', 'failed_login'];

    /**
     * Display a listing of audit trail entries.
     */
    public function index(Request $request): View
    {
        $query = Activity::query()
            ->select('activity_log.*')
            ->leftJoin('users', 'users.id', '=', 'activity_log.causer_id')
            ->with(['causer', 'subject'])
            ->whereIn('event', self::EVENTS);

        $search = $this->tableSearch($request);

        if ($search !== null) {
            $term = '%'.$search.'%';
            $usesPostgres = DB::connection((new Activity)->getConnectionName())->getDriverName() === 'pgsql';

            $query->where(function (Builder $group) use ($term, $usesPostgres) {
                $group->whereLike('activity_log.description', $term)
                    ->orWhereLike('users.name', $term)
                    ->orWhereLike('activity_log.subject_type', $term);

                if ($usesPostgres) {
                    $group->orWhereRaw('activity_log.properties::text ilike ?', [$term]);

                    return;
                }

                foreach (['name', 'slug', 'email'] as $attribute) {
                    $group->orWhere("activity_log.properties->attributes->{$attribute}", 'like', $term)
                        ->orWhere("activity_log.properties->old->{$attribute}", 'like', $term);
                }
            });
        }

        $this->applyFilters($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'created_at' => 'activity_log.created_at',
            'event' => 'activity_log.event',
            'causer' => 'users.name',
            'subject' => 'activity_log.subject_type',
        ], $request->query('sort'), $request->query('direction'), 'created_at', 'desc');

        $activities = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('audit-logs.index', [
            'activities' => $activities,
            'sort' => $sort,
            'direction' => $direction,
            'causers' => $this->causerOptions(),
        ]);
    }

    /**
     * Display a single audit trail entry with its attribute diff.
     */
    public function show(Activity $activity): View
    {
        $activity->load(['causer', 'subject']);

        return view('audit-logs.show', ['activity' => $activity]);
    }

    /**
     * Apply the event, actor and date range filters.
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        $event = $request->query('event');

        if (is_string($event) && in_array($event, self::EVENTS, true)) {
            $query->where('activity_log.event', $event);
        }

        $causer = $request->query('causer');

        if (is_string($causer) && ctype_digit($causer)) {
            $query
                ->where('activity_log.causer_type', (new User)->getMorphClass())
                ->where('activity_log.causer_id', $causer);
        }

        $from = $request->query('from');
        $to = $request->query('to');

        if (is_string($from) && $from !== '') {
            $query->whereDate('activity_log.created_at', '>=', $from);
        }

        if (is_string($to) && $to !== '') {
            $query->whereDate('activity_log.created_at', '<=', $to);
        }
    }

    /**
     * Users that appear as an actor in the audit trail, for the filter select.
     *
     * @return Collection<int, string>
     */
    protected function causerOptions(): Collection
    {
        return User::query()
            ->whereIn('id', Activity::query()->whereNotNull('causer_id')->distinct()->pluck('causer_id'))
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
