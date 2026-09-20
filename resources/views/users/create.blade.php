<x-app-layout :title="__('New user')">
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('New user') }}</h1>
            <p class="text-sm text-muted-foreground">{{ __('Create an account for a new team member.') }}</p>
        </div>

        @include('partials.flash')

        @include('users._form', ['user' => null, 'roles' => $roles])
    </div>
</x-app-layout>
