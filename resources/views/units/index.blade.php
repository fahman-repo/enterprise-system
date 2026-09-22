<x-app-layout :title="__('Units')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Units') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Units of measure products are stocked and sold in.') }}</p>
            </div>

            @can('units.create')
                <x-ui.button href="{{ route('units.create') }}">
                    {{ __('New unit') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'abbreviation', 'label' => __('Abbreviation'), 'sortable' => true],
                ['key' => 'products', 'label' => __('Products'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];

            $unitFilters = [
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

        <x-ui.data-table :columns="$columns" :paginator="$units" :sort="$sort" :direction="$direction" :empty="__('No units found.')" :exclude-params="array_keys($unitFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$unitFilters" />
            </x-slot:filters>
            @foreach ($units as $unit)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-medium">{{ $unit->name }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $unit->abbreviation }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($unit->products_count) }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($unit->is_active)
                                <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                            @else
                                <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                            @endif

                            @if ($unit->allows_decimal)
                                <x-ui.badge variant="outline">{{ __('Decimals') }}</x-ui.badge>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('units.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('units.edit', $unit) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('units.delete')
                                <form method="POST" action="{{ route('units.destroy', $unit) }}"
                                    onsubmit="return confirm('{{ __('Delete this unit?') }}')">
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
