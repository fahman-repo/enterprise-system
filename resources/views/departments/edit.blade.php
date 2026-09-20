<x-app-layout :title="__('Edit department')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('departments._form', ['department' => $department])
    </div>
</x-app-layout>
