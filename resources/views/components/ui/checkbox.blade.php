@props(['disabled' => false])

<input type="checkbox" @disabled($disabled) {{ $attributes->merge(['class' => 'size-4 shrink-0 rounded-sm border border-input shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50']) }}>