<x-app-layout :title="__('Brands')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Brands') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the brands products can be assigned to.') }}</p>
            </div>

            @can('brands.create')
                <x-ui.button href="{{ route('brands.create') }}">
                    {{ __('New brand') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $columns = [
                ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
                ['key' => 'slug', 'label' => __('Slug'), 'sortable' => true],
                ['key' => 'products', 'label' => __('Products'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];

            $brandFilters = [
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

        <x-ui.data-table :columns="$columns" :paginator="$brands" :sort="$sort" :direction="$direction" :empty="__('No brands found.')" :exclude-params="array_keys($brandFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$brandFilters" />
            </x-slot:filters>
            @foreach ($brands as $brand)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3 font-medium">{{ $brand->name }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $brand->slug }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format($brand->products_count) }}</td>
                    <td class="px-4 py-3">
                        @if ($brand->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('brands.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('brands.edit', $brand) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('brands.delete')
                                <form method="POST" action="{{ route('brands.destroy', $brand) }}"
                                    onsubmit="return confirm('{{ __('Delete this brand?') }}')">
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
