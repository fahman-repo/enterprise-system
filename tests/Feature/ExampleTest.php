<?php

it('redirects guests from the application root to the login screen', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login', absolute: false));
});
