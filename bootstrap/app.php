<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // 👇 ลงทะเบียน Alias 'check.ie' ตรงนี้ครับ
        $middleware->alias([
            'check.ie' => \App\Http\Middleware\CheckBrowserIE::class,

            'keycloak-web' => \Vizir\KeycloakWebGuard\Middleware\KeycloakAuthenticated::class,
            'keycloak-can' => \Vizir\KeycloakWebGuard\Middleware\KeycloakCan::class,

            'check-keycloak' => \App\Http\Middleware\CheckKeycloakSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
