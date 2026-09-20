<x-app-layout :title="__('Edit work location')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('work-locations._form', ['location' => $location])
    </div>
</x-app-layout>
