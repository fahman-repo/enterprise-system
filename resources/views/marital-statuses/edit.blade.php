<x-app-layout :title="__('Edit marital status')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('marital-statuses._form', ['maritalStatus' => $maritalStatus])
    </div>
</x-app-layout>
