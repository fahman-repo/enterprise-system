<x-app-layout :title="__('New employment status')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('employment-statuses._form', ['status' => null])
    </div>
</x-app-layout>
