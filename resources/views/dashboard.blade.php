<x-app-layout :title="__('Dashboard')">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('Dashboard') }}</h1>
            <p class="text-sm text-muted-foreground">{{ __('Overview of your workspace. Figures below are placeholders.') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card :label="__('Total users')" value="1,204" delta="+12.5%" trend="up" />
            <x-stat-card :label="__('Active sessions')" value="312" delta="+4.1%" trend="up" />
            <x-stat-card :label="__('Pending invites')" value="18" delta="-2.0%" trend="down" />
            <x-stat-card :label="__('Monthly revenue')" value="$48.2K" delta="+8.9%" trend="up" />
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <x-ui.card class="lg:col-span-2">
                <x-ui.card-header>
                    <x-ui.card-title>{{ __('Activity') }}</x-ui.card-title>
                    <x-ui.card-description>{{ __('Recent workspace events will appear here.') }}</x-ui.card-description>
                </x-ui.card-header>
                <x-ui.card-content>
                    <div class="flex min-h-48 flex-col items-center justify-center gap-3 rounded-lg border border-dashed p-8 text-center">
                        <div class="flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground">
                            <x-icon.layout-dashboard />
                        </div>
                        <div class="flex flex-col gap-1">
                            <p class="text-sm font-medium">{{ __('Nothing here yet') }}</p>
                            <p class="text-sm text-muted-foreground">{{ __('Data will appear once your workspace is connected.') }}</p>
                        </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header>
                    <x-ui.card-title>{{ __('Quick actions') }}</x-ui.card-title>
                    <x-ui.card-description>{{ __('Placeholder shortcuts.') }}</x-ui.card-description>
                </x-ui.card-header>
                <x-ui.card-content>
                    <div class="flex flex-col gap-2">
                        <x-ui.button variant="outline" class="w-full justify-start" disabled>
                            <x-icon.users />
                            {{ __('Invite a user') }}
                        </x-ui.button>
                        <x-ui.button variant="outline" class="w-full justify-start" disabled>
                            <x-icon.settings />
                            {{ __('Workspace settings') }}
                        </x-ui.button>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>