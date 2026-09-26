<x-app-layout :title="$site->name">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        @include('partials.flash')

        <x-ui.card>
            <x-ui.card-content class="pt-6">
                <div class="flex flex-wrap items-start justify-between gap-6">
                    <div class="flex flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl font-semibold tracking-tight">{{ $site->name }}</h1>

                            @if ($site->is_active)
                                <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                            @else
                                <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                            @endif

                            <x-ui.badge variant="outline">{{ $site->typeLabel() }}</x-ui.badge>
                        </div>

                        <p class="text-sm text-muted-foreground">
                            <span class="font-mono">{{ $site->code }}</span>
                            @if ($site->parent)
                                · <a href="{{ route('sites.show', $site->parent) }}" class="hover:underline">{{ $site->parent->name }}</a>
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-ui.button variant="outline" href="{{ route('sites.index') }}">
                            {{ __('Back') }}
                        </x-ui.button>

                        @can('sites.update')
                            <x-ui.button href="{{ route('sites.edit', $site) }}">
                                {{ __('Edit') }}
                            </x-ui.button>
                        @endcan
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.tabs default="overview">
            <x-ui.card>
                <x-ui.tabs-list class="px-6">
                    <x-ui.tabs-trigger value="overview">{{ __('Overview') }}</x-ui.tabs-trigger>
                    <x-ui.tabs-trigger value="hierarchy">{{ __('Hierarchy') }}</x-ui.tabs-trigger>
                    <x-ui.tabs-trigger value="employees">{{ __('Employees') }}</x-ui.tabs-trigger>
                </x-ui.tabs-list>

                <x-ui.tabs-content value="overview" class="p-6">
                    @php
                        $overview = [
                            __('Description') => $site->description,
                            __('Address') => $site->address,
                            __('City') => $site->city,
                            __('Province') => $site->province,
                            __('Postal code') => $site->postal_code,
                            __('Phone') => $site->phone,
                            __('Email') => $site->email,
                            __('Notes') => $site->notes,
                        ];
                    @endphp

                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($overview as $label => $value)
                            <div class="flex flex-col gap-1">
                                <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $label }}</dt>
                                <dd class="text-sm">{{ $value ?? __('—') }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.tabs-content>

                <x-ui.tabs-content value="hierarchy" class="p-6">
                    <div class="flex flex-col gap-6">
                        <div class="flex flex-col gap-2">
                            <h2 class="text-sm font-semibold">{{ __('Location in the hierarchy') }}</h2>

                            <nav class="flex flex-wrap items-center gap-1 text-sm">
                                @forelse ($trail as $ancestor)
                                    <a href="{{ route('sites.show', $ancestor) }}" class="hover:underline">{{ $ancestor->name }}</a>
                                    <span class="text-muted-foreground">›</span>
                                @empty
                                    <span class="text-muted-foreground">{{ __('This site is a root site.') }}</span>
                                @endforelse

                                <span class="font-medium">{{ $site->name }}</span>
                            </nav>
                        </div>

                        <div class="flex flex-col gap-2">
                            <h2 class="text-sm font-semibold">{{ __('Child sites') }}</h2>

                            @if ($site->children->isEmpty())
                                <p class="text-sm text-muted-foreground">{{ __('No child sites.') }}</p>
                            @else
                                <ul class="divide-y rounded-lg border border-border">
                                    @foreach ($site->children as $child)
                                        <li class="flex items-center justify-between gap-4 px-4 py-3">
                                            <div class="flex flex-col">
                                                <a href="{{ route('sites.show', $child) }}" class="text-sm font-medium hover:underline">{{ $child->name }}</a>
                                                <span class="font-mono text-xs text-muted-foreground">{{ $child->code }}</span>
                                            </div>

                                            <x-ui.badge variant="outline">{{ $child->typeLabel() }}</x-ui.badge>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </x-ui.tabs-content>

                <x-ui.tabs-content value="employees" class="p-6">
                    @if ($employees->isEmpty())
                        <p class="text-sm text-muted-foreground">{{ __('No employees assigned to this site.') }}</p>
                    @else
                        <ul class="divide-y rounded-lg border border-border">
                            @foreach ($employees as $employee)
                                <li class="flex items-center justify-between gap-4 px-4 py-3">
                                    <a href="{{ route('employees.show', $employee) }}" class="text-sm font-medium hover:underline">{{ $employee->name }}</a>
                                    <span class="font-mono text-xs text-muted-foreground">{{ $employee->employee_number }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.tabs-content>
            </x-ui.card>
        </x-ui.tabs>
    </div>
</x-app-layout>