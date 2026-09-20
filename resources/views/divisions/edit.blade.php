<x-app-layout :title="__('Edit division')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('divisions._form', ['division' => $division])
    </div>
</x-app-layout>
