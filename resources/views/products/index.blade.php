<x-app-layout :title="__('Products')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">{{ __('Products') }}</h1>
                <p class="text-sm text-muted-foreground">{{ __('Manage the product and item master records.') }}</p>
            </div>

            @can('products.create')
                <x-ui.button href="{{ route('products.create') }}">
                    {{ __('New product') }}
                </x-ui.button>
            @endcan
        </div>

        @include('partials.flash')

        @php
            $summaryLinkParams = collect(request()->only(['search', 'sort', 'direction', 'per_page']))
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->all();
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a
                href="{{ route('products.index', $summaryLinkParams) }}"
                class="block rounded-xl transition-transform hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            >
                <x-stat-card :label="__('Total products')" :value="number_format($summary['total_products'])" />
            </a>

            <a
                href="{{ route('products.index', array_merge($summaryLinkParams, ['status' => 'active'])) }}"
                class="block rounded-xl transition-transform hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            >
                <x-stat-card :label="__('Active products')" :value="number_format($summary['active_products'])" />
            </a>

            <a
                href="{{ route('products.index', array_merge($summaryLinkParams, ['stock' => 'low'])) }}"
                class="block rounded-xl transition-transform hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            >
                <x-stat-card :label="__('Low stock')" :value="number_format($summary['low_stock_products'])" />
            </a>

            <a
                href="{{ route('products.index', array_merge($summaryLinkParams, ['stock' => 'out'])) }}"
                class="block rounded-xl transition-transform hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            >
                <x-stat-card :label="__('Out of stock')" :value="number_format($summary['out_of_stock_products'])" />
            </a>
        </div>

        @php
            $columns = [
                ['key' => 'name', 'label' => __('Product'), 'sortable' => true],
                ['key' => 'category', 'label' => __('Category'), 'sortable' => true],
                ['key' => 'brand', 'label' => __('Brand'), 'sortable' => true],
                ['key' => 'selling_price', 'label' => __('Price'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'stock_quantity', 'label' => __('Stock'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];

            $productFilters = [
                'category_id' => [
                    'label' => __('Category'),
                    'all' => __('All categories'),
                    'options' => $categories->map(fn ($option) => [
                        'value' => (string) $option['category']->id,
                        'label' => str_repeat('— ', $option['depth']).$option['category']->name,
                    ])->values()->all(),
                ],
                'brand_id' => [
                    'label' => __('Brand'),
                    'all' => __('All brands'),
                    'options' => $brands->map(fn ($brand) => [
                        'value' => (string) $brand->id,
                        'label' => $brand->name,
                    ])->values()->all(),
                ],
                'status' => [
                    'label' => __('Status'),
                    'all' => __('All statuses'),
                    'options' => [
                        ['value' => 'active', 'label' => __('Active')],
                        ['value' => 'inactive', 'label' => __('Inactive')],
                    ],
                ],
                'stock' => [
                    'label' => __('Stock'),
                    'all' => __('All stock'),
                    'options' => [
                        ['value' => 'low', 'label' => __('Low stock')],
                        ['value' => 'out', 'label' => __('Out of stock')],
                    ],
                ],
            ];
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$products" :sort="$sort" :direction="$direction" :empty="__('No products found.')" :exclude-params="array_keys($productFilters)">
            <x-slot:filters>
                <x-ui.filter-bar :filters="$productFilters" />
            </x-slot:filters>

            @foreach ($products as $product)
                <tr class="hover:bg-accent/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            @if ($product->image_path)
                                <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="size-9 rounded-md border object-cover">
                            @else
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-md border bg-muted text-muted-foreground">
                                    <x-icon.package />
                                </span>
                            @endif

                            <div class="flex flex-col">
                                <span class="font-medium">{{ $product->name }}</span>
                                <span class="text-xs text-muted-foreground">{{ $product->sku }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $product->category?->name ?? __('—') }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ $product->brand?->name ?? __('—') }}</td>
                    <td class="px-4 py-3 text-right text-muted-foreground">{{ number_format((float) $product->selling_price, 2) }}</td>
                    <td class="px-4 py-3 text-right">
                        @if (! $product->track_stock)
                            <x-ui.badge variant="muted">{{ __('Not tracked') }}</x-ui.badge>
                        @elseif ($product->isOutOfStock())
                            <x-ui.badge variant="destructive">{{ __('Out of stock') }}</x-ui.badge>
                        @else
                            <span class="text-muted-foreground">
                                {{ $product->formattedStockQuantity() }} {{ $product->unit?->abbreviation }}
                            </span>

                            @if ($product->isLowStock())
                                <x-ui.badge variant="outline">{{ __('Low') }}</x-ui.badge>
                            @endif
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if ($product->is_active)
                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @can('products.update')
                                <x-ui.button variant="outline" size="sm" href="{{ route('products.edit', $product) }}">
                                    {{ __('Edit') }}
                                </x-ui.button>
                            @endcan

                            @can('products.delete')
                                <form method="POST" action="{{ route('products.destroy', $product) }}"
                                    onsubmit="return confirm('{{ __('Delete this product?') }}')">
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
