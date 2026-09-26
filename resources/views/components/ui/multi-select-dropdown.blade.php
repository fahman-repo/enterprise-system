@props([
    'options' => [],
    'selected' => [],
    'name' => null,
    'id' => null,
    'placeholder' => __('Select options...'),
    'searchPlaceholder' => __('Search...'),
    'emptyText' => __('No options found.'),
    'ariaLabel' => null,
    'dynamicName' => null,
    'dynamicSelected' => null,
])

@php
    $id = $id ?? 'multiselect-'.\Illuminate\Support\Str::random(8);
@endphp

<div
    class="relative w-full"
    x-data="{
        open: false,
        search: '',
        options: @js($options),
        selected: @if($dynamicSelected) {{ $dynamicSelected }} @else @js(array_map('intval', (array) $selected)) @endif,
        get filteredOptions() {
            if (!this.search.trim()) return this.options;
            const query = this.search.toLowerCase();
            return this.options.filter(opt => opt.name.toLowerCase().includes(query));
        },
        get selectedObjects() {
            return this.options.filter(opt => this.selected.some(s => Number(s) === Number(opt.id)));
        },
        toggle(id) {
            const numId = Number(id);
            const idx = this.selected.findIndex(item => Number(item) === numId);
            if (idx > -1) {
                this.selected.splice(idx, 1);
            } else {
                this.selected.push(numId);
            }
        },
        isSelected(id) {
            return this.selected.some(item => Number(item) === Number(id));
        },
        selectAll() {
            const currentIds = this.filteredOptions.map(opt => Number(opt.id));
            const newSelected = Array.from(new Set([...this.selected.map(Number), ...currentIds]));
            this.selected.splice(0, this.selected.length, ...newSelected);
        },
        clearAll() {
            this.selected.splice(0, this.selected.length);
        },
        remove(id) {
            const idx = this.selected.findIndex(item => Number(item) === Number(id));
            if (idx > -1) {
                this.selected.splice(idx, 1);
            }
        }
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    @if ($dynamicName)
        <template x-for="val in selected" :key="val">
            <input type="hidden" :name="{{ $dynamicName }}" :value="val">
        </template>
    @elseif ($name)
        <template x-for="val in selected" :key="val">
            <input type="hidden" name="{{ $name }}" :value="val">
        </template>
    @endif
    {{-- Trigger Button / Field Box --}}
    <div
        id="{{ $id }}"
        role="combobox"
        aria-haspopup="listbox"
        :aria-expanded="open"
        aria-label="{{ $ariaLabel ?? $placeholder }}"
        tabindex="0"
        @click="open = !open"
        @keydown.enter.prevent="open = !open"
        @keydown.space.prevent="open = !open"
        class="flex min-h-9 w-full cursor-pointer items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-1.5 text-sm shadow-xs transition-colors hover:bg-accent/40 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:ring-offset-background"
        :class="{ 'ring-2 ring-ring ring-offset-2 ring-offset-background border-ring': open }"
    >
        <div class="flex flex-1 flex-wrap items-center gap-1.5 overflow-hidden">
            <template x-if="selectedObjects.length === 0">
                <span class="text-muted-foreground select-none">{{ $placeholder }}</span>
            </template>

            <template x-for="item in selectedObjects" :key="item.id">
                <span
                    class="inline-flex items-center gap-1 rounded-md border border-border bg-secondary/80 px-2 py-0.5 text-xs font-medium text-secondary-foreground shadow-2xs transition-colors hover:bg-secondary"
                    @click.stop
                >
                    <span x-text="item.name" class="max-w-36 truncate"></span>
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground focus:outline-none"
                        @click.stop="remove(item.id)"
                        aria-label="{{ __('Remove') }}"
                    >
                        <svg class="size-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
            </template>
        </div>

        <div class="flex items-center gap-1 shrink-0 text-muted-foreground">
            <template x-if="selected.length > 0">
                <button
                    type="button"
                    class="rounded p-0.5 hover:text-foreground focus:outline-none"
                    @click.stop="clearAll"
                    title="{{ __('Clear all') }}"
                >
                    <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </template>

            <svg
                class="size-4 transition-transform duration-200"
                :class="{ 'rotate-180': open }"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
        </div>
    </div>



    {{-- Dropdown Menu Popover --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1 scale-98"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-1 scale-98"
        style="display: none;"
        class="absolute left-0 right-0 z-50 mt-1 max-h-72 rounded-lg border bg-popover text-popover-foreground shadow-lg backdrop-blur-md"
    >
        <div class="border-b border-border p-2">
            <div class="relative">
                <input
                    type="text"
                    x-model="search"
                    @click.stop
                    placeholder="{{ $searchPlaceholder }}"
                    class="w-full rounded-md border border-input bg-background/80 px-2.5 py-1 text-xs placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                >
                <template x-if="search">
                    <button
                        type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        @click.stop="search = ''"
                    >
                        <svg class="size-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </template>
            </div>

            <div class="mt-1.5 flex items-center justify-between px-0.5 text-xs text-muted-foreground">
                <span x-text="selected.length + ' ' + @js(__('selected'))"></span>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click.stop="selectAll"
                        class="hover:text-foreground underline-offset-2 hover:underline"
                    >
                        {{ __('Select all') }}
                    </button>
                    <span>&bull;</span>
                    <button
                        type="button"
                        @click.stop="clearAll"
                        class="hover:text-foreground underline-offset-2 hover:underline"
                    >
                        {{ __('Clear') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="max-h-48 overflow-y-auto p-1 scrollbar-thin">
            <template x-for="option in filteredOptions" :key="option.id">
                <div
                    @click.stop="toggle(option.id)"
                    class="flex cursor-pointer items-center justify-between rounded-md px-2.5 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                    :class="{ 'bg-accent/50 font-medium': isSelected(option.id) }"
                >
                    <span x-text="option.name" class="truncate"></span>

                    <div
                        class="flex size-4 items-center justify-center rounded-xs border transition-colors"
                        :class="isSelected(option.id) ? 'border-primary bg-primary text-primary-foreground' : 'border-input bg-background'"
                    >
                        <template x-if="isSelected(option.id)">
                            <svg class="size-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="filteredOptions.length === 0">
                <div class="py-4 text-center text-xs text-muted-foreground">
                    {{ $emptyText }}
                </div>
            </template>
        </div>
    </div>
</div>
