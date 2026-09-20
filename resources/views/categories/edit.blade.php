<x-app-layout :title="__('Edit category')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('categories._form', ['category' => $category])
    </div>
</x-app-layout>
