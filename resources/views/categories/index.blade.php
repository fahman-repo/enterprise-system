<x-app-layout :title="__('Categories')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Categories') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Organize products into a category tree.') }}</p>
            </div>

            @can('categories.create')
                <x-ui.button href="{{ route('categories.create') }}">
                    {{ __('New category') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'parent', 'label' => __('Parent'), 'sortable' => true],
                ['key' => 'slug', 'label' => __('Slug'), 'sortable' => true],
                ['key' => 'products', 'label' => __('Products'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'sort_order', 'label' => __('Sort'), 'sortable' => true],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];

            $categoryFilters = [
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

        <x-ui.data-table :columns="$columns" :paginator="$categories" :sort="$sort" :direction="$direction" :empty="__('No categories found.')" :exclude-params="array_keys($categoryFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$categoryFilters" />
            </x-slot:filters>
            @foreach ($categories as $category)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $category->parent?->name ?? __('—') }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $category->slug }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($category->products_count) }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $category->sort_order }}</td>
                    <td class="px-4 py-3">
                        @if ($category->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('categories.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('categories.edit', $category) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('categories.delete')
                                <form method="POST" action="{{ route('categories.destroy', $category) }}"
                                    onsubmit="return confirm('{{ __('Delete this category? Child categories move up one level.') }}')">
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
