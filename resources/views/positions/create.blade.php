<x-app-layout :title="__('New position')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('positions._form', ['position' => null])
    </div>
</x-app-layout>
