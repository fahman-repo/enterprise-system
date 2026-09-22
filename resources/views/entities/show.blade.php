<x-app-layout :title="$entity->name">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        @include('partials.flash')

        <x-ui.card>
            <x-ui.card-content class="pt-6">
                <div class="flex flex-wrap items-start justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <x-ui.avatar :name="$entity->name" class="size-16" />

                        <div class="flex flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-xl font-semibold tracking-tight">{{ $entity->name }}</h1>

                                @if ($entity->is_active)
                                    <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                                @else
                                    <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                                @endif

                                <x-ui.badge variant="outline">{{ $entity->roleLabel() }}</x-ui.badge>
                            </div>

                            <p class="text-sm text-muted-foreground">
                                <span class="font-mono">{{ $entity->code }}</span>
                                · {{ $entity->typeLabel() }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-ui.button variant="outline" href="{{ route('entities.index') }}">
                            {{ __('Back') }}
                        </x-ui.button>

                        @can('entities.update')
                            <x-ui.button href="{{ route('entities.edit', $entity) }}">
                                {{ __('Edit') }}
                            </x-ui.button>
                        @endcan
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.tabs default="profile">
            <x-ui.card>
                <x-ui.tabs-list class="px-6">
                    <x-ui.tabs-trigger value="profile">{{ __('Profile') }}</x-ui.tabs-trigger>
                    <x-ui.tabs-trigger value="contact">{{ __('Contact') }}</x-ui.tabs-trigger>
                    <x-ui.tabs-trigger value="bank">{{ __('Bank & notes') }}</x-ui.tabs-trigger>
                </x-ui.tabs-list>

                <x-ui.tabs-content value="profile" class="p-6">
                    @php
                        $profile = [
                            __('Type') => $entity->typeLabel(),
                            __('Role') => $entity->roleLabel(),
                            __('NPWP') => $entity->npwp,
                            __('Identity number (KTP)') => $entity->identity_number,
                        ];
                    @endphp

                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($profile as $label => $value)
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
                            __('Email') => $entity->email,
                            __('Phone') => $entity->phone,
                            __('Address') => $entity->address,
                            __('City') => $entity->city,
                            __('Province') => $entity->province,
                            __('Postal code') => $entity->postal_code,
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

                <x-ui.tabs-content value="bank" class="p-6">
                    @php
                        $bank = [
                            __('Bank') => $entity->bank_name,
                            __('Bank account number') => $entity->bank_account_number,
                            __('Bank account name') => $entity->bank_account_name,
                            __('Notes') => $entity->notes,
                        ];
                    @endphp

                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($bank as $label => $value)
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
