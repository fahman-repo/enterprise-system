<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $site ? __('Edit site') : __('New site') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ $site ? __('Update the site details below.') : __('Create a company, building, branch, warehouse, workshop or factory.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        @php
            $selectedType = (string) old('type', $site?->type ?? 'branch');

            $parentOptionData = $parentOptions
                ->map(fn ($parent): array => [
                    'id' => $parent->id,
                    'name' => $parent->name,
                    'type' => $parent->type,
                ])
                ->values()
                ->all();
        @endphp

        <form
            method="POST"
            action="{{ $site ? route('sites.update', $site) : route('sites.store') }}"
            class="flex flex-col gap-6"
            x-data="{
                type: @js($selectedType),
                parentOptions: @js($parentOptionData),
                parentTypes: @js(App\Models\Site::PARENT_TYPES),
                get allowed() {
                    return this.parentTypes[this.type] ?? [];
                },
                isAllowedParent(id) {
                    if (id === '') {
                        return true;
                    }

                    const parent = this.parentOptions.find((option) => String(option.id) === String(id));

                    return parent ? this.allowed.includes(parent.type) : true;
                },
                resetParent() {
                    const select = this.$refs.parent;

                    if (select && ! this.isAllowedParent(select.value)) {
                        select.value = '';
                    }
                },
            }"
        >
            @csrf
            @if ($site)
                @method('PUT')
            @endif

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Site identity') }}</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="code">{{ __('Code') }}</x-ui.label>
                        <x-ui.input id="code" name="code" :value="old('code', $site?->code)" placeholder="{{ __('Generated automatically when blank') }}" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                        <x-ui.input id="name" name="name" :value="old('name', $site?->name)" required autofocus />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="type">{{ __('Type') }}</x-ui.label>
                        <x-ui.select id="type" name="type" x-model="type" x-on:change="resetParent()" required>
                            <option value="">{{ __('Select a type') }}</option>
                            @foreach (App\Models\Site::typeLabels() as $value => $label)
                                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="parent_id">{{ __('Parent site') }}</x-ui.label>
                        <x-ui.select
                            id="parent_id"
                            name="parent_id"
                            x-ref="parent"
                        >
                            <option value="">{{ __('No parent site') }}</option>
                            @foreach ($parentOptions as $parent)
                                <option value="{{ $parent->id }}" @selected((string) old('parent_id', $site?->parent_id) === (string) $parent->id)>
                                    {{ $parent->name }}
                                </option>
                            @endforeach
                        </x-ui.select>
                        <p class="text-xs text-muted-foreground" x-show="allowed.length === 0" x-cloak>
                            {{ __('A :type site is a root site and cannot be nested.', ['type' => $selectedType]) }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 sm:col-span-2">
                        <x-ui.label for="description">{{ __('Description') }}</x-ui.label>
                        <x-ui.textarea id="description" name="description" rows="2">{{ old('description', $site?->description) }}</x-ui.textarea>
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Location & contact') }}</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2 sm:col-span-2">
                        <x-ui.label for="address">{{ __('Address') }}</x-ui.label>
                        <x-ui.textarea id="address" name="address" rows="2">{{ old('address', $site?->address) }}</x-ui.textarea>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="city">{{ __('City') }}</x-ui.label>
                        <x-ui.input id="city" name="city" :value="old('city', $site?->city)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="province">{{ __('Province') }}</x-ui.label>
                        <x-ui.input id="province" name="province" :value="old('province', $site?->province)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="postal_code">{{ __('Postal code') }}</x-ui.label>
                        <x-ui.input id="postal_code" name="postal_code" :value="old('postal_code', $site?->postal_code)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="phone">{{ __('Phone') }}</x-ui.label>
                        <x-ui.input id="phone" name="phone" :value="old('phone', $site?->phone)" />
                    </div>

                    <div class="flex flex-col gap-2 sm:col-span-2">
                        <x-ui.label for="email">{{ __('Email') }}</x-ui.label>
                        <x-ui.input id="email" name="email" type="email" :value="old('email', $site?->email)" />
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Notes') }}</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2 sm:col-span-2">
                        <x-ui.textarea id="notes" name="notes" rows="3" placeholder="{{ __('Notes') }}">{{ old('notes', $site?->notes) }}</x-ui.textarea>
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Status') }}</h2>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $site?->is_active ?? true)" />
                    {{ __('Active') }}
                </label>
            </div>

            <div class="flex items-center justify-end gap-2">
                <x-ui.button variant="outline" href="{{ route('sites.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>