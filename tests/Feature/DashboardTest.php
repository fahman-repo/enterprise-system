<?php

use App\Models\User;

test('guests are redirected from the dashboard to the login screen', function () {
    $this->get('/dashboard')
        ->assertRedirect(route('login', absolute: false));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Dashboard');
});

test('the root path redirects guests to the login screen', function () {
    $this->get('/')
        ->assertRedirect(route('login', absolute: false));
});

test('the root path redirects authenticated users to the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('dashboard', absolute: false));
});
