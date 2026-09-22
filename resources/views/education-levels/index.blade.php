<x-app-layout :title="__('Education Levels')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Education Levels') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the formal education levels available on employee records.') }}</p>
            </div>

            @can('education-levels.create')
                <x-ui.button href="{{ route('education-levels.create') }}">
                    {{ __('New education level') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'level', 'label' => __('Level'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'sort_order', 'label' => __('Sort'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'employees', 'label' => __('Employees'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];

            $educationLevelFilters = [
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

        <x-ui.data-table :columns="$columns" :paginator="$educationLevels" :sort="$sort" :direction="$direction" :empty="__('No education levels found.')" :exclude-params="array_keys($educationLevelFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$educationLevelFilters" />
            </x-slot:filters>
            @foreach ($educationLevels as $educationLevel)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-medium">{{ $educationLevel->name }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ $educationLevel->level }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ $educationLevel->sort_order }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($educationLevel->employees_count) }}</td>
                    <td class="px-4 py-3">
                        @if ($educationLevel->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('education-levels.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('education-levels.edit', $educationLevel) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('education-levels.delete')
                                <form method="POST" action="{{ route('education-levels.destroy', $educationLevel) }}"
                                    onsubmit="return confirm('{{ __('Delete this education level?') }}')">
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
