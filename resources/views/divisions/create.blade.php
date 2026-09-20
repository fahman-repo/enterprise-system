<x-app-layout :title="__('New division')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('divisions._form', ['division' => null])
    </div>
</x-app-layout>
