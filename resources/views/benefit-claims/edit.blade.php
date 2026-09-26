<x-app-layout :title="__('Edit claim')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('benefit-claims._form', ['claim' => $claim])
    </div>
</x-app-layout>
