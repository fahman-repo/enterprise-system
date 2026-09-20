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
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$employees" :sort="$sort" :direction="$direction" :empty="__('No employees found.')">
            <x-slot:filters>
                <x-ui.select name="division_id" class="w-44" aria-label="{{ __('Division') }}" x-data @change="$el.form.requestSubmit()">
                    <option value="">{{ __('All divisions') }}</option>
                    @foreach ($divisions as $division)
                        <option value="{{ $division->id }}" @selected((string) request('division_id') === (string) $division->id)>{{ $division->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="department_id" class="w-48" aria-label="{{ __('Department') }}" x-data @change="$el.form.requestSubmit()">
                    <option value="">{{ __('All departments') }}</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="org_unit_id" class="w-44" aria-label="{{ __('Org unit') }}" x-data @change="$el.form.requestSubmit()">
                    <option value="">{{ __('All org units') }}</option>
                    @foreach ($orgUnits as $orgUnit)
                        <option value="{{ $orgUnit->id }}" @selected((string) request('org_unit_id') === (string) $orgUnit->id)>{{ $orgUnit->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="position_id" class="w-44" aria-label="{{ __('Position') }}" x-data @change="$el.form.requestSubmit()">
                    <option value="">{{ __('All positions') }}</option>
                    @foreach ($positions as $position)
                        <option value="{{ $position->id }}" @selected((string) request('position_id') === (string) $position->id)>{{ $position->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="employment_status_id" class="w-44" aria-label="{{ __('Employment status') }}" x-data @change="$el.form.requestSubmit()">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($employmentStatuses as $status)
                        <option value="{{ $status->id }}" @selected((string) request('employment_status_id') === (string) $status->id)>{{ $status->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="work_location_id" class="w-44" aria-label="{{ __('Work location') }}" x-data @change="$el.form.requestSubmit()">
                    <option value="">{{ __('All locations') }}</option>
                    @foreach ($workLocations as $location)
                        <option value="{{ $location->id }}" @selected((string) request('work_location_id') === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="status" class="w-36" aria-label="{{ __('Status') }}" x-data @change="$el.form.requestSubmit()">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
                </x-ui.select>
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
