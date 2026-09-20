@props(['variant' => 'default'])

@php
    $variants = [
        'default' => 'border-transparent bg-primary text-primary-foreground',
        'secondary' => 'border-transparent bg-secondary text-secondary-foreground',
        'outline' => 'text-foreground',
        'destructive' => 'border-transparent bg-destructive text-destructive-foreground',
        'muted' => 'border-transparent bg-muted text-muted-foreground',
        'success' => 'border-transparent bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
    ];

    $classes = 'inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-medium '.($variants[$variant] ?? $variants['default']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</span>