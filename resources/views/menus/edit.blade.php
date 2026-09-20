<x-app-layout :title="__('Edit menu item')">
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('Edit menu item') }}: {{ $menu->name }}</h1>
            <p class="text-sm text-muted-foreground">{{ __('Update the sidebar entry below.') }}</p>
        </div>

        @include('partials.flash')

        @include('menus._form', ['menu' => $menu, 'parentOptions' => $parentOptions])
    </div>
</x-app-layout>
