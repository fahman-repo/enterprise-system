<x-app-layout :title="__('Positions')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Positions') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the job titles employees can hold.') }}</p>
            </div>

            @can('positions.create')
                <x-ui.button href="{{ route('positions.create') }}">
                    {{ __('New position') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'code', 'label' => __('Code'), 'sortable' => true],
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'department', 'label' => __('Department'), 'sortable' => true],
                ['key' => 'employees', 'label' => __('Employees'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$positions" :sort="$sort" :direction="$direction" :empty="__('No positions found.')">
            <x-slot:filters>
                <x-ui.select
                    name="department_id"
                    class="w-56"
                    aria-label="{{ __('Department') }}"
                    x-data
                    @change="$el.form.requestSubmit()"
                >
                    <option value="">{{ __('All departments') }}</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select
                    name="status"
                    class="w-36"
                    aria-label="{{ __('Status') }}"
                    x-data
                    @change="$el.form.requestSubmit()"
                >
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
                </x-ui.select>
            </x-slot:filters>

            @foreach ($positions as $position)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $position->code }}</td>
                    <td class="px-4 py-3 font-medium">{{ $position->name }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $position->department?->name ?? __('—') }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($position->employees_count) }}</td>
                    <td class="px-4 py-3">
                        @if ($position->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('positions.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('positions.edit', $position) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('positions.delete')
                                <form method="POST" action="{{ route('positions.destroy', $position) }}"
                                    onsubmit="return confirm('{{ __('Delete this position?') }}')">
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
