<x-app-layout :title="__('New brand')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('brands._form', ['brand' => null])
    </div>
</x-app-layout>
