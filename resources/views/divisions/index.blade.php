<x-app-layout :title="__('Divisions')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Divisions') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the top level of the organizational structure.') }}</p>
            </div>

            @can('divisions.create')
                <x-ui.button href="{{ route('divisions.create') }}">
                    {{ __('New division') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'code', 'label' => __('Code'), 'sortable' => true],
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'departments', 'label' => __('Departments'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];

            $divisionFilters = [
                'status' => [
                    'label' => __('Status'),
                    'all' => __('All statuses'),
                    'options' => [
                        ['value' => 'active', 'label' => __('Active')],
                        ['value' => 'inactive', 'label' => __('Inactive')],
                    ],
                ],
            ];
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$divisions" :sort="$sort" :direction="$direction" :empty="__('No divisions found.')" :exclude-params="array_keys($divisionFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$divisionFilters" />
            </x-slot:filters>
            @foreach ($divisions as $division)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $division->code }}</td>
                    <td class="px-4 py-3 font-medium">{{ $division->name }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($division->departments_count) }}</td>
                    <td class="px-4 py-3">
                        @if ($division->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('divisions.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('divisions.edit', $division) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('divisions.delete')
                                <form method="POST" action="{{ route('divisions.destroy', $division) }}"
                                    onsubmit="return confirm('{{ __('Delete this division?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button variant="destructive" size="sm" type="submit">
                                        {{ __('Delete') }}
                                    </x-ui.button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </div>
</x-app-layout>
