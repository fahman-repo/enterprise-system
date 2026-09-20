@props([
    'href' => null,
    'variant' => 'default',
])

@php
    $base = 'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground disabled:pointer-events-none disabled:opacity-50 [&_svg]:size-4 [&_svg]:shrink-0';

    $variants = [
        'default' => '',
        'destructive' => 'text-destructive focus:bg-destructive/10 focus:text-destructive',
    ];

    $classes = trim($base.' '.($variants[$variant] ?? $variants['default']));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif