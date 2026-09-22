@props(['value'])

<div
    role="tabpanel"
    tabindex="0"
    x-bind:id="id + '-panel-' + @js($value)"
    x-bind:aria-labelledby="id + '-tab-' + @js($value)"
    x-show="tab === @js($value)"
    x-cloak
    {{ $attributes->merge(['class' => 'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none']) }}
>
    {{ $slot }}
</div>
