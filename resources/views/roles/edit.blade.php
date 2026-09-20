<x-app-layout :title="__('Edit role')">
    <div class="mx-auto flex max-w-4xl flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('Edit role') }}: {{ $role->name }}</h1>
            <p class="text-sm text-muted-foreground">{{ __('Update the details and permission matrix below.') }}</p>
        </div>

        @include('partials.flash')

        @include('roles._form', ['role' => $role, 'menus' => $menus])
    </div>
</x-app-layout>
