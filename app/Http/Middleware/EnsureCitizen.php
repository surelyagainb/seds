<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Keeps administrators out of the citizen interface; they have their own area. */
class EnsureCitizen
{
    /** If an admin opens a citizen page, send them to the admin area instead. */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.overview');
        }

        return $next($request);
    }
}
