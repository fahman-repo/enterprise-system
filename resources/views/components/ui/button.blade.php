@props([
    'variant' => 'default',
    'size' => 'default',
    'href' => null,
    'type' => 'submit',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0';

    $variants = [
        'default' => 'bg-primary text-primary-foreground shadow-xs hover:bg-primary/90',
        'secondary' => 'bg-secondary text-secondary-foreground shadow-xs hover:bg-secondary/80',
        'outline' => 'border border-input bg-background shadow-xs hover:bg-accent hover:text-accent-foreground',
        'ghost' => 'hover:bg-accent hover:text-accent-foreground',
        'destructive' => 'bg-destructive text-destructive-foreground shadow-xs hover:bg-destructive/90',
        'link' => 'text-primary underline-offset-4 hover:underline',
    ];

    $sizes = [
        'sm' => 'h-8 px-3 text-xs',
        'default' => 'h-9 px-4 py-2',
        'lg' => 'h-10 px-6',
        'icon' => 'size-9',
    ];

    $classes = trim($base.' '.($variants[$variant] ?? $variants['default']).' '.($sizes[$size] ?? $sizes['default']));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif