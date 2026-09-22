@props([
    'filters',
    'label' => __('Filter by :'),
    'placeholder' => __('Choose…'),
    'valuePlaceholder' => __('Choose a value…'),
])

@php
    $activeField = null;
    $activeValue = '';

    foreach ($filters as $name => $filter) {
        if (request()->filled($name)) {
            $activeField = $name;
            $activeValue = (string) request($name);

            break;
        }
    }

    $filterConfig = collect($filters)
        ->map(fn (array $filter, string $name): array => [
            'name' => $name,
            'label' => $filter['label'],
            'all' => $filter['all'],
            'options' => $filter['options'],
        ])
        ->values()
        ->all();
@endphp

<div
    class="flex flex-wrap items-center gap-3"
    x-data="{
        field: @js($activeField ?? ''),
        value: @js($activeValue),
        valueLabel: @js(__('Filter value')),
        filters: @js($filterConfig),
        get current() {
            return this.filters.find((filter) => filter.name === this.field) ?? null
        },
        get options() {
            return this.current ? this.current.options : []
        },
    }"
>
    <span class="text-sm text-muted-foreground">{{ $label }}</span>

    {{-- Widths wrap the select because the shared select component always applies w-full. --}}
    <div class="w-40">
        <x-ui.select
            x-model="field"
            @change="value = ''"
            aria-label="{{ __('Filter field') }}"
        >
            <option value="">{{ $placeholder }}</option>

            @foreach ($filters as $name => $filter)
                <option value="{{ $name }}" @selected($name === $activeField)>{{ $filter['label'] }}</option>
            @endforeach
        </x-ui.select>
    </div>

    <div class="w-48">
        <x-ui.select
            x-model="value"
            x-bind:name="field"
            x-bind:disabled="!field"
            x-bind:aria-label="current ? current.label : valueLabel"
            @change="$el.form.requestSubmit()"
        >
            <option value="" x-text="current ? current.all : @js($valuePlaceholder)"></option>

            <template x-for="option in options" :key="option.value">
                <option :value="option.value" x-text="option.label"></option>
            </template>
        </x-ui.select>
    </div>
</div>
