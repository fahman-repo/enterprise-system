<x-app-layout :title="__('Edit enrollment')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('benefit-enrollments._form', ['enrollment' => $enrollment])
    </div>
</x-app-layout>
