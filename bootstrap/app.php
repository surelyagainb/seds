<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Make sure the folders Laravel writes to exist. They are empty folders, so they are often lost when the
// project is zipped, copied or cloned onto another computer. A missing folder causes errors such as
// "Please provide a valid cache path." This creates any missing folder on the first request or command.
foreach (['storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions',
          'storage/framework/views', 'storage/framework/testing', 'storage/logs', 'bootstrap/cache'] as $folder) {
    if (! is_dir(dirname(__DIR__).'/'.$folder)) {
        @mkdir(dirname(__DIR__).'/'.$folder, 0775, true);
    }
}

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
