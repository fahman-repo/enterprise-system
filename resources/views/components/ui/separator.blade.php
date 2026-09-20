@props(['orientation' => 'horizontal'])

<div
    role="separator"
    {{ $attributes->merge(['class' => $orientation === 'vertical' ? 'h-full w-px shrink-0 bg-border' : 'h-px w-full shrink-0 bg-border']) }}
></div>