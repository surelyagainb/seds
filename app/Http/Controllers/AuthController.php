<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** Sign in and sign out for every role. */
class AuthController extends Controller
{
    /** Show the login page. */
    public function showLogin()
    {
        return view('auth.login');
    }

    /** Show the municipal-hall login page for SB members and administrators. */
    public function showHallLogin()
    {
        return view('auth.login', ['hallLogin' => true]);
    }

    /** Check the password and send an email code before signing the person in. */
    public function login(Request $request, EmailOtpService $emailOtpService)
    {
        $credentials = $request->validate([
            'identity' => ['required', 'string', 'max:190'],
            'password' => ['required'],
        ]);
        $identity = trim($credentials['identity']);
        $user = User::where('username', $identity)
            ->orWhere('email', $identity)
            ->orWhere('mobile', $identity)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withInput($request->only('identity'))->withErrors([
                'identity' => 'The account or password is incorrect.',
            ]);
        }

        if ($request->boolean('hall') && $user->role === 'citizen') {
            return back()->withInput($request->only('identity'))->withErrors([
                'identity' => 'Municipal Hall login is only for SB members and administrators.',
            ]);
        }

        if ($user->status === 'deactivated' || (! $user->is_active && $user->status !== 'pending')) {
            return back()->withInput($request->only('identity'))->withErrors([
                'identity' => 'Your account is deactivated. Contact the Municipal Hall.',
            ]);
        }

        if (! $user->email) {
            return back()->withInput($request->only('identity'))->withErrors([
                'identity' => 'This account needs an email address to receive a verification code. Please contact the Municipal Hall.',
            ]);
        }

        $request->session()->forget(['pending_login', 'verification_user_id']);
        $request->session()->regenerate();

        // Accounts that were not verified yet receive the registration code again.
        if ($user->status === 'pending' || ! $user->verified_at) {
            $user->forceFill([
                'is_active' => false,
                'status' => 'pending',
            ])->save();
            $request->session()->put('verification_user_id', $user->id);

            try {
                $emailOtpService->send($user->email, 'registration');
            } catch (ValidationException $exception) {
                $message = $exception->errors()['otp'][0] ?? 'The verification email could not be sent.';

                if (str_contains($message, 'Please wait 60 seconds')) {
                    return redirect()->route('register.verify')->with('status', 'A verification code was already sent. Please check your email.');
                }

                $request->session()->forget('verification_user_id');

                return back()->withInput($request->only('identity'))->withErrors([
                    'identity' => $message,
                ]);
            }

            return redirect()->route('register.verify');
        }

        if (! $user->is_active) {
            return back()->withInput($request->only('identity'))->withErrors([
                'identity' => 'Your account is deactivated. Contact the Municipal Hall.',
            ]);
        }

        $request->session()->put('pending_login', [
            'user_id' => $user->id,
            'remember' => $request->boolean('remember'),
        ]);

        try {
            $emailOtpService->send($user->email, 'login');
        } catch (ValidationException $exception) {
            $message = $exception->errors()['otp'][0] ?? 'The verification email could not be sent.';

            if (str_contains($message, 'Please wait 60 seconds')) {
                return redirect()->route('login.verify')->with('status', 'A verification code was already sent. Please check your email.');
            }

            $request->session()->forget('pending_login');

            return back()->withInput($request->only('identity'))->withErrors([
                'identity' => $message,
            ]);
        }

        return redirect()->route('login.verify');
    }

    /** Show the code-entry page for a pending login. */
    public function showLoginOtp(Request $request)
    {
        $pending = $request->session()->get('pending_login');
        $user = $pending ? User::find($pending['user_id']) : null;

        if (! $user || ! $user->is_active || $user->status === 'deactivated') {
            $request->session()->forget('pending_login');

            return redirect()->route('login')->withErrors([
                'identity' => 'Please sign in again to request a verification code.',
            ]);
        }

        return view('auth.verify', [
            'user' => $user,
            'purpose' => 'login',
            'email' => $user->email,
        ]);
    }

    /** Verify the login code and then create the authenticated session. */
    public function verifyLoginOtp(Request $request, EmailOtpService $emailOtpService)
    {
        $data = $request->validate(['otp' => ['required', 'digits:6']]);
        $pending = $request->session()->get('pending_login');
        $user = $pending ? User::find($pending['user_id']) : null;

        if (! $user || ! $user->is_active || $user->status === 'deactivated') {
            $request->session()->forget('pending_login');

            return redirect()->route('login')->withErrors([
                'identity' => 'Your sign-in session expired. Please sign in again.',
            ]);
        }

        if (! $emailOtpService->verify($user->email, 'login', $data['otp'])) {
            return back()->withErrors([
                'otp' => 'The code is incorrect or expired. You can request a new code below.',
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        Auth::login($user, (bool) ($pending['remember'] ?? false));
        $request->session()->forget(['pending_login', 'verification_user_id']);
        $request->session()->regenerate();

        AuditLogger::activity('Signed in', 'Password and email code', $user);

        return $this->redirectToOwnArea($request, $user);
    }

    /** Send another login code if the resend cooldown has passed. */
    public function resendLoginOtp(Request $request, EmailOtpService $emailOtpService)
    {
        $pending = $request->session()->get('pending_login');
        $user = $pending ? User::find($pending['user_id']) : null;

        if (! $user || ! $user->is_active || $user->status === 'deactivated') {
            $request->session()->forget('pending_login');

            return redirect()->route('login')->withErrors([
                'identity' => 'Please sign in again to request a verification code.',
            ]);
        }

        try {
            $emailOtpService->send($user->email, 'login');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    /** Sign the person out and go back to the login page. */
    public function logout(Request $request)
    {
        AuditLogger::activity('Signed out', 'Account security');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Send each account to its own section and only honor intended links inside that section. */
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
