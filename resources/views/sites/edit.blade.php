<x-app-layout :title="__('Edit site')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        @include('sites._form', ['site' => $site, 'parentOptions' => $parentOptions])
    </div>
</x-app-layout>