<x-app-layout :title="__('Edit entity')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('entities._form', ['entity' => $entity])
    </div>
</x-app-layout>
