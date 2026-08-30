<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SetStoreContext;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'store.context' => SetStoreContext::class,
            'subscription' => \App\Http\Middleware\CheckSubscription::class,
            'auth.store.token' => \App\Http\Middleware\ApiTokenAuth::class,
        ]);

        $middleware->web(prepend: [
            SetStoreContext::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\CheckSubscription::class,
        ]);

        // Le contexte tenant doit être posé AVANT SubstituteBindings : sinon le
        // model binding résout les modèles sans le scope magasin (fuite inter-magasins).
        $middleware->api(prepend: [
            \App\Http\Middleware\ApiTokenAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();