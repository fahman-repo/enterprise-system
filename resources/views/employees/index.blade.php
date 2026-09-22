<x-app-layout :title="__('Employees')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Employees') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the employee master records and their placement.') }}</p>
            </div>

            @can('employees.create')
                <x-ui.button href="{{ route('employees.create') }}">
                    {{ __('New employee') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'name', 'label' => __('Employee'), 'sortable' => true],
                ['key' => 'department', 'label' => __('Department'), 'sortable' => true],
                ['key' => 'position', 'label' => __('Position'), 'sortable' => true],
                ['key' => 'employment_status', 'label' => __('Employment status'), 'sortable' => true],
                ['key' => 'join_date', 'label' => __('Join date'), 'sortable' => true],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];

            $toOptions = fn ($records) => $records->map(fn ($record) => [
                'value' => (string) $record->id,
                'label' => $record->name,
            ])->values()->all();

            $employeeFilters = [
                'division_id' => [
                    'label' => __('Division'),
                    'all' => __('All divisions'),
                    'options' => $toOptions($divisions),
                ],
                'department_id' => [
                    'label' => __('Department'),
                    'all' => __('All departments'),
                    'options' => $toOptions($departments),
                ],
                'org_unit_id' => [
                    'label' => __('Org unit'),
                    'all' => __('All org units'),
                    'options' => $toOptions($orgUnits),
                ],
                'position_id' => [
                    'label' => __('Position'),
                    'all' => __('All positions'),
                    'options' => $toOptions($positions),
                ],
                'employment_status_id' => [
                    'label' => __('Employment status'),
                    'all' => __('All statuses'),
                    'options' => $toOptions($employmentStatuses),
                ],
                'work_location_id' => [
                    'label' => __('Work location'),
                    'all' => __('All locations'),
                    'options' => $toOptions($workLocations),
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

        <x-ui.data-table :columns="$columns" :paginator="$employees" :sort="$sort" :direction="$direction" :empty="__('No employees found.')" :exclude-params="array_keys($employeeFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$employeeFilters" />
            </x-slot:filters>

            @foreach ($employees as $employee)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <x-ui.avatar :name="$employee->name" :src="$employee->photoUrl()" />

                            <div class="flex flex-col">
                                <a href="{{ route('employees.show', $employee) }}" class="font-medium hover:underline">{{ $employee->name }}</a>
                                <span class="font-mono text-xs text-muted-foreground">{{ $employee->employee_number }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">
                        {{ $employee->department?->name ?? __('—') }}
                        @if ($employee->division)
                            <span class="block text-xs">{{ $employee->division->name }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $employee->position?->name ?? __('—') }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $employee->employmentStatus?->name ?? __('—') }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $employee->join_date?->format('d M Y') ?? __('—') }}</td>
                    <td class="px-4 py-3">
                        @if ($employee->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <x-ui.button variant="outline" size="sm" href="{{ route('employees.show', $employee) }}">
                                {{ __('View') }}
                            </x-ui.button>

                            @can('employees.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('employees.edit', $employee) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('employees.delete')
                                <form method="POST" action="{{ route('employees.destroy', $employee) }}"
                                    onsubmit="return confirm('{{ __('Delete this employee?') }}')">
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
