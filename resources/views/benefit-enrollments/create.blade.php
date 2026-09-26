<x-app-layout :title="__('New enrollment')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('benefit-enrollments._form', ['enrollment' => null])
    </div>
</x-app-layout>
