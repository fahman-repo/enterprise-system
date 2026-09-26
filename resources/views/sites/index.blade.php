<x-app-layout :title="__('Sites')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Sites') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the company, buildings, branches, warehouses, workshops and factories.') }}</p>
            </div>

            @can('sites.create')
                <x-ui.button href="{{ route('sites.create') }}">
                    {{ __('New site') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $typeOptions = [
                ['value' => 'company', 'label' => __('Company')],
                ['value' => 'building', 'label' => __('Building')],
                ['value' => 'branch', 'label' => __('Branch')],
                ['value' => 'warehouse', 'label' => __('Warehouse')],
                ['value' => 'workshop', 'label' => __('Workshop')],
                ['value' => 'factory', 'label' => __('Factory')],
            ];

            $siteFilters = [
                'type' => [
                    'label' => __('Type'),
                    'all' => __('All types'),
                    'options' => $typeOptions,
                ],
                'parent_id' => [
                    'label' => __('Parent site'),
                    'all' => __('All parents'),
                    'options' => $parents->map(fn ($parent): array => [
                        'value' => (string) $parent->id,
                        'label' => $parent->name,
                    ])->all(),
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

            $columns = [
                ['key' => 'code', 'label' => __('Code'), 'sortable' => true],
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'type', 'label' => __('Type'), 'sortable' => true],
                ['key' => 'parent', 'label' => __('Parent'), 'sortable' => true],
                ['key' => 'city', 'label' => __('City'), 'sortable' => true],
                ['key' => 'employees', 'label' => __('Employees'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$sites" :sort="$sort" :direction="$direction" :empty="__('No sites found.')" :exclude-params="array_keys($siteFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$siteFilters" />
            </x-slot:filters>

            @foreach ($sites as $site)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $site->code }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('sites.show', $site) }}" class="font-medium hover:underline">{{ $site->name }}</a>
                    </td>
                    <td class="px-4 py-3">
                        <x-ui.badge variant="outline">{{ $site->typeLabel() }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">
                        @if ($site->parent)
                            <a href="{{ route('sites.show', $site->parent) }}" class="hover:underline">{{ $site->parent->name }}</a>
                        @else
                            {{ __('—') }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $site->city ?? __('—') }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($site->employees_count) }}</td>
                    <td class="px-4 py-3">
                        @if ($site->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <x-ui.button variant="outline" size="sm" href="{{ route('sites.show', $site) }}">
                                {{ __('View') }}
                            </x-ui.button>

                            @can('sites.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('sites.edit', $site) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('sites.delete')
                                <form method="POST" action="{{ route('sites.destroy', $site) }}"
                                    onsubmit="return confirm('{{ __('Delete this site?') }}')">
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