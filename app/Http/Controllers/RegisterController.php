<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/** Lets new people sign up. Every new account is a citizen. */
class RegisterController extends Controller
{
    /** Show the register page. */
    public function show()
    {
        return view('auth.register');
    }

    /** Validate the registration details and email a code before activating the account. */
    public function store(Request $request, EmailOtpService $emailOtpService)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:60', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'mobile' => ['nullable', 'string', 'max:32', 'unique:users,mobile'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $user = new User([
            'name' => $data['username'],
            'username' => $data['username'],
            'email' => $data['email'],
            'mobile' => $data['mobile'] ?? null,
            'password' => $data['password'],
        ]);
        $user->forceFill([
            'role' => 'citizen',
            'is_active' => false,
            'status' => 'pending',
            'password_changed_at' => now(),
        ])->save();

        $request->session()->regenerate();
        $request->session()->put('verification_user_id', $user->id);
        $request->session()->forget('pending_login');

        try {
            $emailOtpService->send($user->email, 'registration');
        } catch (ValidationException $exception) {
            $user->delete();
            $request->session()->forget('verification_user_id');

            return back()->withInput($request->except(['password', 'password_confirmation']))->withErrors([
                'email' => $exception->errors()['otp'][0] ?? 'The verification email could not be sent.',
            ]);
        }

        return redirect()->route('register.verify');
    }

    /** Show the code-entry page for a pending registration. */
    public function verifyPage(Request $request)
    {
        $user = User::find($request->session()->get('verification_user_id'));

        if (! $user || $user->status !== 'pending') {
            $request->session()->forget('verification_user_id');

            return redirect()->route('register')->withErrors([
                'email' => 'Please enter your registration details first.',
            ]);
        }

        return view('auth.verify', [
            'user' => $user,
            'purpose' => 'registration',
            'email' => $user->email,
        ]);
    }

    /** Check the email code, activate the citizen account, and sign the citizen in. */
    public function verify(Request $request, EmailOtpService $emailOtpService)
    {
        $data = $request->validate(['otp' => ['required', 'digits:6']]);
        $user = User::find($request->session()->get('verification_user_id'));

        if (! $user || $user->status !== 'pending') {
            $request->session()->forget('verification_user_id');

            return redirect()->route('register')->withErrors([
                'email' => 'Your registration session expired. Please register or sign in again.',
            ]);
        }

        if (! $emailOtpService->verify($user->email, 'registration', $data['otp'])) {
            return back()->withErrors([
                'otp' => 'The code is incorrect or expired. You can request a new code below.',
            ]);
        }

        $user->forceFill([
            'is_active' => true,
            'status' => 'active',
            'verified_at' => now(),
            'verification_code' => null,
            'verification_sent_at' => null,
        ])->save();

        $request->session()->forget(['verification_user_id', 'pending_login']);
        Auth::login($user);
        $request->session()->regenerate();

        AuditLogger::activity('Account registered', 'Citizen email verified', $user);

        return redirect()->route($user->homeRoute())->with('status', 'Your email has been verified. Welcome!');
    }

    /** Send another registration code if the resend cooldown has passed. */
    public function resendVerification(Request $request, EmailOtpService $emailOtpService)
    {
        $user = User::find($request->session()->get('verification_user_id'));

        if (! $user || $user->status !== 'pending') {
            $request->session()->forget('verification_user_id');

            return redirect()->route('register')->withErrors([
                'email' => 'Please enter your registration details first.',
            ]);
        }

        try {
            $emailOtpService->send($user->email, 'registration');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'A new verification code has been sent to your email.');
    }
}
