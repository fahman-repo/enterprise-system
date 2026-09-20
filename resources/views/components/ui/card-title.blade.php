@props(['as' => 'h3'])

<{{ $as }} {{ $attributes->merge(['class' => 'font-semibold leading-none tracking-tight']) }}>
    {{ $slot }}
</{{ $as }}>