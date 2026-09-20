<x-app-layout :title="__('New role')">
    <div class="mx-auto flex max-w-4xl flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('New role') }}</h1>
            <p class="text-sm text-muted-foreground">{{ __('Define a role and pick what it may do in each module.') }}</p>
        </div>

        @include('partials.flash')

        @include('roles._form', ['role' => null, 'menus' => $menus])
    </div>
</x-app-layout>
