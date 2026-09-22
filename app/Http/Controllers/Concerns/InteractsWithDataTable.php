<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared parsing and application of data-table query parameters
 * (search, sort, per page) for index listings.
 */
trait InteractsWithDataTable
{
    /**
     * The trimmed search term, or null when blank.
     */
    protected function tableSearch(Request $request): ?string
    {
        $search = trim((string) $request->query('search', ''));

        return $search === '' ? null : $search;
    }

    /**
     * The requested per-page count, restricted to known options.
     */
    protected function tablePerPage(Request $request, array $allowed = [10, 25, 50, 100], int $default = 10): int
    {
        $perPage = (int) $request->query('per_page');

        return in_array($perPage, $allowed, true) ? $perPage : $default;
    }

    /**
     * Apply a broad OR search across the given columns.
     *
     * @param  list<string>  $columns
     */
    protected function applyTableSearch(Builder $query, ?string $search, array $columns): void
    {
        if ($search === null) {
            return;
        }

        $query->where(fn (Builder $q) => collect($columns)->each(
            fn (string $column) => $q->orWhereLike($column, '%'.$search.'%')
        ));
    }

    /**
     * Apply the active/inactive status filter from the query string.
     */
    protected function applyTableStatusFilter(Builder $query, Request $request, string $column = 'is_active'): void
    {
        if (in_array($request->query('status'), ['active', 'inactive'], true)) {
            $query->where($column, $request->query('status') === 'active');
        }
    }

    /**
     * Apply sorting from an allowlist of sort keys to qualified columns.
     *
     * When no valid key is requested the default key is applied first, followed
     * by any tiebreakers. The effective key and direction are returned so the
     * table header can mark the column that was actually ordered.
     *
     * @param  array<string, string>  $sorts  sort key => qualified column
     * @param  array<string, string>  $tiebreakers  qualified column => direction
     * @return array{0: ?string, 1: ?string} effective [key, direction]
     */
    protected function applyTableSort(
        Builder $query,
        array $sorts,
        ?string $sort,
        ?string $direction,
        ?string $defaultKey = null,
        string $defaultDirection = 'asc',
        array $tiebreakers = [],
    ): array {
        if ($sort !== null && array_key_exists($sort, $sorts)) {
            $resolved = $direction === 'desc' ? 'desc' : 'asc';

            $query->orderBy($sorts[$sort], $resolved);

            return [$sort, $resolved];
        }

        $key = $defaultKey !== null && array_key_exists($defaultKey, $sorts) ? $defaultKey : null;
        $resolved = $defaultDirection === 'desc' ? 'desc' : 'asc';

        if ($key !== null) {
            $query->orderBy($sorts[$key], $resolved);
        }

        foreach ($tiebreakers as $column => $order) {
            $query->orderBy($column, $order);
        }

        return [$key, $key === null ? null : $resolved];
    }
}
