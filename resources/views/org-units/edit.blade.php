<x-app-layout :title="__('Edit org unit')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('org-units._form', ['orgUnit' => $orgUnit])
    </div>
</x-app-layout>
