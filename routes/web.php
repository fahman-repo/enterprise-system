<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BrandsController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\DepartmentsController;
use App\Http\Controllers\DivisionsController;
use App\Http\Controllers\EducationLevelsController;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\EmploymentStatusesController;
use App\Http\Controllers\GradesController;
use App\Http\Controllers\MaritalStatusesController;
use App\Http\Controllers\MenusController;
use App\Http\Controllers\OrgUnitsController;
use App\Http\Controllers\PositionsController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\ReligionsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UnitsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\WorkLocationsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('users', [UsersController::class, 'index'])
        ->middleware('menu.permission:users,view')->name('users.index');
    Route::get('users/create', [UsersController::class, 'create'])
        ->middleware('menu.permission:users,create')->name('users.create');
    Route::post('users', [UsersController::class, 'store'])
        ->middleware('menu.permission:users,create')->name('users.store');
    Route::get('users/{user}/edit', [UsersController::class, 'edit'])
        ->middleware('menu.permission:users,update')->name('users.edit');
    Route::put('users/{user}', [UsersController::class, 'update'])
        ->middleware('menu.permission:users,update')->name('users.update');
    Route::delete('users/{user}', [UsersController::class, 'destroy'])
        ->middleware('menu.permission:users,delete')->name('users.destroy');

    Route::get('roles', [RolesController::class, 'index'])
        ->middleware('menu.permission:roles,view')->name('roles.index');
    Route::get('roles/create', [RolesController::class, 'create'])
        ->middleware('menu.permission:roles,create')->name('roles.create');
    Route::post('roles', [RolesController::class, 'store'])
        ->middleware('menu.permission:roles,create')->name('roles.store');
    Route::get('roles/{role}/edit', [RolesController::class, 'edit'])
        ->middleware('menu.permission:roles,update')->name('roles.edit');
    Route::put('roles/{role}', [RolesController::class, 'update'])
        ->middleware('menu.permission:roles,update')->name('roles.update');
    Route::delete('roles/{role}', [RolesController::class, 'destroy'])
        ->middleware('menu.permission:roles,delete')->name('roles.destroy');

    Route::get('menus', [MenusController::class, 'index'])
        ->middleware('menu.permission:menus,view')->name('menus.index');
    Route::get('menus/create', [MenusController::class, 'create'])
        ->middleware('menu.permission:menus,create')->name('menus.create');
    Route::post('menus', [MenusController::class, 'store'])
        ->middleware('menu.permission:menus,create')->name('menus.store');
    Route::get('menus/{menu}/edit', [MenusController::class, 'edit'])
        ->middleware('menu.permission:menus,update')->name('menus.edit');
    Route::put('menus/{menu}', [MenusController::class, 'update'])
        ->middleware('menu.permission:menus,update')->name('menus.update');
    Route::delete('menus/{menu}', [MenusController::class, 'destroy'])
        ->middleware('menu.permission:menus,delete')->name('menus.destroy');

    Route::get('audit-logs', [AuditLogController::class, 'index'])
        ->middleware('menu.permission:audit-logs,view')->name('audit-logs.index');
    Route::get('audit-logs/{activity}', [AuditLogController::class, 'show'])
        ->middleware('menu.permission:audit-logs,view')->name('audit-logs.show');

    Route::get('products', [ProductsController::class, 'index'])
        ->middleware('menu.permission:products,view')->name('products.index');
    Route::get('products/create', [ProductsController::class, 'create'])
        ->middleware('menu.permission:products,create')->name('products.create');
    Route::post('products', [ProductsController::class, 'store'])
        ->middleware('menu.permission:products,create')->name('products.store');
    Route::get('products/{product}/edit', [ProductsController::class, 'edit'])
        ->middleware('menu.permission:products,update')->name('products.edit');
    Route::put('products/{product}', [ProductsController::class, 'update'])
        ->middleware('menu.permission:products,update')->name('products.update');
    Route::delete('products/{product}', [ProductsController::class, 'destroy'])
        ->middleware('menu.permission:products,delete')->name('products.destroy');

    Route::get('categories', [CategoriesController::class, 'index'])
        ->middleware('menu.permission:categories,view')->name('categories.index');
    Route::get('categories/create', [CategoriesController::class, 'create'])
        ->middleware('menu.permission:categories,create')->name('categories.create');
    Route::post('categories', [CategoriesController::class, 'store'])
        ->middleware('menu.permission:categories,create')->name('categories.store');
    Route::get('categories/{category}/edit', [CategoriesController::class, 'edit'])
        ->middleware('menu.permission:categories,update')->name('categories.edit');
    Route::put('categories/{category}', [CategoriesController::class, 'update'])
        ->middleware('menu.permission:categories,update')->name('categories.update');
    Route::delete('categories/{category}', [CategoriesController::class, 'destroy'])
        ->middleware('menu.permission:categories,delete')->name('categories.destroy');

    Route::get('brands', [BrandsController::class, 'index'])
        ->middleware('menu.permission:brands,view')->name('brands.index');
    Route::get('brands/create', [BrandsController::class, 'create'])
        ->middleware('menu.permission:brands,create')->name('brands.create');
    Route::post('brands', [BrandsController::class, 'store'])
        ->middleware('menu.permission:brands,create')->name('brands.store');
    Route::get('brands/{brand}/edit', [BrandsController::class, 'edit'])
        ->middleware('menu.permission:brands,update')->name('brands.edit');
    Route::put('brands/{brand}', [BrandsController::class, 'update'])
        ->middleware('menu.permission:brands,update')->name('brands.update');
    Route::delete('brands/{brand}', [BrandsController::class, 'destroy'])
        ->middleware('menu.permission:brands,delete')->name('brands.destroy');

    Route::get('units', [UnitsController::class, 'index'])
        ->middleware('menu.permission:units,view')->name('units.index');
    Route::get('units/create', [UnitsController::class, 'create'])
        ->middleware('menu.permission:units,create')->name('units.create');
    Route::post('units', [UnitsController::class, 'store'])
        ->middleware('menu.permission:units,create')->name('units.store');
    Route::get('units/{unit}/edit', [UnitsController::class, 'edit'])
        ->middleware('menu.permission:units,update')->name('units.edit');
    Route::put('units/{unit}', [UnitsController::class, 'update'])
        ->middleware('menu.permission:units,update')->name('units.update');
    Route::delete('units/{unit}', [UnitsController::class, 'destroy'])
        ->middleware('menu.permission:units,delete')->name('units.destroy');

    Route::get('employees', [EmployeesController::class, 'index'])
        ->middleware('menu.permission:employees,view')->name('employees.index');
    Route::get('employees/create', [EmployeesController::class, 'create'])
        ->middleware('menu.permission:employees,create')->name('employees.create');
    Route::post('employees', [EmployeesController::class, 'store'])
        ->middleware('menu.permission:employees,create')->name('employees.store');
    Route::get('employees/{employee}', [EmployeesController::class, 'show'])
        ->middleware('menu.permission:employees,view')->name('employees.show');
    Route::get('employees/{employee}/edit', [EmployeesController::class, 'edit'])
        ->middleware('menu.permission:employees,update')->name('employees.edit');
    Route::put('employees/{employee}', [EmployeesController::class, 'update'])
        ->middleware('menu.permission:employees,update')->name('employees.update');
    Route::delete('employees/{employee}', [EmployeesController::class, 'destroy'])
        ->middleware('menu.permission:employees,delete')->name('employees.destroy');

    Route::get('divisions', [DivisionsController::class, 'index'])
        ->middleware('menu.permission:divisions,view')->name('divisions.index');
    Route::get('divisions/create', [DivisionsController::class, 'create'])
        ->middleware('menu.permission:divisions,create')->name('divisions.create');
    Route::post('divisions', [DivisionsController::class, 'store'])
        ->middleware('menu.permission:divisions,create')->name('divisions.store');
    Route::get('divisions/{division}/edit', [DivisionsController::class, 'edit'])
        ->middleware('menu.permission:divisions,update')->name('divisions.edit');
    Route::put('divisions/{division}', [DivisionsController::class, 'update'])
        ->middleware('menu.permission:divisions,update')->name('divisions.update');
    Route::delete('divisions/{division}', [DivisionsController::class, 'destroy'])
        ->middleware('menu.permission:divisions,delete')->name('divisions.destroy');

    Route::get('departments', [DepartmentsController::class, 'index'])
        ->middleware('menu.permission:departments,view')->name('departments.index');
    Route::get('departments/create', [DepartmentsController::class, 'create'])
        ->middleware('menu.permission:departments,create')->name('departments.create');
    Route::post('departments', [DepartmentsController::class, 'store'])
        ->middleware('menu.permission:departments,create')->name('departments.store');
    Route::get('departments/{department}/edit', [DepartmentsController::class, 'edit'])
        ->middleware('menu.permission:departments,update')->name('departments.edit');
    Route::put('departments/{department}', [DepartmentsController::class, 'update'])
        ->middleware('menu.permission:departments,update')->name('departments.update');
    Route::delete('departments/{department}', [DepartmentsController::class, 'destroy'])
        ->middleware('menu.permission:departments,delete')->name('departments.destroy');

    Route::get('org-units', [OrgUnitsController::class, 'index'])
        ->middleware('menu.permission:org-units,view')->name('org-units.index');
    Route::get('org-units/create', [OrgUnitsController::class, 'create'])
        ->middleware('menu.permission:org-units,create')->name('org-units.create');
    Route::post('org-units', [OrgUnitsController::class, 'store'])
        ->middleware('menu.permission:org-units,create')->name('org-units.store');
    Route::get('org-units/{orgUnit}/edit', [OrgUnitsController::class, 'edit'])
        ->middleware('menu.permission:org-units,update')->name('org-units.edit');
    Route::put('org-units/{orgUnit}', [OrgUnitsController::class, 'update'])
        ->middleware('menu.permission:org-units,update')->name('org-units.update');
    Route::delete('org-units/{orgUnit}', [OrgUnitsController::class, 'destroy'])
        ->middleware('menu.permission:org-units,delete')->name('org-units.destroy');

    Route::get('positions', [PositionsController::class, 'index'])
        ->middleware('menu.permission:positions,view')->name('positions.index');
    Route::get('positions/create', [PositionsController::class, 'create'])
        ->middleware('menu.permission:positions,create')->name('positions.create');
    Route::post('positions', [PositionsController::class, 'store'])
        ->middleware('menu.permission:positions,create')->name('positions.store');
    Route::get('positions/{position}/edit', [PositionsController::class, 'edit'])
        ->middleware('menu.permission:positions,update')->name('positions.edit');
    Route::put('positions/{position}', [PositionsController::class, 'update'])
        ->middleware('menu.permission:positions,update')->name('positions.update');
    Route::delete('positions/{position}', [PositionsController::class, 'destroy'])
        ->middleware('menu.permission:positions,delete')->name('positions.destroy');

    Route::get('grades', [GradesController::class, 'index'])
        ->middleware('menu.permission:grades,view')->name('grades.index');
    Route::get('grades/create', [GradesController::class, 'create'])
        ->middleware('menu.permission:grades,create')->name('grades.create');
    Route::post('grades', [GradesController::class, 'store'])
        ->middleware('menu.permission:grades,create')->name('grades.store');
    Route::get('grades/{grade}/edit', [GradesController::class, 'edit'])
        ->middleware('menu.permission:grades,update')->name('grades.edit');
    Route::put('grades/{grade}', [GradesController::class, 'update'])
        ->middleware('menu.permission:grades,update')->name('grades.update');
    Route::delete('grades/{grade}', [GradesController::class, 'destroy'])
        ->middleware('menu.permission:grades,delete')->name('grades.destroy');

    Route::get('employment-statuses', [EmploymentStatusesController::class, 'index'])
        ->middleware('menu.permission:employment-statuses,view')->name('employment-statuses.index');
    Route::get('employment-statuses/create', [EmploymentStatusesController::class, 'create'])
        ->middleware('menu.permission:employment-statuses,create')->name('employment-statuses.create');
    Route::post('employment-statuses', [EmploymentStatusesController::class, 'store'])
        ->middleware('menu.permission:employment-statuses,create')->name('employment-statuses.store');
    Route::get('employment-statuses/{employmentStatus}/edit', [EmploymentStatusesController::class, 'edit'])
        ->middleware('menu.permission:employment-statuses,update')->name('employment-statuses.edit');
    Route::put('employment-statuses/{employmentStatus}', [EmploymentStatusesController::class, 'update'])
        ->middleware('menu.permission:employment-statuses,update')->name('employment-statuses.update');
    Route::delete('employment-statuses/{employmentStatus}', [EmploymentStatusesController::class, 'destroy'])
        ->middleware('menu.permission:employment-statuses,delete')->name('employment-statuses.destroy');

    Route::get('work-locations', [WorkLocationsController::class, 'index'])
        ->middleware('menu.permission:work-locations,view')->name('work-locations.index');
    Route::get('work-locations/create', [WorkLocationsController::class, 'create'])
        ->middleware('menu.permission:work-locations,create')->name('work-locations.create');
    Route::post('work-locations', [WorkLocationsController::class, 'store'])
        ->middleware('menu.permission:work-locations,create')->name('work-locations.store');
    Route::get('work-locations/{workLocation}/edit', [WorkLocationsController::class, 'edit'])
        ->middleware('menu.permission:work-locations,update')->name('work-locations.edit');
    Route::put('work-locations/{workLocation}', [WorkLocationsController::class, 'update'])
        ->middleware('menu.permission:work-locations,update')->name('work-locations.update');
    Route::delete('work-locations/{workLocation}', [WorkLocationsController::class, 'destroy'])
        ->middleware('menu.permission:work-locations,delete')->name('work-locations.destroy');

    Route::get('religions', [ReligionsController::class, 'index'])
        ->middleware('menu.permission:religions,view')->name('religions.index');
    Route::get('religions/create', [ReligionsController::class, 'create'])
        ->middleware('menu.permission:religions,create')->name('religions.create');
    Route::post('religions', [ReligionsController::class, 'store'])
        ->middleware('menu.permission:religions,create')->name('religions.store');
    Route::get('religions/{religion}/edit', [ReligionsController::class, 'edit'])
        ->middleware('menu.permission:religions,update')->name('religions.edit');
    Route::put('religions/{religion}', [ReligionsController::class, 'update'])
        ->middleware('menu.permission:religions,update')->name('religions.update');
    Route::delete('religions/{religion}', [ReligionsController::class, 'destroy'])
        ->middleware('menu.permission:religions,delete')->name('religions.destroy');

    Route::get('education-levels', [EducationLevelsController::class, 'index'])
        ->middleware('menu.permission:education-levels,view')->name('education-levels.index');
    Route::get('education-levels/create', [EducationLevelsController::class, 'create'])
        ->middleware('menu.permission:education-levels,create')->name('education-levels.create');
    Route::post('education-levels', [EducationLevelsController::class, 'store'])
        ->middleware('menu.permission:education-levels,create')->name('education-levels.store');
    Route::get('education-levels/{educationLevel}/edit', [EducationLevelsController::class, 'edit'])
        ->middleware('menu.permission:education-levels,update')->name('education-levels.edit');
    Route::put('education-levels/{educationLevel}', [EducationLevelsController::class, 'update'])
        ->middleware('menu.permission:education-levels,update')->name('education-levels.update');
    Route::delete('education-levels/{educationLevel}', [EducationLevelsController::class, 'destroy'])
        ->middleware('menu.permission:education-levels,delete')->name('education-levels.destroy');

    Route::get('marital-statuses', [MaritalStatusesController::class, 'index'])
        ->middleware('menu.permission:marital-statuses,view')->name('marital-statuses.index');
    Route::get('marital-statuses/create', [MaritalStatusesController::class, 'create'])
        ->middleware('menu.permission:marital-statuses,create')->name('marital-statuses.create');
    Route::post('marital-statuses', [MaritalStatusesController::class, 'store'])
        ->middleware('menu.permission:marital-statuses,create')->name('marital-statuses.store');
    Route::get('marital-statuses/{maritalStatus}/edit', [MaritalStatusesController::class, 'edit'])
        ->middleware('menu.permission:marital-statuses,update')->name('marital-statuses.edit');
    Route::put('marital-statuses/{maritalStatus}', [MaritalStatusesController::class, 'update'])
        ->middleware('menu.permission:marital-statuses,update')->name('marital-statuses.update');
    Route::delete('marital-statuses/{maritalStatus}', [MaritalStatusesController::class, 'destroy'])
        ->middleware('menu.permission:marital-statuses,delete')->name('marital-statuses.destroy');
});

require __DIR__.'/auth.php';
