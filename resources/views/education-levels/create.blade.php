<x-app-layout :title="__('New education level')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('education-levels._form', ['educationLevel' => null])
    </div>
</x-app-layout>
