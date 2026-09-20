@props(['variant' => 'default'])

@php
    $variants = [
        'default' => 'bg-background text-foreground',
        'destructive' => 'border-destructive/50 bg-destructive/5 text-destructive',
    ];

    $classes = 'relative flex w-full items-start gap-3 rounded-lg border px-4 py-3 text-sm '.($variants[$variant] ?? $variants['default']);
@endphp

<div role="alert" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>