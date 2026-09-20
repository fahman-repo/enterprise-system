<x-app-layout :title="__('New menu item')">
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('New menu item') }}</h1>
            <p class="text-sm text-muted-foreground">{{ __('Add a sidebar entry, optionally nested under a parent.') }}</p>
        </div>

        @include('partials.flash')

        @include('menus._form', ['menu' => null, 'parentOptions' => $parentOptions])
    </div>
</x-app-layout>
