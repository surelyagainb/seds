<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Keeps administrators out of the user interface; they have their own area. */
class EnsureCitizen
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.overview');
        }

        return $next($request);
    }
}
