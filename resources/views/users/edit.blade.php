<x-app-layout :title="__('Edit user')">
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('Edit user') }}</h1>
            <p class="text-sm text-muted-foreground">{{ __('Update the account details below.') }}</p>
        </div>

        @include('partials.flash')

        @include('users._form', ['user' => $user, 'roles' => $roles])
    </div>
</x-app-layout>
