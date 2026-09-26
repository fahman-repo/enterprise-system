<x-app-layout :title="__('New claim')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('benefit-claims._form', ['claim' => null])
    </div>
</x-app-layout>
