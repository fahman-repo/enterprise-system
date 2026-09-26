<x-app-layout :title="__('New site')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('sites._form', ['site' => null, 'parentOptions' => $parentOptions])
    </div>
</x-app-layout>