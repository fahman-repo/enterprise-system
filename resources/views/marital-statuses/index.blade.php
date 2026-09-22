<x-app-layout :title="__('Marital Statuses')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Marital Statuses') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the marital statuses available on employee records.') }}</p>
            </div>

            @can('marital-statuses.create')
                <x-ui.button href="{{ route('marital-statuses.create') }}">
                    {{ __('New marital status') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'employees', 'label' => __('Employees'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'sort_order', 'label' => __('Sort'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];

            $maritalStatusFilters = [
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

        <x-ui.data-table :columns="$columns" :paginator="$maritalStatuses" :sort="$sort" :direction="$direction" :empty="__('No marital statuses found.')" :exclude-params="array_keys($maritalStatusFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$maritalStatusFilters" />
            </x-slot:filters>
            @foreach ($maritalStatuses as $maritalStatus)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-medium">{{ $maritalStatus->name }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($maritalStatus->employees_count) }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ $maritalStatus->sort_order }}</td>
                    <td class="px-4 py-3">
                        @if ($maritalStatus->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('marital-statuses.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('marital-statuses.edit', $maritalStatus) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('marital-statuses.delete')
                                <form method="POST" action="{{ route('marital-statuses.destroy', $maritalStatus) }}"
                                    onsubmit="return confirm('{{ __('Delete this marital status?') }}')">
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
