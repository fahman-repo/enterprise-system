<x-app-layout :title="__('Edit education level')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('education-levels._form', ['educationLevel' => $educationLevel])
    </div>
</x-app-layout>
