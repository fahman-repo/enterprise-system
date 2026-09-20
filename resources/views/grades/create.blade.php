<x-app-layout :title="__('New grade')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('grades._form', ['grade' => null])
    </div>
</x-app-layout>
