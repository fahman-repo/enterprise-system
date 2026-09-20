<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $educationLevel ? __('Edit education level') : __('New education level') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Levels order qualifications from the lowest to the highest.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $educationLevel ? route('education-levels.update', $educationLevel) : route('education-levels.store') }}"
            class="flex flex-col gap-4"
        >
            @csrf
            @if ($educationLevel)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="flex flex-col gap-2 sm:col-span-2">
                    <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                    <x-ui.input id="name" name="name" :value="old('name', $educationLevel?->name)" required autofocus />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="level">{{ __('Level') }}</x-ui.label>
                    <x-ui.input id="level" name="level" type="number" min="0" :value="old('level', $educationLevel?->level ?? 0)" required />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="sort_order">{{ __('Sort order') }}</x-ui.label>
                    <x-ui.input id="sort_order" name="sort_order" type="number" min="0" :value="old('sort_order', $educationLevel?->sort_order ?? 0)" />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $educationLevel?->is_active ?? true)" />
                {{ __('Active') }}
            </label>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('education-levels.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
