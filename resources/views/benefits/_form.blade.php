<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $benefit ? __('Edit benefit') : __('New benefit') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Benefit packages define the limit, period, and eligibility rules employees enroll into.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        @php
            $selectedType = (string) old('type', $benefit?->type ?? '');
            $selectedPeriod = (string) old('period', $benefit?->period ?? 'yearly');
            $selectedGrades = collect(old('eligible_grade_ids', $benefit?->eligibleGrades->pluck('id')->all() ?? []))
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();
            $selectedStatuses = collect(old('eligible_employment_status_ids', $benefit?->eligibleEmploymentStatuses->pluck('id')->all() ?? []))
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();
            $gradeOptions = $grades->map(fn ($grade): array => [
                'id' => $grade->id,
                'name' => $grade->name,
            ])->values()->all();
            $employmentStatusOptions = $employmentStatuses->map(fn ($status): array => [
                'id' => $status->id,
                'name' => $status->name,
            ])->values()->all();
        @endphp

        <form
            method="POST"
            action="{{ $benefit ? route('benefits.update', $benefit) : route('benefits.store') }}"
            class="flex flex-col gap-6"
        >
            @csrf
            @if ($benefit)
                @method('PUT')
            @endif

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Package details') }}</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="code">{{ __('Code') }}</x-ui.label>
                        <x-ui.input id="code" name="code" :value="old('code', $benefit?->code)" required />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                        <x-ui.input id="name" name="name" :value="old('name', $benefit?->name)" required autofocus />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="type">{{ __('Type') }}</x-ui.label>
                        <x-ui.select id="type" name="type" required>
                            <option value="">{{ __('Select a type') }}</option>
                            @foreach (App\Models\Benefit::TYPES as $type)
                                <option value="{{ $type }}" @selected($selectedType === $type)>{{ (new App\Models\Benefit(['type' => $type]))->typeLabel() }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="period">{{ __('Period') }}</x-ui.label>
                        <x-ui.select id="period" name="period" required>
                            @foreach (App\Models\Benefit::PERIODS as $period)
                                <option value="{{ $period }}" @selected($selectedPeriod === $period)>{{ (new App\Models\Benefit(['period' => $period]))->periodLabel() }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Limits & description') }}</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="limit_amount">{{ __('Limit amount') }}</x-ui.label>
                        <x-ui.input id="limit_amount" name="limit_amount" type="number" step="0.01" min="0" :value="old('limit_amount', $benefit?->limit_amount ?? '0.00')" required />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="min_tenure_months">{{ __('Minimum tenure (months)') }}</x-ui.label>
                        <x-ui.input id="min_tenure_months" name="min_tenure_months" type="number" min="0" :value="old('min_tenure_months', $benefit?->min_tenure_months ?? 0)" required />
                    </div>

                    <div class="flex flex-col gap-2 sm:col-span-2">
                        <x-ui.label for="description">{{ __('Description') }}</x-ui.label>
                        <x-ui.textarea id="description" name="description" rows="3">{{ old('description', $benefit?->description) }}</x-ui.textarea>
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Eligibility') }}</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="eligible-grades">{{ __('Eligible grades') }}</x-ui.label>
                        <x-ui.multi-select-dropdown
                            id="eligible-grades"
                            name="eligible_grade_ids[]"
                            :options="$gradeOptions"
                            :selected="$selectedGrades"
                            :placeholder="__('Select grades...')"
                            :search-placeholder="__('Filter grades...')"
                            :aria-label="__('Eligible grades')"
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="eligible-employment-statuses">{{ __('Eligible statuses') }}</x-ui.label>
                        <x-ui.multi-select-dropdown
                            id="eligible-employment-statuses"
                            name="eligible_employment_status_ids[]"
                            :options="$employmentStatusOptions"
                            :selected="$selectedStatuses"
                            :placeholder="__('Select statuses...')"
                            :search-placeholder="__('Filter statuses...')"
                            :aria-label="__('Eligible statuses')"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <x-icon.alert-circle class="size-3.5 shrink-0" />
                    <span>{{ __('Note: Leaving both lists empty means every grade and employment status is eligible.') }}</span>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Options') }}</h2>

                <div class="flex flex-wrap gap-6">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="requires_receipt" value="0">
                        <x-ui.checkbox name="requires_receipt" value="1" :checked="(bool) old('requires_receipt', $benefit?->requires_receipt ?? true)" />
                        {{ __('Requires receipt') }}
                    </label>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $benefit?->is_active ?? true)" />
                        {{ __('Active') }}
                    </label>
                </div>
            </div>

            @if ($errors->any())
                <x-ui.alert variant="destructive">
                    <x-slot:title>{{ __('Please review the benefit details') }}</x-slot:title>
                    <ul class="list-inside list-disc text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            <div class="flex items-center justify-end gap-2 border-t border-border pt-4">
                <x-ui.button variant="outline" href="{{ route('benefits.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
