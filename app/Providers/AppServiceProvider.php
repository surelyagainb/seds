<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/** Place for app-wide setup. Nothing is needed here yet. */
class AppServiceProvider extends ServiceProvider
{
    /** Runs first: register services (none yet). */
    public function register(): void {}

    /** Runs after everything is registered: app-wide setup (none yet). */
    public function boot(): void {}
}
