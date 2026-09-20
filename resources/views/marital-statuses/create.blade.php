<x-app-layout :title="__('New marital status')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('marital-statuses._form', ['maritalStatus' => null])
    </div>
</x-app-layout>
