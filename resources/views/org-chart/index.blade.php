<x-app-layout :title="__('Organization Chart')">
    <div class="mx-auto flex w-full max-w-none flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('Organization Chart') }}</h1>
            <p class="text-sm text-muted-foreground">{{ __('Explore the company structure by division, department, org unit and reporting line.') }}</p>
        </div>

        @include('partials.flash')

        <x-ui.card>
            <div class="flex flex-wrap items-center gap-2 border-b px-4 py-3">
                <x-ui.button type="button" variant="default" data-org-chart-mode="structure">
                    {{ __('Structure') }}
                </x-ui.button>

                <x-ui.button type="button" variant="outline" data-org-chart-mode="reporting">
                    {{ __('Reporting') }}
                </x-ui.button>

                <div class="w-48">
                    <x-ui.select data-org-chart-location>
                        <option value="">{{ __('All locations') }}</option>
                        @foreach ($workLocations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <form data-org-chart-search-form class="flex items-center gap-2">
                    <div class="w-56">
                        <x-ui.input type="search" data-org-chart-search placeholder="{{ __('Search name or code') }}" />
                    </div>
                    <x-ui.button type="submit" variant="outline">
                        {{ __('Search') }}
                    </x-ui.button>
                    <span data-org-chart-search-empty class="hidden text-sm text-destructive">{{ __('No matching node found.') }}</span>
                </form>

                <div class="ml-auto flex flex-wrap items-center gap-2">
                    <x-ui.button type="button" variant="outline" data-org-chart-action="expandAll">
                        {{ __('Expand all') }}
                    </x-ui.button>

                    <x-ui.button type="button" variant="outline" data-org-chart-action="collapseAll">
                        {{ __('Collapse all') }}
                    </x-ui.button>

                    <x-ui.button type="button" variant="outline" data-org-chart-action="fit">
                        {{ __('Fit') }}
                    </x-ui.button>

                    <x-ui.button type="button" variant="outline" data-org-chart-action="fullscreen">
                        {{ __('Fullscreen') }}
                    </x-ui.button>

                    <x-ui.button type="button" variant="outline" data-org-chart-action="export">
                        {{ __('Export PNG') }}
                    </x-ui.button>
                </div>
            </div>

            <div
                data-org-chart
                data-url="{{ route('org-chart.data') }}"
                data-empty-message="{{ __('No organization data to display.') }}"
                data-error-message="{{ __('Failed to load organization data.') }}"
                class="h-[70vh] min-h-[480px] w-full overflow-hidden rounded-b-xl bg-muted/30"
            ></div>

            <div data-org-chart-empty class="hidden p-10 text-center text-sm text-muted-foreground">
                {{ __('No organization data to display.') }}
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
