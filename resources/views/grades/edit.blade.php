<x-app-layout :title="__('Edit grade')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('grades._form', ['grade' => $grade])
    </div>
</x-app-layout>
