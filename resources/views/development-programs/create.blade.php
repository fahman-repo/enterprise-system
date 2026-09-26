<x-app-layout :title="__('New program')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('development-programs._form', ['program' => null])
    </div>
</x-app-layout>
