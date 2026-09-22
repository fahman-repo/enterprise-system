<x-app-layout :title="__('Employment Statuses')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Employment Statuses') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the employment types employees can be hired under.') }}</p>
            </div>

            @can('employment-statuses.create')
                <x-ui.button href="{{ route('employment-statuses.create') }}">
                    {{ __('New status') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'code', 'label' => __('Code'), 'sortable' => true],
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'employees', 'label' => __('Employees'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'sort_order', 'label' => __('Sort'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];

            $statusFilters = [
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

        <x-ui.data-table :columns="$columns" :paginator="$statuses" :sort="$sort" :direction="$direction" :empty="__('No employment statuses found.')" :exclude-params="array_keys($statusFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$statusFilters" />
            </x-slot:filters>
            @foreach ($statuses as $status)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $status->code }}</td>
                    <td class="px-4 py-3 font-medium">{{ $status->name }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($status->employees_count) }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ $status->sort_order }}</td>
                    <td class="px-4 py-3">
                        @if ($status->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('employment-statuses.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('employment-statuses.edit', $status) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('employment-statuses.delete')
                                <form method="POST" action="{{ route('employment-statuses.destroy', $status) }}"
                                    onsubmit="return confirm('{{ __('Delete this employment status?') }}')">
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
