<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/** Prototype password reset flow. In production, replace the session/demo link with mailed secure tokens. */
class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('auth.password-reset', ['step' => 'request']);
    }

    public function sendLink(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);
        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return back()->withErrors(['email' => 'No account was found for that email address.']);
        }

        $token = bin2hex(random_bytes(16));
        $request->session()->put('password_reset', ['user_id' => $user->id, 'token' => $token]);

        return view('auth.password-reset', [
            'step' => 'sent',
            'demoLink' => route('password.reset', ['token' => $token]),
        ]);
    }

    public function resetForm(Request $request, string $token)
    {
        $reset = $request->session()->get('password_reset');
        if (! $reset || ! hash_equals($reset['token'], $token)) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'This demo reset link is invalid or has expired.',
            ]);
        }

        return view('auth.password-reset', ['step' => 'reset', 'token' => $token]);
    }

    public function update(Request $request, string $token)
    {
        $reset = $request->session()->get('password_reset');
        if (! $reset || ! hash_equals($reset['token'], $token)) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'This demo reset link is invalid or has expired.',
            ]);
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);
        $user = User::findOrFail($reset['user_id']);
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'password_changed_at' => now(),
        ])->save();

        $request->session()->forget('password_reset');
        AuditLogger::security('Password changed', 'Password reset flow', $user);

        return view('auth.password-reset', ['step' => 'changed']);
    }
}
