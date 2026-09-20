<x-app-layout :title="__('New department')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('departments._form', ['department' => null])
    </div>
</x-app-layout>
