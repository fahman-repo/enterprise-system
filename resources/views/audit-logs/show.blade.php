<x-app-layout :title="__('Audit entry')">
    <div class="mx-auto flex max-w-4xl flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-semibold tracking-tight">{{ __('Audit entry') }}</h1>
                    <x-ui.badge :variant="$activity->variant()">{{ $activity->eventLabel() }}</x-ui.badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ $activity->created_at?->format('Y-m-d H:i:s') }} · {{ $activity->causerName() }}
                </p>
            </div>

            <x-ui.button variant="outline" href="{{ route('audit-logs.index') }}">
                {{ __('Back to audit logs') }}
            </x-ui.button>
        </div>

        @php
            $subjectRoutes = [
                \App\Models\User::class => 'users.edit',
                \App\Models\Role::class => 'roles.edit',
                \App\Models\Menu::class => 'menus.edit',
            ];
            $subjectRoute = $subjectRoutes[$activity->subject_type] ?? null;
            $causerSnapshot = $activity->getExtraProperty('causer');
            $changes = $activity->changesForDisplay();
            $fieldChanges = collect($changes)->reject(
                fn (array $change, string $attribute): bool => str_starts_with($attribute, 'permissions_')
            );
            $permissionsChanged = $fieldChanges->count() !== count($changes);
        @endphp

        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>{{ __('Metadata') }}</x-ui.card-title>
            </x-ui.card-header>

            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 border-t p-6 text-sm sm:grid-cols-2">
                <div class="flex flex-col gap-1">
                    <dt class="text-muted-foreground">{{ __('Event') }}</dt>
                    <dd>{{ $activity->eventLabel() }}</dd>
                </div>
                <div class="flex flex-col gap-1">
                    <dt class="text-muted-foreground">{{ __('Log') }}</dt>
                    <dd>{{ $activity->log_name }}</dd>
                </div>
                <div class="flex flex-col gap-1">
                    <dt class="text-muted-foreground">{{ __('Actor') }}</dt>
                    <dd>
                        {{ $activity->causerName() }}
                        @if (is_array($causerSnapshot) && filled($causerSnapshot['email'] ?? null))
                            <span class="text-muted-foreground">({{ $causerSnapshot['email'] }})</span>
                        @endif
                    </dd>
                </div>
                <div class="flex flex-col gap-1">
                    <dt class="text-muted-foreground">{{ __('IP address') }}</dt>
                    <dd>{{ $activity->getExtraProperty('ip') ?? '—' }}</dd>
                </div>
                <div class="col-span-full flex flex-col gap-1">
                    <dt class="text-muted-foreground">{{ __('User agent') }}</dt>
                    <dd class="break-all">{{ $activity->getExtraProperty('user_agent') ?? '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>{{ __('Record') }}</x-ui.card-title>
            </x-ui.card-header>

            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 border-t p-6 text-sm sm:grid-cols-2">
                <div class="flex flex-col gap-1">
                    <dt class="text-muted-foreground">{{ __('Type') }}</dt>
                    <dd>{{ $activity->subjectTypeLabel() }}</dd>
                </div>
                <div class="flex flex-col gap-1">
                    <dt class="text-muted-foreground">{{ __('Identifier') }}</dt>
                    <dd>{{ $activity->subject_id ?? '—' }}</dd>
                </div>
                <div class="flex flex-col gap-1">
                    <dt class="text-muted-foreground">{{ __('Label') }}</dt>
                    <dd>{{ $activity->subjectLabel() }}</dd>
                </div>
                <div class="flex items-center gap-2">
                    @if ($activity->subject && $subjectRoute)
                        @can($activity->log_name.'.update')
                            <x-ui.button variant="outline" size="sm" href="{{ route($subjectRoute, $activity->subject) }}">
                                {{ __('View record') }}
                            </x-ui.button>
                        @endcan
                    @endif
                </div>
            </dl>
        </x-ui.card>

        @if ($fieldChanges->isNotEmpty())
            <x-ui.card>
                <x-ui.card-header>
                    <x-ui.card-title>{{ __('Changes') }}</x-ui.card-title>
                </x-ui.card-header>

                <div class="overflow-x-auto border-t">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="px-4 py-3 font-medium">{{ __('Attribute') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Before') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('After') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($fieldChanges as $attribute => $change)
                                <tr class="align-top">
                                    <td class="px-4 py-3 font-medium">{{ $attribute }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">
                                        <span class="break-all">{{ $activity->formatValue($change['old']) }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="break-all">{{ $activity->formatValue($change['new']) }}</span>
                                    </td>
                                </tr>
                            @endforeach

                            @if ($activity->getExtraProperty('password_changed') === true)
                                <tr class="align-top">
                                    <td class="px-4 py-3 font-medium">{{ __('password') }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ __('hidden') }}</td>
                                    <td class="px-4 py-3">{{ __('changed') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endif

        @if ($permissionsChanged)
            @php
                $menuLabels = \App\Models\Menu::query()->pluck('name', 'id');
                $before = collect($activity->permissionRows($changes['permissions_before']['old'] ?? []));
                $after = collect($activity->permissionRows($changes['permissions_after']['new'] ?? []));
            @endphp

            <x-ui.card>
                <x-ui.card-header>
                    <x-ui.card-title>{{ __('Permission matrix') }}</x-ui.card-title>
                </x-ui.card-header>

                <div class="grid grid-cols-1 gap-6 border-t p-6 text-sm sm:grid-cols-2">
                    <div class="flex flex-col gap-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ __('Before') }}</p>

                        @forelse ($before as $row)
                            <div class="flex flex-col gap-1">
                                <span class="font-medium">{{ $menuLabels[$row['menu_id']] ?? '#'.$row['menu_id'] }}</span>
                                <span class="text-muted-foreground">{{ $row['actions'] === [] ? __('No permissions') : implode(', ', $row['actions']) }}</span>
                            </div>
                        @empty
                            <p class="text-muted-foreground">{{ __('No permissions') }}</p>
                        @endforelse
                    </div>

                    <div class="flex flex-col gap-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ __('After') }}</p>

                        @forelse ($after as $row)
                            <div class="flex flex-col gap-1">
                                <span class="font-medium">{{ $menuLabels[$row['menu_id']] ?? '#'.$row['menu_id'] }}</span>
                                <span class="text-muted-foreground">{{ $row['actions'] === [] ? __('No permissions') : implode(', ', $row['actions']) }}</span>
                            </div>
                        @empty
                            <p class="text-muted-foreground">{{ __('No permissions') }}</p>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>
        @endif

        @if ($changes === [] && ! $permissionsChanged)
            @php
                $properties = collect($activity->properties)->except(['attributes', 'old', 'causer']);
            @endphp

            <x-ui.card>
                <x-ui.card-header>
                    <x-ui.card-title>{{ __('Details') }}</x-ui.card-title>
                </x-ui.card-header>

                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 border-t p-6 text-sm sm:grid-cols-2">
                    @foreach ($properties as $key => $value)
                        <div class="flex flex-col gap-1">
                            <dt class="text-muted-foreground">{{ str_replace('_', ' ', $key) }}</dt>
                            <dd>{{ $activity->formatValue($value) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-ui.card>
        @endif
    </div>
</x-app-layout>
