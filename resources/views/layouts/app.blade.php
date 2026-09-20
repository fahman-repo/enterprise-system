<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' | '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">

        @include('partials.theme-script')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <div class="min-h-screen">
            <aside data-sidebar data-open="false" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r bg-sidebar text-sidebar-foreground transition-transform duration-200 data-[open=true]:translate-x-0 lg:translate-x-0">
                <div class="flex h-16 items-center gap-2 border-b px-5">
                    <div class="flex size-8 items-center justify-center rounded-lg border bg-background">
                        <x-icon.layout-dashboard class="size-4" />
                    </div>
                    <span class="text-sm font-semibold tracking-tight">{{ config('app.name', 'Laravel') }}</span>
                </div>

                <nav class="flex flex-1 flex-col gap-1 p-3">
                    <x-ui.sidebar-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                        <x-slot:icon>
                            <x-icon.layout-dashboard />
                        </x-slot:icon>
                        {{ __('Dashboard') }}
                    </x-ui.sidebar-link>

                    @foreach ($sidebarMenus ?? collect() as $item)
                        @if ($item->children->isNotEmpty())
                            <p class="px-3 pt-4 pb-1 text-xs font-medium text-muted-foreground">{{ $item->name }}</p>

                            @foreach ($item->children as $child)
                                @php
                                    $href = $child->route_name && Route::has($child->route_name) ? route($child->route_name) : '#';
                                    $active = $child->route_name && request()->routeIs($child->route_name);
                                @endphp

                                <x-ui.sidebar-link :href="$href" :active="$active" class="ml-3">
                                    @if ($child->icon)
                                        <x-slot:icon>
                                            <x-dynamic-component :component="'icon.'.$child->icon" />
                                        </x-slot:icon>
                                    @endif
                                    {{ $child->name }}
                                </x-ui.sidebar-link>
                            @endforeach
                        @else
                            @php
                                $href = $item->route_name && Route::has($item->route_name) ? route($item->route_name) : '#';
                                $active = $item->route_name && request()->routeIs($item->route_name);
                            @endphp

                            <x-ui.sidebar-link :href="$href" :active="$active">
                                @if ($item->icon)
                                    <x-slot:icon>
                                        <x-dynamic-component :component="'icon.'.$item->icon" />
                                    </x-slot:icon>
                                @endif
                                {{ $item->name }}
                            </x-ui.sidebar-link>
                        @endif
                    @endforeach
                </nav>
            </aside>

            <div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden"></div>

            <div class="flex min-h-screen flex-col lg:pl-64">
                <header class="sticky top-0 z-20 flex h-16 items-center gap-2 border-b bg-background/80 px-4 backdrop-blur sm:px-6">
                    <button type="button" data-sidebar-toggle class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground lg:hidden" aria-label="{{ __('Toggle navigation') }}">
                        <x-icon.menu />
                    </button>

                    <div class="flex-1">
                        <h1 class="text-sm font-medium">{{ $title ?? __('Dashboard') }}</h1>
                    </div>

                    <button type="button" data-theme-toggle class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground" aria-label="{{ __('Toggle theme') }}">
                        <x-icon.sun class="dark:hidden" />
                        <x-icon.moon class="hidden dark:block" />
                    </button>

                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <button type="button" data-dropdown-trigger aria-expanded="false" aria-haspopup="menu" class="flex items-center gap-2 rounded-md p-1 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                                <x-ui.avatar :name="auth()->user()->name" />
                                <span class="hidden max-w-32 truncate font-medium sm:block">{{ auth()->user()->name }}</span>
                                <x-icon.chevron-down class="text-muted-foreground" />
                            </button>
                        </x-slot:trigger>

                        <div class="flex flex-col border-b px-2 py-1.5">
                            <p class="text-sm font-medium">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-muted-foreground">{{ auth()->user()->email }}</p>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="p-1">
                            @csrf
                            <x-ui.dropdown-item variant="destructive">
                                <x-icon.log-out />
                                {{ __('Log out') }}
                            </x-ui.dropdown-item>
                        </form>
                    </x-ui.dropdown>
                </header>

                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>