<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $location ? __('Edit work location') : __('New work location') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Work locations are the physical sites employees are assigned to.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $location ? route('work-locations.update', $location) : route('work-locations.store') }}"
            class="flex flex-col gap-4"
        >
            @csrf
            @if ($location)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="code">{{ __('Code') }}</x-ui.label>
                    <x-ui.input id="code" name="code" :value="old('code', $location?->code)" required autofocus />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                    <x-ui.input id="name" name="name" :value="old('name', $location?->name)" required />
                </div>

                <div class="flex flex-col gap-2 sm:col-span-2">
                    <x-ui.label for="address">{{ __('Address') }}</x-ui.label>
                    <x-ui.textarea id="address" name="address" rows="2">{{ old('address', $location?->address) }}</x-ui.textarea>
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="city">{{ __('City') }}</x-ui.label>
                    <x-ui.input id="city" name="city" :value="old('city', $location?->city)" />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="province">{{ __('Province') }}</x-ui.label>
                    <x-ui.input id="province" name="province" :value="old('province', $location?->province)" />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="postal_code">{{ __('Postal code') }}</x-ui.label>
                    <x-ui.input id="postal_code" name="postal_code" :value="old('postal_code', $location?->postal_code)" />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="phone">{{ __('Phone') }}</x-ui.label>
                    <x-ui.input id="phone" name="phone" :value="old('phone', $location?->phone)" />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $location?->is_active ?? true)" />
                {{ __('Active') }}
            </label>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('work-locations.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
