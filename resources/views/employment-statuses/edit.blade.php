<x-app-layout :title="__('Edit employment status')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('employment-statuses._form', ['status' => $status])
    </div>
</x-app-layout>
