<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Protects the /staff pages: only SB members may open them. */
class EnsureSbMember
{
    /** Let SB members through; send everyone else to their own area. */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->isSbMember()) {
            return $user ? redirect()->route($user->homeRoute()) : redirect()->route('login');
        }

        return $next($request);
    }
}
