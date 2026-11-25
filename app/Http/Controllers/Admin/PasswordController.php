<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/** Lets the signed-in admin change their own password. */
class PasswordController extends Controller
{
    /** Show the change-password form. */
    public function edit(Request $request)
    {
        return view('admin.password', ['user' => $request->user()]);
    }

    /** Check the old password, save the new one, and sign out the other devices. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', 'different:current_password', Password::min(8)->numbers()->symbols()],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.different'                => 'Choose a password you have not used just now.',
            'password.confirmed'                => 'The two new passwords do not match.',
            'password.min'                      => 'Use at least 8 characters.',
        ]);

        $user = $request->user();
        $user->forceFill(['password' => $data['password'], 'password_changed_at' => now()])->save();

        // Sign out every other session (needs the auth.session middleware on the route group).
        Auth::logoutOtherDevices($data['password']);

        AuditLogger::security('Password changed', 'Account security');

        return redirect()->route('admin.password.edit')->with('status', 'Password updated. Other sessions were signed out.');
    }
}
