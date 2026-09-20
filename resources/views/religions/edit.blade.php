<x-app-layout :title="__('Edit religion')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('religions._form', ['religion' => $religion])
    </div>
</x-app-layout>
