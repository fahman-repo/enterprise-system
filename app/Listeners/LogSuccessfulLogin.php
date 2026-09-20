<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    /**
     * Record a successful authentication attempt in the audit trail.
     */
    public function handle(Login $event): void
    {
        activity('auth')
            ->event('login')
            ->causedBy($event->user)
            ->withProperties([
                'email' => $event->user->email,
                'guard' => $event->guard,
                'remember' => $event->remember,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Logged in');
    }
}
