<x-app-layout :title="$employee->name">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        @include('partials.flash')

        <x-ui.card>
            <x-ui.card-content class="pt-6">
                <div class="flex flex-wrap items-start justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <x-ui.avatar :name="$employee->name" :src="$employee->photoUrl()" class="size-16" />

                        <div class="flex flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-xl font-semibold tracking-tight">{{ $employee->name }}</h1>

                                @if ($employee->is_active)
                                    <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                                @else
                                    <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                                @endif

                                @if ($employee->employmentStatus)
                                    <x-ui.badge variant="outline">{{ $employee->employmentStatus->name }}</x-ui.badge>
                                @endif
                            </div>

                            <p class="text-sm text-muted-foreground">
                                <span class="font-mono">{{ $employee->employee_number }}</span>
                                @if ($employee->position)
                                    · {{ $employee->position->name }}
                                @endif
                                @if ($employee->department)
                                    · {{ $employee->department->name }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-ui.button variant="outline" href="{{ route('employees.index') }}">
                            {{ __('Back') }}
                        </x-ui.button>

                        @can('employees.update')
                            <x-ui.button href="{{ route('employees.edit', $employee) }}">
                                {{ __('Edit') }}
                            </x-ui.button>
                        @endcan
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.tabs default="employment">
            <x-ui.card>
                <x-ui.tabs-list class="px-6">
                    <x-ui.tabs-trigger value="employment">{{ __('Employment') }}</x-ui.tabs-trigger>
                    <x-ui.tabs-trigger value="personal">{{ __('Personal') }}</x-ui.tabs-trigger>
                    <x-ui.tabs-trigger value="contact">{{ __('Contact') }}</x-ui.tabs-trigger>
                    <x-ui.tabs-trigger value="legal">{{ __('Legal & bank') }}</x-ui.tabs-trigger>
                    <x-ui.tabs-trigger value="emergency">{{ __('Emergency contact') }}</x-ui.tabs-trigger>
                </x-ui.tabs-list>

                <x-ui.tabs-content value="employment" class="p-6">
                    @php
                        $employment = [
                            __('Division') => $employee->division?->name,
                            __('Department') => $employee->department?->name,
                            __('Org unit') => $employee->orgUnit?->name,
                            __('Position') => $employee->position?->name,
                            __('Grade') => $employee->grade?->name,
                            __('Work location') => $employee->workLocation?->name,
                            __('Employment status') => $employee->employmentStatus?->name,
                            __('Direct manager') => $employee->manager?->name,
                            __('Direct reports') => (string) $employee->reports_count,
                            __('Linked user') => $employee->user?->email,
                            __('Join date') => $employee->join_date?->format('d M Y'),
                            __('Probation end') => $employee->probation_end_date?->format('d M Y'),
                            __('End date') => $employee->end_date?->format('d M Y'),
                        ];
                    @endphp

                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($employment as $label => $value)
                            <div class="flex flex-col gap-1">
                                <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $label }}</dt>
                                <dd class="text-sm">{{ $value ?? __('—') }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.tabs-content>

                <x-ui.tabs-content value="personal" class="p-6">
                    @php
                        $personal = [
                            __('Gender') => $employee->genderLabel(),
                            __('Birth place') => $employee->birth_place,
                            __('Birth date') => $employee->birth_date?->format('d M Y'),
                            __('Religion') => $employee->religion?->name,
                            __('Marital status') => $employee->maritalStatus?->name,
                            __('Education level') => $employee->educationLevel?->name,
                        ];
                    @endphp

                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($personal as $label => $value)
                            <div class="flex flex-col gap-1">
                                <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $label }}</dt>
                                <dd class="text-sm">{{ $value ?? __('—') }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.tabs-content>

                <x-ui.tabs-content value="contact" class="p-6">
                    @php
                        $contact = [
                            __('Email') => $employee->email,
                            __('Phone') => $employee->phone,
                            __('Address') => $employee->address,
                            __('City') => $employee->city,
                            __('Province') => $employee->province,
                            __('Postal code') => $employee->postal_code,
                        ];
                    @endphp

                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($contact as $label => $value)
                            <div class="flex flex-col gap-1">
                                <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $label }}</dt>
                                <dd class="text-sm">{{ $value ?? __('—') }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.tabs-content>

                <x-ui.tabs-content value="legal" class="p-6">
                    @php
                        $legal = [
                            __('Identity number') => $employee->identity_number,
                            __('NPWP') => $employee->npwp,
                            __('BPJS Kesehatan') => $employee->bpjs_kesehatan,
                            __('BPJS Ketenagakerjaan') => $employee->bpjs_ketenagakerjaan,
                            __('Bank') => $employee->bank_name,
                            __('Bank account number') => $employee->bank_account_number,
                            __('Bank account name') => $employee->bank_account_name,
                        ];
                    @endphp

                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($legal as $label => $value)
                            <div class="flex flex-col gap-1">
                                <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $label }}</dt>
                                <dd class="text-sm">{{ $value ?? __('—') }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.tabs-content>

                <x-ui.tabs-content value="emergency" class="p-6">
                    @php
                        $emergency = [
                            __('Name') => $employee->emergency_contact_name,
                            __('Relationship') => $employee->emergency_contact_relationship,
                            __('Phone') => $employee->emergency_contact_phone,
                        ];
                    @endphp

                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($emergency as $label => $value)
                            <div class="flex flex-col gap-1">
                                <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $label }}</dt>
                                <dd class="text-sm">{{ $value ?? __('—') }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.tabs-content>
            </x-ui.card>
        </x-ui.tabs>
    </div>
</x-app-layout>
