<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $menu ? __('Edit menu item') : __('New menu item') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Sidebar entries are rendered from these records, ordered by sort value.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $menu ? route('menus.update', $menu) : route('menus.store') }}"
            class="flex flex-col gap-4"
        >
            @csrf
            @if ($menu)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                    <x-ui.input id="name" name="name" :value="old('name', $menu?->name)" required autofocus />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="slug">{{ __('Slug') }}</x-ui.label>
                    <x-ui.input id="slug" name="slug" :value="old('slug', $menu?->slug)" required />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="parent_id">{{ __('Parent') }}</x-ui.label>
                    <x-ui.select id="parent_id" name="parent_id">
                        <option value="">{{ __('Top level') }}</option>
                        @foreach ($parentOptions as $option)
                            <option value="{{ $option->id }}" @selected((string) old('parent_id', $menu?->parent_id) === (string) $option->id)>
                                {{ $option->name }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="icon">{{ __('Icon') }}</x-ui.label>
                    <x-ui.select id="icon" name="icon">
                        <option value="">{{ __('None') }}</option>
                        @foreach (\App\Models\Menu::ICONS as $icon)
                            <option value="{{ $icon }}" @selected((string) old('icon', $menu?->icon) === $icon)>
                                {{ $icon }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="route_name">{{ __('Route name') }}</x-ui.label>
                    <x-ui.input id="route_name" name="route_name" :value="old('route_name', $menu?->route_name)" placeholder="users.index" />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="sort_order">{{ __('Sort order') }}</x-ui.label>
                    <x-ui.input id="sort_order" name="sort_order" type="number" min="0" :value="old('sort_order', $menu?->sort_order ?? 0)" />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <x-ui.checkbox id="is_active" name="is_active" value="1" :checked="(bool) old('is_active', $menu?->is_active ?? true)" />
                <x-ui.label for="is_active" class="text-muted-foreground">{{ __('Active') }}</x-ui.label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('menus.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
