<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Protects a route by permission, for example permission:admin. */
class EnsurePermission
{
    // Usage: ->middleware('permission:view_audit')  or  ->middleware('permission:admin')
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        $ok = $user && ($permission === 'admin' ? $user->isAdmin() : $user->allows($permission));

        // Signed-in users who are not administrators belong in the citizen interface.
        if (! $ok && $user && $permission === 'admin') {
            return redirect()->route($user->homeRoute());
        }

        abort_unless($ok, 403, 'You do not have access to this page.');

        return $next($request);
    }
}
