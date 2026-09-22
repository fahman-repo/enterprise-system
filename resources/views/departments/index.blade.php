<x-app-layout :title="__('Departments')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Departments') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the departments under each division.') }}</p>
            </div>

            @can('departments.create')
                <x-ui.button href="{{ route('departments.create') }}">
                    {{ __('New department') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'code', 'label' => __('Code'), 'sortable' => true],
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'division', 'label' => __('Division'), 'sortable' => true],
                ['key' => 'org_units', 'label' => __('Org units'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'employees', 'label' => __('Employees'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];

            $departmentFilters = [
                'division_id' => [
                    'label' => __('Division'),
                    'all' => __('All divisions'),
                    'options' => $divisions->map(fn ($division) => [
                        'value' => (string) $division->id,
                        'label' => $division->name,
                    ])->values()->all(),
                ],
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

        <x-ui.data-table :columns="$columns" :paginator="$departments" :sort="$sort" :direction="$direction" :empty="__('No departments found.')" :exclude-params="array_keys($departmentFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$departmentFilters" />
            </x-slot:filters>

            @foreach ($departments as $department)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $department->code }}</td>
                    <td class="px-4 py-3 font-medium">{{ $department->name }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $department->division?->name ?? __('—') }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($department->org_units_count) }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($department->employees_count) }}</td>
                    <td class="px-4 py-3">
                        @if ($department->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('departments.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('departments.edit', $department) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('departments.delete')
                                <form method="POST" action="{{ route('departments.destroy', $department) }}"
                                    onsubmit="return confirm('{{ __('Delete this department?') }}')">
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
