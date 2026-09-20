@props([])

<div {{ $attributes->merge(['class' => 'rounded-xl border bg-card text-card-foreground shadow-sm']) }}>
    {{ $slot }}
</div>