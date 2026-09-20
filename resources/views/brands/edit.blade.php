<x-app-layout :title="__('Edit brand')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('brands._form', ['brand' => $brand])
    </div>
</x-app-layout>
