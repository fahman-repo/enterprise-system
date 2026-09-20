<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $product ? __('Edit product') : __('New product') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ $product ? __('Update the item master details below.') : __('Create a new product or item master record.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $product ? route('products.update', $product) : route('products.store') }}"
            enctype="multipart/form-data"
            class="flex flex-col gap-6"
        >
            @csrf
            @if ($product)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="sku">{{ __('SKU') }}</x-ui.label>
                    <x-ui.input id="sku" name="sku" :value="old('sku', $product?->sku)" required autofocus />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="barcode">{{ __('Barcode') }}</x-ui.label>
                    <x-ui.input id="barcode" name="barcode" :value="old('barcode', $product?->barcode)" />
                </div>

                <div class="flex flex-col gap-2 sm:col-span-2">
                    <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                    <x-ui.input id="name" name="name" :value="old('name', $product?->name)" required />
                </div>

                <div class="flex flex-col gap-2 sm:col-span-2">
                    <x-ui.label for="description">{{ __('Description') }}</x-ui.label>
                    <x-ui.textarea id="description" name="description" rows="3">{{ old('description', $product?->description) }}</x-ui.textarea>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="category_id">{{ __('Category') }}</x-ui.label>
                    <x-ui.select id="category_id" name="category_id">
                        <option value="">{{ __('No category') }}</option>
                        @foreach ($categories as $option)
                            <option value="{{ $option['category']->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $option['category']->id)>
                                {{ str_repeat('— ', $option['depth']).$option['category']->name }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="brand_id">{{ __('Brand') }}</x-ui.label>
                    <x-ui.select id="brand_id" name="brand_id">
                        <option value="">{{ __('No brand') }}</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product?->brand_id) === (string) $brand->id)>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="unit_id">{{ __('Unit') }}</x-ui.label>
                    <x-ui.select id="unit_id" name="unit_id">
                        <option value="">{{ __('No unit') }}</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected((string) old('unit_id', $product?->unit_id) === (string) $unit->id)>
                                {{ $unit->name }} ({{ $unit->abbreviation }})
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="cost_price">{{ __('Cost price') }}</x-ui.label>
                    <x-ui.input id="cost_price" name="cost_price" type="number" step="0.01" min="0" :value="old('cost_price', $product?->cost_price ?? '0.00')" required />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="selling_price">{{ __('Selling price') }}</x-ui.label>
                    <x-ui.input id="selling_price" name="selling_price" type="number" step="0.01" min="0" :value="old('selling_price', $product?->selling_price ?? '0.00')" required />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="tax_rate">{{ __('Tax rate (%)') }}</x-ui.label>
                    <x-ui.input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="100" :value="old('tax_rate', $product?->tax_rate ?? '0.00')" required />
                </div>
            </div>

            <div class="flex flex-col gap-4 rounded-lg border p-4">
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="hidden" name="track_stock" value="0">
                    <x-ui.checkbox name="track_stock" value="1" :checked="(bool) old('track_stock', $product?->track_stock ?? true)" />
                    {{ __('Track stock levels') }}
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="stock_quantity">{{ __('Stock quantity') }}</x-ui.label>
                        <x-ui.input id="stock_quantity" name="stock_quantity" type="number" step="0.001" min="0" :value="old('stock_quantity', $product?->stock_quantity ?? '0.000')" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="reorder_level">{{ __('Reorder level') }}</x-ui.label>
                        <x-ui.input id="reorder_level" name="reorder_level" type="number" step="0.001" min="0" :value="old('reorder_level', $product?->reorder_level)" />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="image">{{ __('Image') }}</x-ui.label>

                @if ($product?->image_path)
                    <div class="flex flex-wrap items-center gap-4">
                        <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="size-16 rounded-md border object-cover">

                        <label class="flex items-center gap-2 text-sm text-muted-foreground">
                            <x-ui.checkbox name="remove_image" value="1" />
                            {{ __('Remove current image') }}
                        </label>
                    </div>
                @endif

                <x-ui.file-input id="image" name="image" accept="image/jpeg,image/png,image/webp" />
                <p class="text-xs text-muted-foreground">{{ __('JPG, PNG or WebP up to 2 MB.') }}</p>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $product?->is_active ?? true)" />
                {{ __('Active') }}
            </label>

            <div class="flex items-center justify-end gap-2">
                <x-ui.button variant="outline" href="{{ route('products.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
