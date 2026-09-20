<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $category ? __('Edit category') : __('New category') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Categories can be nested to build a product hierarchy.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $category ? route('categories.update', $category) : route('categories.store') }}"
            class="flex flex-col gap-4"
        >
            @csrf
            @if ($category)
                @method('PUT')
            @endif

            <div class="flex flex-col gap-2">
                <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                <x-ui.input id="name" name="name" :value="old('name', $category?->name)" required autofocus />
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="slug">{{ __('Slug') }}</x-ui.label>
                <x-ui.input id="slug" name="slug" :value="old('slug', $category?->slug)" required />
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="parent_id">{{ __('Parent category') }}</x-ui.label>
                <x-ui.select id="parent_id" name="parent_id">
                    <option value="">{{ __('No parent (top level)') }}</option>
                    @foreach ($parentOptions as $option)
                        <option value="{{ $option['category']->id }}" @selected((string) old('parent_id', $category?->parent_id) === (string) $option['category']->id)>
                            {{ str_repeat('— ', $option['depth']).$option['category']->name }}
                        </option>
                    @endforeach
                </x-ui.select>
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="description">{{ __('Description') }}</x-ui.label>
                <x-ui.textarea id="description" name="description" rows="3">{{ old('description', $category?->description) }}</x-ui.textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="sort_order">{{ __('Sort order') }}</x-ui.label>
                    <x-ui.input id="sort_order" name="sort_order" type="number" min="0" :value="old('sort_order', $category?->sort_order ?? 0)" />
                </div>

                <div class="flex items-end pb-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $category?->is_active ?? true)" />
                        {{ __('Active') }}
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('categories.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
