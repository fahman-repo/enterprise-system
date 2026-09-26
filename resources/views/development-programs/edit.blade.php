<x-app-layout :title="__('Edit program')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('development-programs._form', ['program' => $program])
    </div>
</x-app-layout>
