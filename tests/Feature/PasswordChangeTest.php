<?php

use App\Models\User;

test('the user menu links to the change password page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('password.change'))
        ->assertSee(__('Change password'));
});

test('guests are redirected to the login page', function () {
    $this->get(route('password.change'))->assertRedirect(route('login'));
    $this->put(route('password.update'))->assertRedirect(route('login'));
});

test('users can change their own password', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->put(route('password.update'), [
        'current_password' => 'password',
        'password' => 'new-secret-pass',
        'password_confirmation' => 'new-secret-pass',
    ])->assertRedirect(route('login'));

    $this->assertGuest();
    $this->assertCredentials(['email' => $user->email, 'password' => 'new-secret-pass']);
    $this->assertInvalidCredentials(['email' => $user->email, 'password' => 'password']);
});

test('the current password must match', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $oldHash = $user->password;

    $this->put(route('password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'new-secret-pass',
        'password_confirmation' => 'new-secret-pass',
    ])->assertSessionHasErrors('current_password');

    $this->assertAuthenticated();
    expect($user->fresh()->password)->toBe($oldHash);
});

test('the new password must be confirmed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $oldHash = $user->password;

    $this->put(route('password.update'), [
        'current_password' => 'password',
        'password' => 'new-secret-pass',
        'password_confirmation' => 'different-pass',
    ])->assertSessionHasErrors('password');

    $this->assertAuthenticated();
    expect($user->fresh()->password)->toBe($oldHash);
});

test('the new password must be at least eight characters', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $oldHash = $user->password;

    $this->put(route('password.update'), [
        'current_password' => 'password',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');

    $this->assertAuthenticated();
    expect($user->fresh()->password)->toBe($oldHash);
});

test('the current and new passwords are required', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $oldHash = $user->password;

    $this->put(route('password.update'), [])
        ->assertSessionHasErrors(['current_password', 'password']);

    $this->assertAuthenticated();
    expect($user->fresh()->password)->toBe($oldHash);
});
