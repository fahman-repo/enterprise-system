@props([
    'name' => '',
    'src' => null,
])

@php
    $initials = collect(explode(' ', trim($name)))
        ->filter()
        ->map(fn (string $part): string => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');

    $initials = mb_strtoupper($initials) ?: '?';
@endphp

<span {{ $attributes->merge(['class' => 'relative flex size-9 shrink-0 overflow-hidden rounded-full']) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name }}" class="aspect-square size-full object-cover">
    @else
        <span class="flex size-full items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground">
            {{ $initials }}
        </span>
    @endif
</span>