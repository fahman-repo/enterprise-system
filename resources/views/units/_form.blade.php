<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $unit ? __('Edit unit') : __('New unit') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Units define how product quantities are measured.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $unit ? route('units.update', $unit) : route('units.store') }}"
            class="flex flex-col gap-4"
        >
            @csrf
            @if ($unit)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                    <x-ui.input id="name" name="name" :value="old('name', $unit?->name)" required autofocus />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="abbreviation">{{ __('Abbreviation') }}</x-ui.label>
                    <x-ui.input id="abbreviation" name="abbreviation" :value="old('abbreviation', $unit?->abbreviation)" required />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="allows_decimal" value="0">
                <x-ui.checkbox name="allows_decimal" value="1" :checked="(bool) old('allows_decimal', $unit?->allows_decimal ?? false)" />
                {{ __('Allow decimal quantities (e.g. 0.5 kg)') }}
            </label>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $unit?->is_active ?? true)" />
                {{ __('Active') }}
            </label>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('units.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
