<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Keeps administrators and SB members out of the citizen pages; they have their own areas. */
class EnsureCitizen
{
    /** If an admin or SB member opens a citizen page, send them to their own area instead. */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Admins and SB members have their own areas
        if ($user && ($user->isAdmin() || $user->isSbMember())) {
            return redirect()->route($user->homeRoute());
        }

        return $next($request);
    }
}
