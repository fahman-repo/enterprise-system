<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $status ? __('Edit employment status') : __('New employment status') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Employment statuses describe the contract type, such as permanent or contract.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $status ? route('employment-statuses.update', $status) : route('employment-statuses.store') }}"
            class="flex flex-col gap-4"
        >
            @csrf
            @if ($status)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="code">{{ __('Code') }}</x-ui.label>
                    <x-ui.input id="code" name="code" :value="old('code', $status?->code)" required autofocus />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                    <x-ui.input id="name" name="name" :value="old('name', $status?->name)" required />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="sort_order">{{ __('Sort order') }}</x-ui.label>
                    <x-ui.input id="sort_order" name="sort_order" type="number" min="0" :value="old('sort_order', $status?->sort_order ?? 0)" />
                </div>

                <div class="flex items-end pb-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $status?->is_active ?? true)" />
                        {{ __('Active') }}
                    </label>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="description">{{ __('Description') }}</x-ui.label>
                <x-ui.textarea id="description" name="description" rows="3">{{ old('description', $status?->description) }}</x-ui.textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('employment-statuses.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
