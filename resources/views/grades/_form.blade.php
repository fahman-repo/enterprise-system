<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $grade ? __('Edit grade') : __('New grade') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Grades describe seniority; a higher level means a more senior grade.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $grade ? route('grades.update', $grade) : route('grades.store') }}"
            class="flex flex-col gap-4"
        >
            @csrf
            @if ($grade)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                    <x-ui.input id="name" name="name" :value="old('name', $grade?->name)" required autofocus />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="level">{{ __('Level') }}</x-ui.label>
                    <x-ui.input id="level" name="level" type="number" min="0" :value="old('level', $grade?->level ?? 1)" required />
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="description">{{ __('Description') }}</x-ui.label>
                <x-ui.textarea id="description" name="description" rows="3">{{ old('description', $grade?->description) }}</x-ui.textarea>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $grade?->is_active ?? true)" />
                {{ __('Active') }}
            </label>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('grades.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
