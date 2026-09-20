@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'flex h-9 w-full appearance-none rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 dark:[&>option]:bg-popover dark:[&>option]:text-popover-foreground']) }}>
    {{ $slot }}
</select>
