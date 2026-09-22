<x-app-layout :title="__('Menus')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Menus') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Build the sidebar navigation and its module permissions.') }}</p>
            </div>

            @can('menus.create')
                <x-ui.button href="{{ route('menus.create') }}">
                    {{ __('New menu item') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'slug', 'label' => __('Slug'), 'sortable' => true],
                ['key' => 'parent', 'label' => __('Parent'), 'sortable' => true],
                ['key' => 'route_name', 'label' => __('Route'), 'sortable' => true],
                ['key' => 'sort_order', 'label' => __('Sort'), 'sortable' => true],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];

            $menuFilters = [
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

        <x-ui.data-table :columns="$columns" :paginator="$menus" :sort="$sort" :direction="$direction" :empty="__('No menus found.')" :exclude-params="array_keys($menuFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$menuFilters" />
            </x-slot:filters>
            @foreach ($menus as $menu)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-medium">
                        <span class="inline-flex items-center gap-2">
                            @if ($menu->icon)
                                <x-dynamic-component :component="'icon.'.$menu->icon" class="size-4 text-muted-foreground" />
                            @endif
                            {{ $menu->name }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $menu->slug }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $menu->parent?->name ?? __('—') }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $menu->route_name ?? __('—') }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $menu->sort_order }}</td>
                    <td class="px-4 py-3">
                        @if ($menu->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('menus.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('menus.edit', $menu) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('menus.delete')
                                <form method="POST" action="{{ route('menus.destroy', $menu) }}"
                                    onsubmit="return confirm('{{ __('Delete this menu item? Its children move to the top level.') }}')">
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
