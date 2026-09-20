<x-app-layout :title="__('Edit employee')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('employees._form', ['employee' => $employee])
    </div>
</x-app-layout>
