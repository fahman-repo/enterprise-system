<x-app-layout :title="__('New org unit')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('org-units._form', ['orgUnit' => null])
    </div>
</x-app-layout>
