<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    // Where the routes live: web pages, console commands, and a health check at /up
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Short names for our middleware, used in routes/web.php (for example 'citizen' or 'permission:admin')
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'active'     => \App\Http\Middleware\EnsureAccountActive::class,
            'citizen'    => \App\Http\Middleware\EnsureCitizen::class,
            'sb'         => \App\Http\Middleware\EnsureSbMember::class,
        ]);
    })
    // Custom error handling would go here (none needed yet)
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
