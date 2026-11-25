<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/** Multi-step password recovery flow using 6-digit email OTP verification. */
class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('auth.password-reset', [
            'step' => 'request',
            'email' => null,
        ]);
    }

    public function sendCode(Request $request, EmailOtpService $emailOtpService)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ], [
            'email.required' => 'Please enter the email address linked to your account.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        $email = strtolower(trim($data['email']));
        $user = User::where('email', $email)->first();

        $request->session()->put('password_reset', [
            'user_id' => $user?->id,
            'email' => $email,
            'verified' => false,
        ]);

        if ($user && $user->status !== 'deactivated' && ($user->is_active || $user->status === 'pending')) {
            try {
                $emailOtpService->send($user->email, 'password_reset');
            } catch (ValidationException $exception) {
                $message = $exception->errors()['otp'][0] ?? '';
                if (! str_contains($message, 'Please wait 60 seconds')) {
                    return back()->withInput()->withErrors(['email' => $message]);
                }
            }
        }

        return redirect()
            ->route('password.verify')
            ->with('status', 'If an account is linked to ' . $email . ', we sent a 6-digit verification code.');
    }

    public function verifyForm(Request $request)
    {
        $reset = $request->session()->get('password_reset');

        if (! $reset || empty($reset['email'])) {
            return redirect()->route('password.request');
        }

        return view('auth.password-reset', [
            'step' => 'verify',
            'email' => $reset['email'],
        ]);
    }

    public function verifyCode(Request $request, EmailOtpService $emailOtpService)
    {
        $request->merge([
            'otp' => preg_replace('/\D+/', '', (string) $request->input('otp', '')),
        ]);

        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Please enter the 6-digit verification code.',
            'otp.digits' => 'The verification code must be 6 digits.',
        ]);

        $reset = $request->session()->get('password_reset');
        $user = ($reset && ! empty($reset['user_id'])) ? User::find($reset['user_id']) : null;

        if (! $reset || empty($reset['email'])) {
            return redirect()->route('password.request');
        }

        if (! $user || ! $emailOtpService->verify($user->email, 'password_reset', $data['otp'])) {
            return back()->withInput()->withErrors([
                'otp' => 'The reset code is incorrect or expired. Please try again or request a new code.',
            ]);
        }

        $request->session()->put('password_reset', [
            'user_id' => $user->id,
            'email' => $user->email,
            'verified' => true,
        ]);

        return redirect()->route('password.reset');
    }

    public function resendCode(Request $request, EmailOtpService $emailOtpService)
    {
        $reset = $request->session()->get('password_reset');
        $user = ($reset && ! empty($reset['user_id'])) ? User::find($reset['user_id']) : null;

        if (! $reset || empty($reset['email'])) {
            return redirect()->route('password.request');
        }

        if ($user) {
            try {
                $emailOtpService->send($user->email, 'password_reset');
            } catch (ValidationException $exception) {
                return back()->withErrors($exception->errors());
            }
        }

        return back()->with('status', 'If an account is linked to this email, a new code has been sent.');
    }

    public function resetForm(Request $request)
    {
        $reset = $request->session()->get('password_reset');
        $user = ($reset && ! empty($reset['user_id'])) ? User::find($reset['user_id']) : null;

        if (! $reset || ! $user || empty($reset['verified'])) {
            return redirect()->route('password.verify')->withErrors([
                'otp' => 'Please verify the 6-digit code sent to your email before setting a new password.',
            ]);
        }

        return view('auth.password-reset', [
            'step' => 'reset',
            'email' => $user->email,
        ]);
    }

    public function update(Request $request)
    {
        $reset = $request->session()->get('password_reset');
        $user = ($reset && ! empty($reset['user_id'])) ? User::find($reset['user_id']) : null;

        if (! $reset || ! $user || empty($reset['verified'])) {
            $request->session()->forget('password_reset');

            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.min' => 'Use at least 8 characters.',
            'password.confirmed' => 'The two new passwords do not match.',
        ]);

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
            'verified_at' => $user->verified_at ?: now(),
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();

        $request->session()->forget('password_reset');
        AuditLogger::security('Password changed', 'OTP password reset flow', $user);

        return view('auth.password-reset', [
            'step' => 'changed',
            'email' => $user->email,
        ]);
    }
}
