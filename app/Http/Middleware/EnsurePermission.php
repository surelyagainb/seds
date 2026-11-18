<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePermission
{
    // Usage: ->middleware('permission:view_audit')  or  ->middleware('permission:admin')
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        $ok = $user && ($permission === 'admin' ? $user->isAdmin() : $user->allows($permission));

        abort_unless($ok, 403, 'You do not have access to this page.');

        return $next($request);
    }
}
