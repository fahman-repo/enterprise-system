<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $religion ? __('Edit religion') : __('New religion') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Religions appear on employee personal records.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $religion ? route('religions.update', $religion) : route('religions.store') }}"
            class="flex flex-col gap-4"
        >
            @csrf
            @if ($religion)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                    <x-ui.input id="name" name="name" :value="old('name', $religion?->name)" required autofocus />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="sort_order">{{ __('Sort order') }}</x-ui.label>
                    <x-ui.input id="sort_order" name="sort_order" type="number" min="0" :value="old('sort_order', $religion?->sort_order ?? 0)" />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $religion?->is_active ?? true)" />
                {{ __('Active') }}
            </label>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('religions.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
