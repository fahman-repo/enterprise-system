<x-app-layout :title="__('New entity')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('entities._form', ['entity' => null])
    </div>
</x-app-layout>
