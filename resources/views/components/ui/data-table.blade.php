@props([
    'columns',
    'paginator',
    'sort' => null,
    'direction' => null,
    'searchPlaceholder' => __('Search…'),
    'empty' => __('No results found.'),
    'perPageOptions' => [10, 25, 50, 100],
])

@php
    $colspan = count($columns);
    $sortKey = $sort ?? request('sort');
    $sortDirection = ($direction ?? request('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
    $hiddenInputs = collect(request()->query())->except(['search', 'per_page', 'page']);
    $showing = $paginator->total() > 0 && $paginator->firstItem() !== null
        ? __('Showing :first–:last of :total', [
            'first' => number_format((int) $paginator->firstItem()),
            'last' => number_format((int) $paginator->lastItem()),
            'total' => number_format((int) $paginator->total()),
        ])
        : __('Showing 0 of :total', ['total' => number_format((int) $paginator->total())]);
    $emptyMessage = request()->filled('search')
        ? __('No results match “:search”.', ['search' => request('search')])
        : $empty;
@endphp

<x-ui.card>
    <form method="GET" class="flex flex-wrap items-center gap-3 border-b border-border p-4">
        @foreach ($hiddenInputs as $name => $value)
            @if (is_array($value))
                @foreach ($value as $nested)
                    <input type="hidden" name="{{ $name }}[]" value="{{ $nested }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach

        @isset($filters)
            {{ $filters }}
        @endisset

        <div class="min-w-52 flex-1">
            <x-ui.input
                type="search"
                name="search"
                :placeholder="$searchPlaceholder"
                value="{{ request('search') }}"
                x-data
                @input.debounce.300ms="$el.form.requestSubmit()"
            />
        </div>

        <label class="flex items-center gap-2 text-sm text-muted-foreground">
            {{ __('Per page') }}

            <x-ui.select
                name="per_page"
                class="w-auto"
                x-data
                @change="$el.form.requestSubmit()"
            >
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}" @selected((string) $option === (string) request('per_page', $perPageOptions[0]))>
                        {{ $option }}
                    </option>
                @endforeach
            </x-ui.select>
        </label>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                    @foreach ($columns as $column)
                        @php
                            $active = $sortKey !== null && $sortKey === $column['key'];
                            $columnDirection = $active ? $sortDirection : null;
                            $next = $columnDirection === 'asc' ? 'desc' : 'asc';
                            $indicatorClass = $columnDirection === 'asc' ? 'rotate-180' : '';
                            $sortable = $column['sortable'] ?? true;
                            $align = $column['align'] ?? 'left';
                            $sortUrl = request()->url().'?'.http_build_query(array_merge(request()->query(), [
                                'sort' => $column['key'],
                                'direction' => $next,
                                'page' => null,
                            ]));
                        @endphp

                        <th
                            class="px-4 py-3 font-medium {{ $align === 'right' ? 'text-right' : 'text-left' }}"
                            @if ($sortable) aria-sort="{{ $columnDirection === null ? 'none' : ($columnDirection === 'desc' ? 'descending' : 'ascending') }}" @endif
                        >
                            @if ($sortable)
                                <a href="{{ $sortUrl }}" class="inline-flex items-center gap-1 hover:text-foreground">
                                    {{ $column['label'] }}

                                    @if ($active)
                                        {{-- Bound attribute on purpose: Blade compiles component tags before directives, so an
                                             @if/@class inside this tag would leave the tag uncompiled and render no icon. --}}
                                        <x-icon.chevron-down :class="$indicatorClass" />
                                    @endif
                                </a>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y">
                @if ($paginator->isEmpty())
                    <tr>
                        <td colspan="{{ $colspan }}" class="px-4 py-12 text-center text-muted-foreground">
                            {{ $emptyMessage }}
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm text-muted-foreground">
        <span>{{ $showing }}</span>

        @if ($paginator->hasPages())
            {{ $paginator->links() }}
        @endif
    </div>
</x-ui.card>
