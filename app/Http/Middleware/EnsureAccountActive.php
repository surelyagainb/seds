<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Runs on every protected page. If the account was deactivated, sign it out. */
class EnsureAccountActive
{
    /** Let the request through only if the account is still active. */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ($user->status !== 'active' || ! $user->is_active)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'This account is not active. Please contact the Municipal Hall.']);
        }

        return $next($request);
    }
}
