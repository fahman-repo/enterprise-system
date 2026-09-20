<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class LogLogout
{
    /**
     * Record a user logout in the audit trail.
     */
    public function handle(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        activity('auth')
            ->event('logout')
            ->causedBy($event->user)
            ->withProperties([
                'email' => $event->user->email,
                'guard' => $event->guard,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Logged out');
    }
}
