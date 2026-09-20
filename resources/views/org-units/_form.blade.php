<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $orgUnit ? __('Edit org unit') : __('New org unit') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Org units are the smallest organizational grouping, nested under a department.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $orgUnit ? route('org-units.update', $orgUnit) : route('org-units.store') }}"
            class="flex flex-col gap-4"
        >
            @csrf
            @if ($orgUnit)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="department_id">{{ __('Department') }}</x-ui.label>
                    <x-ui.select id="department_id" name="department_id" required>
                        <option value="">{{ __('Select a department') }}</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) old('department_id', $orgUnit?->department_id) === (string) $department->id)>
                                {{ $department->name }}{{ $department->division ? ' — '.$department->division->name : '' }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="code">{{ __('Code') }}</x-ui.label>
                    <x-ui.input id="code" name="code" :value="old('code', $orgUnit?->code)" required />
                </div>

                <div class="flex flex-col gap-2 sm:col-span-2">
                    <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                    <x-ui.input id="name" name="name" :value="old('name', $orgUnit?->name)" required autofocus />
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="description">{{ __('Description') }}</x-ui.label>
                <x-ui.textarea id="description" name="description" rows="3">{{ old('description', $orgUnit?->description) }}</x-ui.textarea>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $orgUnit?->is_active ?? true)" />
                {{ __('Active') }}
            </label>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('org-units.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
