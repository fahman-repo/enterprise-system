<x-app-layout :title="__('Change password')">
    <div class="mx-auto max-w-4xl">
        @include('partials.flash')

        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>{{ __('Change password') }}</x-ui.card-title>
                <x-ui.card-description>{{ __('Enter your current password and choose a new one.') }}</x-ui.card-description>
            </x-ui.card-header>

            <x-ui.card-content>
                <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
                    @csrf
                    @method('PUT')

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="current_password">{{ __('Current password') }}</x-ui.label>
                        <x-ui.input id="current_password" name="current_password" type="password" required autocomplete="current-password" placeholder="********" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="password">{{ __('New password') }}</x-ui.label>
                        <x-ui.input id="password" name="password" type="password" required autocomplete="new-password" placeholder="********" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="password_confirmation">{{ __('Confirm new password') }}</x-ui.label>
                        <x-ui.input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="********" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <x-ui.button variant="outline" href="{{ route('dashboard') }}" type="button">
                            {{ __('Cancel') }}
                        </x-ui.button>
                        <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card-content>
        </x-ui.card>
    </div>
</x-app-layout>