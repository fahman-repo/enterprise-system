<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $department ? __('Edit department') : __('New department') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Departments belong to a division and group org units and positions.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $department ? route('departments.update', $department) : route('departments.store') }}"
            class="flex flex-col gap-4"
        >
            @csrf
            @if ($department)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="division_id">{{ __('Division') }}</x-ui.label>
                    <x-ui.select id="division_id" name="division_id" required>
                        <option value="">{{ __('Select a division') }}</option>
                        @foreach ($divisions as $division)
                            <option value="{{ $division->id }}" @selected((string) old('division_id', $department?->division_id) === (string) $division->id)>
                                {{ $division->name }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="code">{{ __('Code') }}</x-ui.label>
                    <x-ui.input id="code" name="code" :value="old('code', $department?->code)" required />
                </div>

                <div class="flex flex-col gap-2 sm:col-span-2">
                    <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                    <x-ui.input id="name" name="name" :value="old('name', $department?->name)" required autofocus />
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="description">{{ __('Description') }}</x-ui.label>
                <x-ui.textarea id="description" name="description" rows="3">{{ old('description', $department?->description) }}</x-ui.textarea>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $department?->is_active ?? true)" />
                {{ __('Active') }}
            </label>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('departments.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
