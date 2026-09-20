<x-auth-layout>
    <x-ui.card>
        <x-ui.card-header>
            <x-ui.card-title>{{ __('Log in') }}</x-ui.card-title>
            <x-ui.card-description>{{ __('Enter your email and password to continue.') }}</x-ui.card-description>
        </x-ui.card-header>

        <x-ui.card-content>
            <div class="flex flex-col gap-4">
                @if (session('status'))
                    <x-ui.alert>{{ session('status') }}</x-ui.alert>
                @endif

                @if ($errors->any())
                    <x-ui.alert variant="destructive">
                        <x-icon.alert-circle />
                        <div class="flex flex-col gap-1">
                            <p class="font-medium">{{ __('Could not sign you in.') }}</p>
                            <ul class="list-inside list-disc text-xs">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </x-ui.alert>
                @endif

                <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
                    @csrf

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="email">{{ __('Email') }}</x-ui.label>
                        <x-ui.input id="email" name="email" type="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="password">{{ __('Password') }}</x-ui.label>
                        <x-ui.input id="password" name="password" type="password" required autocomplete="current-password" placeholder="********" />
                    </div>

                    <div class="flex items-center gap-2">
                        <x-ui.checkbox id="remember_me" name="remember" />
                        <x-ui.label for="remember_me" class="text-muted-foreground">{{ __('Remember me') }}</x-ui.label>
                    </div>

                    <x-ui.button type="submit" class="w-full">{{ __('Log in') }}</x-ui.button>
                </form>
            </div>
        </x-ui.card-content>
    </x-ui.card>
</x-auth-layout>