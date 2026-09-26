<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $program ? __('Edit program') : __('New program') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Programs track trainings, seminars, workshops and certifications offered to employees.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        @php
            $selectedType = (string) old('type', $program?->type ?? '');
            $selectedStatus = (string) old('status', $program?->status ?? 'planned');
        @endphp

        <form
            method="POST"
            action="{{ $program ? route('development-programs.update', $program) : route('development-programs.store') }}"
            class="flex flex-col gap-6"
        >
            @csrf
            @if ($program)
                @method('PUT')
            @endif

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Program identity') }}</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="code">{{ __('Code') }}</x-ui.label>
                        <x-ui.input id="code" name="code" :value="old('code', $program?->code)" placeholder="{{ __('Generated automatically when blank') }}" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                        <x-ui.input id="name" name="name" :value="old('name', $program?->name)" required autofocus />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="type">{{ __('Type') }}</x-ui.label>
                        <x-ui.select id="type" name="type" required>
                            <option value="">{{ __('Select a type') }}</option>
                            @foreach (App\Models\DevelopmentProgram::TYPES as $type)
                                <option value="{{ $type }}" @selected($selectedType === $type)>{{ (new App\Models\DevelopmentProgram(['type' => $type]))->typeLabel() }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="status">{{ __('Status') }}</x-ui.label>
                        <x-ui.select id="status" name="status" required>
                            @foreach (App\Models\DevelopmentProgram::STATUSES as $status)
                                <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ (new App\Models\DevelopmentProgram(['status' => $status]))->statusLabel() }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="start_date">{{ __('Start date') }}</x-ui.label>
                        <x-ui.input id="start_date" name="start_date" type="date" :value="old('start_date', $program?->start_date?->format('Y-m-d'))" required />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="end_date">{{ __('End date') }}</x-ui.label>
                        <x-ui.input id="end_date" name="end_date" type="date" :value="old('end_date', $program?->end_date?->format('Y-m-d'))" required />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="capacity">{{ __('Capacity') }}</x-ui.label>
                        <x-ui.input id="capacity" name="capacity" type="number" min="1" max="10000" :value="old('capacity', $program?->capacity)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="cost">{{ __('Cost') }}</x-ui.label>
                        <x-ui.input id="cost" name="cost" type="number" step="0.01" min="0" :value="old('cost', $program?->cost)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="organizer">{{ __('Organizer') }}</x-ui.label>
                        <x-ui.input id="organizer" name="organizer" :value="old('organizer', $program?->organizer)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="location">{{ __('Location') }}</x-ui.label>
                        <x-ui.input id="location" name="location" :value="old('location', $program?->location)" />
                    </div>

                    <div class="flex flex-col gap-2 sm:col-span-2">
                        <x-ui.label for="description">{{ __('Description') }}</x-ui.label>
                        <x-ui.textarea id="description" name="description" rows="3">{{ old('description', $program?->description) }}</x-ui.textarea>
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Status') }}</h2>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $program?->is_active ?? true)" />
                    {{ __('Active') }}
                </label>
            </div>

            <div class="flex items-center justify-end gap-2">
                <x-ui.button variant="outline" href="{{ route('development-programs.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
