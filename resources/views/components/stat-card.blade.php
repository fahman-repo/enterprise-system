@props([
    'label',
    'value',
    'delta' => null,
    'trend' => 'up',
])

<x-ui.card>
    <x-ui.card-header>
        <x-ui.card-description>{{ $label }}</x-ui.card-description>
        <x-ui.card-title class="text-2xl">{{ $value }}</x-ui.card-title>
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