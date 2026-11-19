<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    /** Self-registration always creates a citizen. Nobody can pick their own role here. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $user->forceFill(['role' => 'citizen', 'is_active' => true, 'password_changed_at' => now()])->save();

        Auth::login($user);
        $request->session()->regenerate();

        AuditLogger::activity('Account registered', 'Citizen', $user);

        return redirect()->route($user->homeRoute());
    }
}
