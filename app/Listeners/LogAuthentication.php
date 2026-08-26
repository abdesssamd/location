<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use App\Services\StoreContext;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthentication
{
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        AuditLogger::log('auth.login', $user, null, [
            'email' => $user->email,
            'guard' => $event->guard,
        ], $user, $user->store_id ?? StoreContext::id());
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if (! $user) {
            return;
        }

        AuditLogger::log('auth.logout', $user, null, [
            'email' => $user->email,
            'guard' => $event->guard,
        ], $user, $user->store_id ?? StoreContext::id());
    }
}
