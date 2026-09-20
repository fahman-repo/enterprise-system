<x-app-layout :title="__('Work Locations')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Work Locations') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the offices, branches and sites employees report to.') }}</p>
            </div>

            @can('work-locations.create')
                <x-ui.button href="{{ route('work-locations.create') }}">
                    {{ __('New work location') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'code', 'label' => __('Code'), 'sortable' => true],
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'city', 'label' => __('City'), 'sortable' => true],
                ['key' => 'province', 'label' => __('Province'), 'sortable' => true],
                ['key' => 'employees', 'label' => __('Employees'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$locations" :sort="$sort" :direction="$direction" :empty="__('No work locations found.')">
            <x-slot:filters>
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

            @foreach ($locations as $location)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $location->code }}</td>
                    <td class="px-4 py-3 font-medium">{{ $location->name }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $location->city ?? __('—') }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $location->province ?? __('—') }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($location->employees_count) }}</td>
                    <td class="px-4 py-3">
                        @if ($location->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('work-locations.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('work-locations.edit', $location) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('work-locations.delete')
                                <form method="POST" action="{{ route('work-locations.destroy', $location) }}"
                                    onsubmit="return confirm('{{ __('Delete this work location?') }}')">
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
