<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $user ? __('Edit user') : __('New user') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ $user ? __('Update the account details below.') : __('Create an account for a new team member.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $user ? route('users.update', $user) : route('users.store') }}"
            class="flex flex-col gap-4"
        >
            @csrf
            @if ($user)
                @method('PUT')
            @endif

            <div class="flex flex-col gap-2">
                <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                <x-ui.input id="name" name="name" :value="old('name', $user?->name)" required autofocus autocomplete="name" />
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="email">{{ __('Email') }}</x-ui.label>
                <x-ui.input id="email" name="email" type="email" :value="old('email', $user?->email)" required autocomplete="email" />
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="password">{{ __('Password') }}</x-ui.label>
                <x-ui.input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    @unless ($user)
                        required
                    @endunless
                    :placeholder="$user ? __('Leave blank to keep the current password') : '********'"
                />
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="role_id">{{ __('Role') }}</x-ui.label>
                <x-ui.select id="role_id" name="role_id">
                    <option value="">{{ __('No role') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) old('role_id', $user?->role_id) === (string) $role->id)>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </x-ui.select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('users.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
