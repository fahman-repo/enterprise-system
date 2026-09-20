<x-app-layout :title="__('New product')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('products._form', ['product' => null])
    </div>
</x-app-layout>
