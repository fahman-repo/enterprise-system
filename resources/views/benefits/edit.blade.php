<x-app-layout :title="__('Edit benefit')">
    <div class="mx-auto flex max-w-4xl flex-col gap-6">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('benefits.index') }}"
                class="inline-flex size-9 items-center justify-center rounded-lg border border-input bg-background text-muted-foreground shadow-2xs transition-colors hover:bg-accent hover:text-foreground"
                title="{{ __('Back to Benefits') }}"
            >
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('Edit benefit') }}</h1>
                    @if ($benefit->is_active)
                        <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                    @else
                        <x-ui.badge variant="muted">{{ __('Inactive') }}</x-ui.badge>
                    @endif
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ __('Adjust the package limits and the grades and employment statuses eligible to enroll.') }}
                </p>
            </div>
        </div>

        @include('partials.flash')

        @include('benefits._form', ['benefit' => $benefit])
    </div>
</x-app-layout>
