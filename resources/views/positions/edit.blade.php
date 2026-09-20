<x-app-layout :title="__('Edit position')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('positions._form', ['position' => $position])
    </div>
</x-app-layout>
