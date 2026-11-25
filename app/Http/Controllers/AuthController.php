<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/** Sign in and sign out for every role. */
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'identity' => ['required', 'string', 'max:190'],
            'password' => ['required'],
        ], [
            'identity.required' => 'Please enter your username, email address, or mobile number.',
            'password.required' => 'Please enter your password.',
        ]);

        $identity = trim($credentials['identity']);
        $user = User::where('username', $identity)
            ->orWhere('email', $identity)
            ->orWhere('mobile', $identity)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withInput($request->only('identity', 'remember'))
                ->withErrors([
                    'identity' => 'The account or password is incorrect.',
                ]);
        }

        if (! in_array($user->role, ['citizen', 'sb_member', 'admin'], true)) {
            return back()
                ->withInput($request->only('identity', 'remember'))
                ->withErrors([
                    'identity' => 'The account or password is incorrect.',
                ]);
        }

        if ($user->status !== 'active' || ! $user->is_active) {
            return back()
                ->withInput($request->only('identity', 'remember'))
                ->withErrors([
                    'identity' => 'Your account is not active. Please contact the Municipal Hall.',
                ]);
        }

        $request->session()->forget(['staff_verification_user_id', 'password_reset']);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        AuditLogger::activity('Signed in', 'Password', $user);

        if ($user->must_change_password) {
            return redirect()->route('password.force');
        }

        return $this->redirectToOwnArea($request, $user);
    }

    /** Citizen · 02 Required password change screen. */
    public function showForcePasswordChange(Request $request)
    {
        $user = $request->user();
        if (! $user->must_change_password) {
            return redirect()->route($user->homeRoute());
        }

        return view('auth.force-password', ['user' => $user]);
    }

    /** Citizen · 02 Save new permanent password. */
    public function updateForcePasswordChange(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.min' => 'Use at least 8 characters.',
            'password.confirmed' => 'The two passwords do not match.',
        ]);

        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors([
                'password' => 'Choose a password different from your temporary one.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        AuditLogger::security('Password changed', 'Required temporary password replacement', $user);

        return redirect()
            ->route($user->homeRoute())
            ->with('status', 'Your new password has been saved.');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLogger::activity('Signed out', 'Account security');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectToOwnArea(Request $request, User $user)
    {
        $intended = $request->session()->pull('url.intended');

        if ($intended) {
            $path = (string) parse_url($intended, PHP_URL_PATH);
            $inAdmin = str_starts_with($path, '/admin');
            $inStaff = str_starts_with($path, '/staff');

            $ownArea = match (true) {
                $user->isAdmin() => $inAdmin,
                $user->isSbMember() => $inStaff,
                default => ! $inAdmin && ! $inStaff,
            };

            if ($ownArea) {
                return redirect()->to($intended);
            }
        }

        return redirect()->route($user->homeRoute());
    }
}
