@props([
    'href' => '#',
    'active' => false,
    'disabled' => false,
])

@php
    $classes = 'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors [&_svg]:size-4 [&_svg]:shrink-0';

    if ($disabled) {
        $classes .= ' pointer-events-none text-muted-foreground/60';
    } else {
        $classes .= ' text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground';
    }

    if ($active && ! $disabled) {
        $classes .= ' bg-sidebar-accent text-sidebar-accent-foreground';
    }
@endphp

<a
    href="{{ $disabled ? '#' : $href }}"
    @if ($disabled) aria-disabled="true" tabindex="-1" @endif
    @if ($active) aria-current="page" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @isset($icon)
        <span class="text-muted-foreground">{{ $icon }}</span>
    @endisset

    <span>{{ $slot }}</span>
</a>