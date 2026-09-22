@props([])

<div
    role="tablist"
    aria-orientation="horizontal"
    x-on:keydown.arrow-right.prevent="moveFocus(1)"
    x-on:keydown.arrow-left.prevent="moveFocus(-1)"
    x-on:keydown.home.prevent="moveFocus('first')"
    x-on:keydown.end.prevent="moveFocus('last')"
    {{ $attributes->merge(['class' => 'flex items-center gap-1 overflow-x-auto overflow-y-hidden border-b']) }}
>
    {{ $slot }}
</div>
