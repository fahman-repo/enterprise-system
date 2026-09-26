@php
    $divisionOptions = $divisions->map(fn ($division) => [
        'id' => $division->id,
        'name' => $division->name,
    ])->values();

    $departmentOptions = $departments->map(fn ($department) => [
        'id' => $department->id,
        'name' => $department->name,
        'division_id' => $department->division_id,
    ])->values();

    $orgUnitOptions = $orgUnits->map(fn ($orgUnit) => [
        'id' => $orgUnit->id,
        'name' => $orgUnit->name,
        'department_id' => $orgUnit->department_id,
    ])->values();
@endphp

<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $employee ? __('Edit employee') : __('New employee') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ __('Employee master records hold personal, placement and payroll details.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            enctype="multipart/form-data"
            action="{{ $employee ? route('employees.update', $employee) : route('employees.store') }}"
            class="flex flex-col gap-6"
            x-data="{
                divisionId: @js((string) old('division_id', $employee?->division_id)),
                departmentId: @js((string) old('department_id', $employee?->department_id)),
                orgUnitId: @js((string) old('org_unit_id', $employee?->org_unit_id)),
                divisions: @js($divisionOptions),
                departments: @js($departmentOptions),
                orgUnits: @js($orgUnitOptions),
                departmentsFor(divisionId) {
                    return this.departments.filter((department) => String(department.division_id) === String(divisionId));
                },
                orgUnitsFor(departmentId) {
                    return this.orgUnits.filter((orgUnit) => String(orgUnit.department_id) === String(departmentId));
                },
            }"
        >
            @csrf
            @if ($employee)
                @method('PUT')
            @endif

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Employment placement') }}</h2>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="division_id">{{ __('Division') }}</x-ui.label>
                        <x-ui.select id="division_id" name="division_id" x-model="divisionId" x-bind:disabled="false"
                            @change="departmentId = ''; orgUnitId = ''" required>
                            <option value="">{{ __('Select a division') }}</option>
                            <template x-for="division in divisions" :key="division.id">
                                <option :value="division.id" x-text="division.name"></option>
                            </template>
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="department_id">{{ __('Department') }}</x-ui.label>
                        <x-ui.select id="department_id" name="department_id" x-model="departmentId"
                            x-bind:disabled="!divisionId" @change="orgUnitId = ''" required>
                            <option value="">{{ __('Select a department') }}</option>
                            <template x-for="department in departmentsFor(divisionId)" :key="department.id">
                                <option :value="department.id" x-text="department.name"></option>
                            </template>
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="org_unit_id">{{ __('Org unit') }}</x-ui.label>
                        <x-ui.select id="org_unit_id" name="org_unit_id" x-model="orgUnitId"
                            x-bind:disabled="!departmentId">
                            <option value="">{{ __('No org unit') }}</option>
                            <template x-for="orgUnit in orgUnitsFor(departmentId)" :key="orgUnit.id">
                                <option :value="orgUnit.id" x-text="orgUnit.name"></option>
                            </template>
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="position_id">{{ __('Position') }}</x-ui.label>
                        <x-ui.select id="position_id" name="position_id" required>
                            <option value="">{{ __('Select a position') }}</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}" @selected((string) old('position_id', $employee?->position_id) === (string) $position->id)>
                                    {{ $position->name }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="grade_id">{{ __('Grade') }}</x-ui.label>
                        <x-ui.select id="grade_id" name="grade_id">
                            <option value="">{{ __('No grade') }}</option>
                            @foreach ($grades as $grade)
                                <option value="{{ $grade->id }}" @selected((string) old('grade_id', $employee?->grade_id) === (string) $grade->id)>
                                    {{ $grade->name }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="manager_id">{{ __('Direct manager') }}</x-ui.label>
                        <x-ui.select id="manager_id" name="manager_id">
                            <option value="">{{ __('No manager (top of reporting line)') }}</option>
                            @foreach ($managers as $manager)
                                <option value="{{ $manager->id }}" @selected((string) old('manager_id', $employee?->manager_id) === (string) $manager->id)>
                                    {{ $manager->name }} ({{ $manager->employee_number }})
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="work_location_id">{{ __('Work location') }}</x-ui.label>
                        <x-ui.select id="work_location_id" name="work_location_id">
                            <option value="">{{ __('No work location') }}</option>
                            @foreach ($workLocations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('work_location_id', $employee?->work_location_id) === (string) $location->id)>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="employment_status_id">{{ __('Employment status') }}</x-ui.label>
                        <x-ui.select id="employment_status_id" name="employment_status_id" required>
                            <option value="">{{ __('Select a status') }}</option>
                            @foreach ($employmentStatuses as $status)
                                <option value="{{ $status->id }}" @selected((string) old('employment_status_id', $employee?->employment_status_id) === (string) $status->id)>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="join_date">{{ __('Join date') }}</x-ui.label>
                        <x-ui.input id="join_date" name="join_date" type="date" :value="old('join_date', $employee?->join_date?->format('Y-m-d'))" required />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="probation_end_date">{{ __('Probation end') }}</x-ui.label>
                        <x-ui.input id="probation_end_date" name="probation_end_date" type="date" :value="old('probation_end_date', $employee?->probation_end_date?->format('Y-m-d'))" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="end_date">{{ __('End date') }}</x-ui.label>
                        <x-ui.input id="end_date" name="end_date" type="date" :value="old('end_date', $employee?->end_date?->format('Y-m-d'))" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="user_id">{{ __('Linked user account') }}</x-ui.label>
                        <x-ui.select id="user_id" name="user_id">
                            <option value="">{{ __('No linked account') }}</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((string) old('user_id', $employee?->user_id) === (string) $user->id)>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Personal details') }}</h2>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="name">{{ __('Full name') }}</x-ui.label>
                        <x-ui.input id="name" name="name" :value="old('name', $employee?->name)" required autofocus />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="employee_number">{{ __('Employee number') }}</x-ui.label>
                        <x-ui.input id="employee_number" name="employee_number" :value="old('employee_number', $employee?->employee_number)" placeholder="{{ __('Generated automatically when blank') }}" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="gender">{{ __('Gender') }}</x-ui.label>
                        <x-ui.select id="gender" name="gender" required>
                            <option value="">{{ __('Select a gender') }}</option>
                            <option value="male" @selected(old('gender', $employee?->gender) === 'male')>{{ __('Male') }}</option>
                            <option value="female" @selected(old('gender', $employee?->gender) === 'female')>{{ __('Female') }}</option>
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="birth_place">{{ __('Birth place') }}</x-ui.label>
                        <x-ui.input id="birth_place" name="birth_place" :value="old('birth_place', $employee?->birth_place)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="birth_date">{{ __('Birth date') }}</x-ui.label>
                        <x-ui.input id="birth_date" name="birth_date" type="date" :value="old('birth_date', $employee?->birth_date?->format('Y-m-d'))" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="religion_id">{{ __('Religion') }}</x-ui.label>
                        <x-ui.select id="religion_id" name="religion_id">
                            <option value="">{{ __('No religion') }}</option>
                            @foreach ($religions as $religion)
                                <option value="{{ $religion->id }}" @selected((string) old('religion_id', $employee?->religion_id) === (string) $religion->id)>
                                    {{ $religion->name }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="marital_status_id">{{ __('Marital status') }}</x-ui.label>
                        <x-ui.select id="marital_status_id" name="marital_status_id">
                            <option value="">{{ __('No marital status') }}</option>
                            @foreach ($maritalStatuses as $maritalStatus)
                                <option value="{{ $maritalStatus->id }}" @selected((string) old('marital_status_id', $employee?->marital_status_id) === (string) $maritalStatus->id)>
                                    {{ $maritalStatus->name }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="education_level_id">{{ __('Education level') }}</x-ui.label>
                        <x-ui.select id="education_level_id" name="education_level_id">
                            <option value="">{{ __('No education level') }}</option>
                            @foreach ($educationLevels as $educationLevel)
                                <option value="{{ $educationLevel->id }}" @selected((string) old('education_level_id', $employee?->education_level_id) === (string) $educationLevel->id)>
                                    {{ $educationLevel->name }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Contact & address') }}</h2>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="email">{{ __('Email') }}</x-ui.label>
                        <x-ui.input id="email" name="email" type="email" :value="old('email', $employee?->email)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="phone">{{ __('Phone') }}</x-ui.label>
                        <x-ui.input id="phone" name="phone" :value="old('phone', $employee?->phone)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="postal_code">{{ __('Postal code') }}</x-ui.label>
                        <x-ui.input id="postal_code" name="postal_code" :value="old('postal_code', $employee?->postal_code)" />
                    </div>

                    <div class="flex flex-col gap-2 sm:col-span-3">
                        <x-ui.label for="address">{{ __('Address') }}</x-ui.label>
                        <x-ui.textarea id="address" name="address" rows="2">{{ old('address', $employee?->address) }}</x-ui.textarea>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="city">{{ __('City') }}</x-ui.label>
                        <x-ui.input id="city" name="city" :value="old('city', $employee?->city)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="province">{{ __('Province') }}</x-ui.label>
                        <x-ui.input id="province" name="province" :value="old('province', $employee?->province)" />
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Legal & bank') }}</h2>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="identity_number">{{ __('Identity number (KTP)') }}</x-ui.label>
                        <x-ui.input id="identity_number" name="identity_number" :value="old('identity_number', $employee?->identity_number)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="npwp">{{ __('NPWP') }}</x-ui.label>
                        <x-ui.input id="npwp" name="npwp" :value="old('npwp', $employee?->npwp)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="bpjs_kesehatan">{{ __('BPJS Kesehatan') }}</x-ui.label>
                        <x-ui.input id="bpjs_kesehatan" name="bpjs_kesehatan" :value="old('bpjs_kesehatan', $employee?->bpjs_kesehatan)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="bpjs_ketenagakerjaan">{{ __('BPJS Ketenagakerjaan') }}</x-ui.label>
                        <x-ui.input id="bpjs_ketenagakerjaan" name="bpjs_ketenagakerjaan" :value="old('bpjs_ketenagakerjaan', $employee?->bpjs_ketenagakerjaan)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="bank_name">{{ __('Bank') }}</x-ui.label>
                        <x-ui.input id="bank_name" name="bank_name" :value="old('bank_name', $employee?->bank_name)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="bank_account_number">{{ __('Bank account number') }}</x-ui.label>
                        <x-ui.input id="bank_account_number" name="bank_account_number" :value="old('bank_account_number', $employee?->bank_account_number)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="bank_account_name">{{ __('Bank account name') }}</x-ui.label>
                        <x-ui.input id="bank_account_name" name="bank_account_name" :value="old('bank_account_name', $employee?->bank_account_name)" />
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Emergency contact') }}</h2>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="emergency_contact_name">{{ __('Name') }}</x-ui.label>
                        <x-ui.input id="emergency_contact_name" name="emergency_contact_name" :value="old('emergency_contact_name', $employee?->emergency_contact_name)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="emergency_contact_relationship">{{ __('Relationship') }}</x-ui.label>
                        <x-ui.input id="emergency_contact_relationship" name="emergency_contact_relationship" :value="old('emergency_contact_relationship', $employee?->emergency_contact_relationship)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="emergency_contact_phone">{{ __('Phone') }}</x-ui.label>
                        <x-ui.input id="emergency_contact_phone" name="emergency_contact_phone" :value="old('emergency_contact_phone', $employee?->emergency_contact_phone)" />
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-2">
                <x-ui.label for="photo">{{ __('Photo') }}</x-ui.label>

                @if ($employee?->photo_path)
                    <div class="flex flex-wrap items-center gap-4">
                        <x-ui.avatar :name="$employee->name" :src="$employee->photoUrl()" class="size-16" />

                        <label class="flex items-center gap-2 text-sm text-muted-foreground">
                            <x-ui.checkbox name="remove_photo" value="1" />
                            {{ __('Remove current photo') }}
                        </label>
                    </div>
                @endif

                <x-ui.file-input id="photo" name="photo" accept="image/jpeg,image/png,image/webp" />
                <p class="text-xs text-muted-foreground">{{ __('JPG, PNG or WebP up to 2 MB.') }}</p>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $employee?->is_active ?? true)" />
                {{ __('Active') }}
            </label>

            <div class="flex items-center justify-end gap-2">
                <x-ui.button variant="outline" href="{{ route('employees.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
