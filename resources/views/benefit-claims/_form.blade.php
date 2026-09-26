<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $claim ? __('Edit claim') : __('New claim') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Claims consume the enrollment balance; over-limit amounts are rejected on submit.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            enctype="multipart/form-data"
            action="{{ $claim ? route('benefit-claims.update', $claim) : route('benefit-claims.store') }}"
            class="flex flex-col gap-4"
            x-data="{
                enrollmentId: @js((string) old('benefit_enrollment_id', $claim?->benefit_enrollment_id)),
                enrollments: @js($enrollments),
                get selected() {
                    return this.enrollments.find((enrollment) => String(enrollment.id) === String(this.enrollmentId)) ?? null;
                },
            }"
        >
            @csrf
            @if ($claim)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2 sm:col-span-2">
                    <x-ui.label for="benefit_enrollment_id">{{ __('Enrollment') }}</x-ui.label>
                    <x-ui.select id="benefit_enrollment_id" name="benefit_enrollment_id" x-model="enrollmentId" required>
                        <option value="">{{ __('Select an enrollment') }}</option>
                        @foreach ($enrollments as $enrollment)
                            <option value="{{ $enrollment['id'] }}" @selected((string) old('benefit_enrollment_id', $claim?->benefit_enrollment_id) === (string) $enrollment['id'])>
                                {{ $enrollment['label'] }}
                            </option>
                        @endforeach
                    </x-ui.select>
                    <p class="text-xs text-muted-foreground" x-show="selected">
                        <span x-text="selected ? selected.benefit_name : ''"></span>
                        ·
                        <span x-text="selected ? selected.employee_name : ''"></span>
                        ·
                        {{ __('Remaining') }} <span x-text="selected ? selected.remaining : ''"></span>
                    </p>
                </div>

                <input type="hidden" name="benefit_id" :value="selected ? selected.benefit_id : '{{ old('benefit_id', $claim?->benefit_id) }}'">
                <input type="hidden" name="employee_id" :value="selected ? selected.employee_id : '{{ old('employee_id', $claim?->employee_id) }}'">

                <div class="flex flex-col gap-2">
                    <x-ui.label for="claim_date">{{ __('Claim date') }}</x-ui.label>
                    <x-ui.input id="claim_date" name="claim_date" type="date" :value="old('claim_date', $claim?->claim_date?->format('Y-m-d') ?? now()->format('Y-m-d'))" required />
                </div>

                <div class="flex flex-col gap-2">
                    <x-ui.label for="amount">{{ __('Amount') }}</x-ui.label>
                    <x-ui.input id="amount" name="amount" type="number" step="0.01" min="0.01" :value="old('amount', $claim?->amount)" required />
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="description">{{ __('Description') }}</x-ui.label>
                <x-ui.textarea id="description" name="description" rows="3">{{ old('description', $claim?->description) }}</x-ui.textarea>
            </div>

            <div class="flex flex-col gap-2">
                <x-ui.label for="receipt">{{ __('Receipt') }}</x-ui.label>

                @if ($claim?->receipt_path)
                    <a href="{{ Storage::disk('public')->exists($claim->receipt_path) ? Storage::disk('public')->url($claim->receipt_path) : '#' }}" class="text-sm text-primary hover:underline" target="_blank" rel="noopener">
                        {{ __('View current receipt') }}
                    </a>

                    <label class="flex items-center gap-2 text-sm text-muted-foreground">
                        <x-ui.checkbox name="remove_receipt" value="1" />
                        {{ __('Remove current receipt') }}
                    </label>
                @endif

                <x-ui.file-input id="receipt" name="receipt" accept="image/jpeg,image/png,image/webp,application/pdf" />
                <p class="text-xs text-muted-foreground">{{ __('JPG, PNG, WebP or PDF up to 4 MB.') }}</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button variant="outline" href="{{ route('benefit-claims.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
