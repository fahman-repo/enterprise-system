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
            $columns = [
                ['key' => 'name', 'label' => __('Product'), 'sortable' => true],
                ['key' => 'category', 'label' => __('Category'), 'sortable' => true],
                ['key' => 'brand', 'label' => __('Brand'), 'sortable' => true],
                ['key' => 'selling_price', 'label' => __('Price'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'stock_quantity', 'label' => __('Stock'), 'sortable' => true, 'align' => 'right'],
                ['key' => 'is_active', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'actions', 'label' => __('Actions'), 'sortable' => false, 'align' => 'right'],
            ];
        @endphp

        <x-ui.data-table :columns="$columns" :paginator="$products" :sort="$sort" :direction="$direction" :empty="__('No products found.')">
            <x-slot:filters>
                <x-ui.select
                    name="category_id"
                    class="w-44"
                    aria-label="{{ __('Category') }}"
                    x-data
                    @change="$el.form.requestSubmit()"
                >
                    <option value="">{{ __('All categories') }}</option>
                    @foreach ($categories as $option)
                        <option value="{{ $option['category']->id }}" @selected((string) request('category_id') === (string) $option['category']->id)>
                            {{ str_repeat('— ', $option['depth']).$option['category']->name }}
                        </option>
                    @endforeach
                </x-ui.select>

                <x-ui.select
                    name="brand_id"
                    class="w-40"
                    aria-label="{{ __('Brand') }}"
                    x-data
                    @change="$el.form.requestSubmit()"
                >
                    <option value="">{{ __('All brands') }}</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((string) request('brand_id') === (string) $brand->id)>{{ $brand->name }}</option>
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

                <x-ui.select
                    name="stock"
                    class="w-36"
                    aria-label="{{ __('Stock') }}"
                    x-data
                    @change="$el.form.requestSubmit()"
                >
                    <option value="">{{ __('All stock') }}</option>
                    <option value="low" @selected(request('stock') === 'low')>{{ __('Low stock') }}</option>
                    <option value="out" @selected(request('stock') === 'out')>{{ __('Out of stock') }}</option>
                </x-ui.select>
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
