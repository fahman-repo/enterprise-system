<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-title>{{ $entity ? __('Edit entity') : __('New entity') }}</x-ui.card-title>
        <x-ui.card-description>
            {{ $entity ? __('Update the entity master details below.') : __('Create a new vendor or customer entity record.') }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content>
        <form
            method="POST"
            action="{{ $entity ? route('entities.update', $entity) : route('entities.store') }}"
            class="flex flex-col gap-6"
            x-data="{ type: @js((string) old('type', $entity?->type ?? 'company')) }"
        >
            @csrf
            @if ($entity)
                @method('PUT')
            @endif

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Entity identity') }}</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="code">{{ __('Code') }}</x-ui.label>
                        <x-ui.input id="code" name="code" :value="old('code', $entity?->code)" placeholder="{{ __('Generated automatically when blank') }}" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="name">{{ __('Name') }}</x-ui.label>
                        <x-ui.input id="name" name="name" :value="old('name', $entity?->name)" required autofocus />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="type">{{ __('Type') }}</x-ui.label>
                        <x-ui.select id="type" name="type" x-model="type" required>
                            <option value="">{{ __('Select a type') }}</option>
                            <option value="company" @selected(old('type', $entity?->type) === 'company')>{{ __('Company') }}</option>
                            <option value="personal" @selected(old('type', $entity?->type) === 'personal')>{{ __('Personal') }}</option>
                        </x-ui.select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="role">{{ __('Role') }}</x-ui.label>
                        <x-ui.select id="role" name="role" required>
                            <option value="">{{ __('Select a role') }}</option>
                            <option value="vendor" @selected(old('role', $entity?->role) === 'vendor')>{{ __('Vendor') }}</option>
                            <option value="customer" @selected(old('role', $entity?->role) === 'customer')>{{ __('Customer') }}</option>
                            <option value="both" @selected(old('role', $entity?->role) === 'both')>{{ __('Vendor & customer') }}</option>
                        </x-ui.select>
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Legal & tax') }}</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="npwp">{{ __('NPWP') }}</x-ui.label>
                        <x-ui.input id="npwp" name="npwp" :value="old('npwp', $entity?->npwp)" />
                    </div>

                    <div class="flex flex-col gap-2" x-show="type === 'personal'" x-cloak>
                        <x-ui.label for="identity_number">{{ __('Identity number (KTP)') }}</x-ui.label>
                        <x-ui.input id="identity_number" name="identity_number" :value="old('identity_number', $entity?->identity_number)" />
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Contact & address') }}</h2>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="email">{{ __('Email') }}</x-ui.label>
                        <x-ui.input id="email" name="email" type="email" :value="old('email', $entity?->email)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="phone">{{ __('Phone') }}</x-ui.label>
                        <x-ui.input id="phone" name="phone" :value="old('phone', $entity?->phone)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="postal_code">{{ __('Postal code') }}</x-ui.label>
                        <x-ui.input id="postal_code" name="postal_code" :value="old('postal_code', $entity?->postal_code)" />
                    </div>

                    <div class="flex flex-col gap-2 sm:col-span-3">
                        <x-ui.label for="address">{{ __('Address') }}</x-ui.label>
                        <x-ui.textarea id="address" name="address" rows="2">{{ old('address', $entity?->address) }}</x-ui.textarea>
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="city">{{ __('City') }}</x-ui.label>
                        <x-ui.input id="city" name="city" :value="old('city', $entity?->city)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="province">{{ __('Province') }}</x-ui.label>
                        <x-ui.input id="province" name="province" :value="old('province', $entity?->province)" />
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Bank') }}</h2>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="flex flex-col gap-2">
                        <x-ui.label for="bank_name">{{ __('Bank') }}</x-ui.label>
                        <x-ui.input id="bank_name" name="bank_name" :value="old('bank_name', $entity?->bank_name)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="bank_account_number">{{ __('Bank account number') }}</x-ui.label>
                        <x-ui.input id="bank_account_number" name="bank_account_number" :value="old('bank_account_number', $entity?->bank_account_number)" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.label for="bank_account_name">{{ __('Bank account name') }}</x-ui.label>
                        <x-ui.input id="bank_account_name" name="bank_account_name" :value="old('bank_account_name', $entity?->bank_account_name)" />
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Notes') }}</h2>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="flex flex-col gap-2 sm:col-span-3">
                        <x-ui.label for="notes">{{ __('Notes') }}</x-ui.label>
                        <x-ui.textarea id="notes" name="notes" rows="3">{{ old('notes', $entity?->notes) }}</x-ui.textarea>
                    </div>
                </div>
            </div>

            <x-ui.separator />

            <div class="flex flex-col gap-4">
                <h2 class="text-sm font-semibold">{{ __('Status') }}</h2>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <x-ui.checkbox name="is_active" value="1" :checked="(bool) old('is_active', $entity?->is_active ?? true)" />
                    {{ __('Active') }}
                </label>
            </div>

            <div class="flex items-center justify-end gap-2">
                <x-ui.button variant="outline" href="{{ route('entities.index') }}" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
