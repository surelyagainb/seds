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

        return redirect()->intended(route('admin.overview'));
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
