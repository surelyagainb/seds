<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $attempt = ['email' => $credentials['email'], 'password' => $credentials['password'], 'is_active' => true];

        if (! Auth::attempt($attempt, $request->boolean('remember'))) {
            $inactive = User::where('email', $credentials['email'])->where('is_active', false)->exists();

            return back()->withInput($request->only('email'))->withErrors([
                'email' => $inactive ? 'This account has been deactivated.' : 'The email or password is incorrect.',
            ]);
        }

        $request->session()->regenerate();
        AuditLogger::activity('Signed in', 'Account security');

        $user     = $request->user();
        $intended = $request->session()->pull('url.intended');

        // Only follow the saved URL if it belongs to this person's own area (admin vs user interface).
        if ($intended) {
            $inAdmin = str_starts_with((string) parse_url($intended, PHP_URL_PATH), '/admin');

            if ($inAdmin === $user->isAdmin()) return redirect()->to($intended);
        }

        return redirect()->route($user->homeRoute());
    }

    public function logout(Request $request)
    {
        AuditLogger::activity('Signed out', 'Account security');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
