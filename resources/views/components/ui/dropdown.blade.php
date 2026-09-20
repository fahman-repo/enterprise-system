@props(['align' => 'end'])

<div class="relative" data-dropdown data-open="false">
    {{ $trigger }}

    <div
        data-dropdown-menu
        hidden
        class="absolute z-50 mt-2 w-56 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md {{ $align === 'end' ? 'right-0' : 'left-0' }}"
    >
        {{ $slot }}
    </div>
</div>