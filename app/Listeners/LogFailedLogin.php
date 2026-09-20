<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    /**
     * Record a failed authentication attempt. There is no authenticated
     * user, so the attempted identifier and request context are stored
     * on the entry properties instead.
     */
    public function handle(Failed $event): void
    {
        activity('auth')
            ->event('failed_login')
            ->causedByAnonymous()
            ->withProperties([
                'email' => $event->credentials['email'] ?? null,
                'guard' => $event->guard,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Failed login');
    }
}
