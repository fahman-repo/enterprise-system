@props([
    'label',
    'value',
    'delta' => null,
    'trend' => 'up',
    'href' => null,
    'caption' => null,
    'icon' => null,
])

@php
    $cardClasses = $href === null ? '' : 'h-full transition-colors hover:border-ring';
@endphp

@if ($href)
    <a href="{{ $href }}"
        class="block rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background">
@endif

<x-ui.card :class="$cardClasses">
    <x-ui.card-header>
        <x-ui.card-description class="flex items-center gap-2">
            @if ($icon)
                <x-dynamic-component :component="'icon.'.$icon" class="text-muted-foreground" />
            @endif

            {{ $label }}
        </x-ui.card-description>

        <x-ui.card-title class="text-2xl">{{ $value }}</x-ui.card-title>

        @if ($caption)
            <p class="text-xs text-muted-foreground">{{ $caption }}</p>
        @endif
    </x-ui.card-header>

    @if ($delta)
        <x-ui.card-content>
            <p @class([
                'flex items-center gap-1 text-xs font-medium',
                'text-emerald-600 dark:text-emerald-400' => $trend === 'up',
                'text-destructive' => $trend !== 'up',
            ])>
                {{ $delta }}
                <span class="text-muted-foreground">{{ __('vs last month') }}</span>
            </p>
        </x-ui.card-content>
    @endif
</x-ui.card>

@if ($href)
    </a>
@endif
