<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">

        @include('partials.theme-script')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-10">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,var(--muted),var(--background))]"></div>
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(to_right,var(--border)_1px,transparent_1px),linear-gradient(to_bottom,var(--border)_1px,transparent_1px)] bg-[size:48px_48px] opacity-40 [mask-image:radial-gradient(ellipse_at_center,black,transparent_70%)]"></div>

            <div class="mb-8 flex flex-col items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl border bg-card shadow-sm">
                    <x-icon.layout-dashboard />
                </div>
                <div class="flex flex-col gap-1 text-center">
                    <h1 class="text-lg font-semibold tracking-tight">{{ config('app.name', 'Laravel') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ __('Sign in to your account') }}</p>
                </div>
            </div>

            <div class="w-full max-w-sm">
                {{ $slot }}
            </div>

            <p class="mt-8 text-center text-xs text-muted-foreground">
                &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. {{ __('All rights reserved.') }}
            </p>
        </div>
    </body>
</html>