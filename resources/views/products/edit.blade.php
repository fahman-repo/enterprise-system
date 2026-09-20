<x-app-layout :title="__('Edit product')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('products._form', ['product' => $product])
    </div>
</x-app-layout>
