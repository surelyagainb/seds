<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Restricts citizen pages to citizen accounts. */
class EnsureCitizen
{
    /** Send users with another valid role to their own area. */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->role !== 'citizen') {
            return redirect()->route($user->homeRoute());
        }

        return $next($request);
    }
}
