<?php

use App\Http\Controllers\MenusController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
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
});

require __DIR__.'/auth.php';
