<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $enrollment ? __('Edit enrollment') : __('New enrollment') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Only eligible employees can be enrolled; tenure, grade, and status are checked on submit.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $enrollment ? route('benefit-enrollments.update', $enrollment) : route('benefit-enrollments.store') }}"
            class="flex flex-col gap-4"
        >
            @csrf
            @if ($enrollment)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <x-ui.label for="benefit_id">{{ __('Benefit') }}</x-ui.label>
                    <x-ui.select id="benefit_id" name="benefit_id" required>
                        <option value="">{{ __('Select a benefit') }}</option>
                        @foreach ($benefits as $benefit)
                            <option value="{{ $benefit->id }}" @selected((string) old('benefit_id', $enrollment?->benefit_id) === (string) $benefit->id)>
                                {{ $benefit->name }} ({{ $benefit->code }})
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="employee_id">{{ __('Employee') }}</x-ui.label>
                    <x-ui.select id="employee_id" name="employee_id" required>
                        <option value="">{{ __('Select an employee') }}</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) old('employee_id', $enrollment?->employee_id) === (string) $employee->id)>
                                {{ $employee->name }} ({{ $employee->employee_number }})
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="status">{{ __('Status') }}</x-ui.label>
                    <x-ui.select id="status" name="status" required>
                        @foreach (App\Models\BenefitEnrollment::STATUSES as $status)
                            <option value="{{ $status }}" @selected(old('status', $enrollment?->status ?? 'active') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div></div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="effective_from">{{ __('Effective from') }}</x-ui.label>
                    <x-ui.input id="effective_from" name="effective_from" type="date" :value="old('effective_from', $enrollment?->effective_from?->format('Y-m-d'))" required />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="effective_to">{{ __('Effective to') }}</x-ui.label>
                    <x-ui.input id="effective_to" name="effective_to" type="date" :value="old('effective_to', $enrollment?->effective_to?->format('Y-m-d'))" />
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="notes">{{ __('Notes') }}</x-ui.label>
                <x-ui.textarea id="notes" name="notes" rows="3">{{ old('notes', $enrollment?->notes) }}</x-ui.textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('benefit-enrollments.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
