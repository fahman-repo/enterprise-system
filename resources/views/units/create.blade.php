<x-app-layout :title="__('New unit')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('units._form', ['unit' => null])
    </div>
</x-app-layout>
