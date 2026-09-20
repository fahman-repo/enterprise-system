<?php

namespace App\Providers;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            if (str_contains($ability, '.')) {
                [$menuSlug, $action] = explode('.', $ability, 2);

                return $user->canAccess($menuSlug, $action) ? true : null;
            }

            return null;
        });

        View::composer('layouts.app', function ($view) {
            $view->with('sidebarMenus', app(PermissionService::class)->sidebarForUser(auth()->user()));
        });
    }
}
