<x-app-layout :title="__('Edit unit')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('units._form', ['unit' => $unit])
    </div>
</x-app-layout>
