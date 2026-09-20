<x-app-layout :title="__('New religion')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('religions._form', ['religion' => null])
    </div>
</x-app-layout>
