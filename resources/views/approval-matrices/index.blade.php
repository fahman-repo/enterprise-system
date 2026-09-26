<x-app-layout title='Approval-Matrix'>
    <div class='mx-auto flex max-w-6xl flex-col gap-6'>
        <div class='flex flex-wrap items-end justify-between gap-4'>
            <div class='flex flex-col gap-1'>
                <h1 class='text-xl font-semibold tracking-tight'>{{ __('Approval Matrix') }}</h1>
                <p class='text-sm text-muted-foreground'>{{ __('Configure role-based makers and approvers for each module.') }}</p>
            </div>
        </div>

        @include('partials.flash')

        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>{{ __('Supported modules') }}</x-ui.card-title>
                <x-ui.card-description>{{ __('Each module has one organization-wide matrix. In-flight requests retain their original snapshots.') }}</x-ui.card-description>
            </x-ui.card-header>

            <div class='overflow-x-auto border-t'>
                <table class='w-full text-sm'>
                    <thead class='bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground'>
                        <tr>
                            <th class='px-4 py-3 font-medium'>{{ __('Module') }}</th>
                            <th class='px-4 py-3 font-medium'>{{ __('Configuration') }}</th>
                            <th class='px-4 py-3 font-medium'>{{ __('Maker roles') }}</th>
                            <th class='px-4 py-3 font-medium'>{{ __('Stages') }}</th>
                            <th class='px-4 py-3 font-medium'>{{ __('Active') }}</th>
                            <th class='px-4 py-3 text-right font-medium'>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class='divide-y'>
                        @foreach ($modules as $moduleKey => $module)
                            @php
                                $matrix = $matrices->get($moduleKey);
                                $makerRoleNames = $matrix?->makerRoles->pluck('name')->all() ?? [];
                                $configureUrl = route('approval-matrices.edit', $moduleKey);
                            @endphp
                            <tr class='hover:bg-accent/40'>
                                <td class='px-4 py-4 font-medium'>{{ $module->label() }}</td>
                                <td class='px-4 py-4'>
                                    @if ($matrix)
                                        <x-ui.badge variant='outline'>{{ __('Version :version', ['version' => $matrix->configuration_version]) }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant='muted'>{{ __('Not configured') }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class='px-4 py-4 text-muted-foreground'>{{ $makerRoleNames ? implode(', ', $makerRoleNames) : __('None') }}</td>
                                <td class='px-4 py-4 text-muted-foreground'>{{ $matrix?->stages->count() ?? 0 }}</td>
                                <td class='px-4 py-4'>
                                    @if ($matrix?->is_active)
                                        <x-ui.badge variant='success'>{{ __('Active') }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant='muted'>{{ __('Inactive') }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class='px-4 py-4 text-right'>
                                    @can('approval-matrices.update')
                                        <x-ui.button variant='outline' size='sm' href='{{ $configureUrl }}'>
                                            {{ __('Configure') }}
                                        </x-ui.button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
