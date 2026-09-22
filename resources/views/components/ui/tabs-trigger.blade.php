@props(['value'])

@php
    $classes = 'relative inline-flex items-center justify-center whitespace-nowrap px-3 py-2.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 after:absolute after:inset-x-0 after:-bottom-px after:h-0.5 after:rounded-full after:bg-foreground after:opacity-0 after:transition-opacity data-[state=active]:text-foreground data-[state=active]:after:opacity-100';
@endphp

<button
    type="button"
    role="tab"
    x-bind:id="id + '-tab-' + @js($value)"
    x-bind:aria-controls="id + '-panel-' + @js($value)"
    x-bind:aria-selected="tab === @js($value)"
    x-bind:data-state="tab === @js($value) ? 'active' : 'inactive'"
    x-bind:tabindex="tab === @js($value) ? 0 : -1"
    x-on:click="select(@js($value))"
    x-on:focus="select(@js($value))"
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</button>
