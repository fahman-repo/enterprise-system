<x-app-layout :title="__('New employee')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('employees._form', ['employee' => null])
    </div>
</x-app-layout>
