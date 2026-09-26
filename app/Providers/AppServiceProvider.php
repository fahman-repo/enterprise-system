<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Approvals\ApprovalModule;
use App\Services\Approvals\ApprovalModuleRegistry;
use App\Services\Approvals\BrandsApprovalModule;
use App\Services\Approvals\CategoriesApprovalModule;
use App\Services\Approvals\DepartmentsApprovalModule;
use App\Services\Approvals\DivisionsApprovalModule;
use App\Services\Approvals\EducationLevelsApprovalModule;
use App\Services\Approvals\EmployeesApprovalModule;
use App\Services\Approvals\EmploymentStatusesApprovalModule;
use App\Services\Approvals\EntitiesApprovalModule;
use App\Services\Approvals\GradesApprovalModule;
use App\Services\Approvals\MaritalStatusesApprovalModule;
use App\Services\Approvals\MenusApprovalModule;
use App\Services\Approvals\OrgUnitsApprovalModule;
use App\Services\Approvals\PositionsApprovalModule;
use App\Services\Approvals\ProductsApprovalModule;
use App\Services\Approvals\ReligionsApprovalModule;
use App\Services\Approvals\RolesApprovalModule;
use App\Services\Approvals\UnitsApprovalModule;
use App\Services\Approvals\UsersApprovalModule;
use App\Services\Approvals\WorkLocationsApprovalModule;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Every module supporting the maker-checker workflow, in sidebar
     * menu order — this drives the Approval Matrix page and the
     * Approvals module filter.
     *
     * @var list<class-string<ApprovalModule>>
     */
    protected const APPROVAL_MODULES = [
        UsersApprovalModule::class,
        RolesApprovalModule::class,
        MenusApprovalModule::class,
        EntitiesApprovalModule::class,
        ProductsApprovalModule::class,
        CategoriesApprovalModule::class,
        BrandsApprovalModule::class,
        UnitsApprovalModule::class,
        EmployeesApprovalModule::class,
        DivisionsApprovalModule::class,
        DepartmentsApprovalModule::class,
        OrgUnitsApprovalModule::class,
        PositionsApprovalModule::class,
        GradesApprovalModule::class,
        EmploymentStatusesApprovalModule::class,
        WorkLocationsApprovalModule::class,
        ReligionsApprovalModule::class,
        EducationLevelsApprovalModule::class,
        MaritalStatusesApprovalModule::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ApprovalModuleRegistry::class, function ($app): ApprovalModuleRegistry {
            $registry = new ApprovalModuleRegistry;

            foreach (self::APPROVAL_MODULES as $module) {
                $registry->register($app->make($module));
            }

            return $registry;
        });
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
