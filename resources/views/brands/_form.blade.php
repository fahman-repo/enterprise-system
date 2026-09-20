<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $brand ? __('Edit brand') : __('New brand') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Brands group products by manufacturer or label.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $brand ? route('brands.update', $brand) : route('brands.store') }}"
            class="flex flex-col gap-4"
        >
            @csrf
            @if ($brand)
                @method('PUT')
            @endif

            <div class="flex flex-col gap-2">
                <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                <x-ui.input id="name" name="name" :value="old('name', $brand?->name)" required autofocus />
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="slug">{{ __('Slug') }}</x-ui.label>
                <x-ui.input id="slug" name="slug" :value="old('slug', $brand?->slug)" required />
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="description">{{ __('Description') }}</x-ui.label>
                <x-ui.textarea id="description" name="description" rows="3">{{ old('description', $brand?->description) }}</x-ui.textarea>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $brand?->is_active ?? true)" />
                {{ __('Active') }}
            </label>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('brands.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
