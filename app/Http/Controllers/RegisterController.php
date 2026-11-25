<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/** Handles citizen self-chosen password registration + email verification, and open municipal staff registration. */
class RegisterController extends Controller
{
    /** Citizen · 01 Create your account */
    public function show()
    {
        return view('auth.register');
    }

    /** Citizen · 01 Validate details and send 6-digit email verification code */
    public function store(Request $request, EmailOtpService $emailOtpService)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name'  => ['required', 'string', 'max:80'],
            'email'      => ['required', 'email', 'max:190', 'unique:users,email'],
            'password'   => ['required', Password::min(8)],
            'terms'      => ['accepted'],
        ], [
            'first_name.required' => 'Please enter your first name.',
            'last_name.required'  => 'Please enter your last name.',
            'email.unique'        => 'An account with this email address already exists.',
            'password.min'        => 'Use at least 8 characters.',
            'terms.accepted'      => 'You must agree to the Terms of use and Privacy policy.',
        ]);

        $email = strtolower(trim($data['email']));

        $request->session()->regenerate();
        $request->session()->put('citizen_registration_pending', [
            'first_name'    => trim($data['first_name']),
            'last_name'     => trim($data['last_name']),
            'email'         => $email,
            'password_hash' => Hash::make($data['password']),
        ]);

        try {
            $emailOtpService->send($email, 'citizen_registration');
        } catch (ValidationException $exception) {
            $request->session()->forget('citizen_registration_pending');

            return back()
                ->withInput($request->except('password'))
                ->withErrors([
                    'email' => $exception->errors()['otp'][0] ?? 'The verification email could not be sent.',
                ]);
        }

        return redirect()->route('register.verify');
    }

    /** Citizen · 02 Verify email */
    public function verifyPage(Request $request)
    {
        $pending = $request->session()->get('citizen_registration_pending');

        if (! is_array($pending) || empty($pending['email'])) {
            return redirect()->route('register')->withErrors([
                'email' => 'Please complete your account details first.',
            ]);
        }

        return view('auth.citizen-verify', [
            'maskedEmail' => $this->maskEmail($pending['email']),
        ]);
    }

    /** Citizen · 02 Verify 6-digit OTP -> create active citizen account and sign in */
    public function verify(Request $request, EmailOtpService $emailOtpService)
    {
        $request->merge([
            'otp' => preg_replace('/\D+/', '', (string) $request->input('otp', '')),
        ]);

        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Please enter the 6-digit verification code.',
            'otp.digits'   => 'The verification code must be 6 digits.',
        ]);

        $pending = $request->session()->get('citizen_registration_pending');

        if (! is_array($pending) || empty($pending['email'])) {
            return redirect()->route('register')->withErrors([
                'email' => 'Your verification session expired. Please start again.',
            ]);
        }

        if (! $emailOtpService->verify($pending['email'], 'citizen_registration', $data['otp'])) {
            return back()->withInput()->withErrors([
                'otp' => 'The code is incorrect or expired. Please try again or resend a new code.',
            ]);
        }

        if (User::where('email', $pending['email'])->exists()) {
            $request->session()->forget('citizen_registration_pending');

            return redirect()->route('login')->withErrors([
                'identity' => 'An account with this email already exists. Please sign in.',
            ]);
        }

        $fullName = $pending['first_name'] . ' ' . $pending['last_name'];
        $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', strstr($pending['email'], '@', true) ?: 'citizen'));
        $username = $baseUsername;
        $suffix = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . '_' . $suffix++;
        }

        $user = new User([
            'name'       => $fullName,
            'first_name' => $pending['first_name'],
            'last_name'  => $pending['last_name'],
            'username'   => $username,
            'email'      => $pending['email'],
        ]);
        $user->forceFill([
            'password'             => $pending['password_hash'],
            'role'                 => 'citizen',
            'must_change_password' => false,
            'is_active'            => true,
            'status'               => 'active',
            'terms_accepted_at'    => now(),
            'verified_at'          => now(),
            'email_verified_at'    => now(),
            'password_changed_at'  => now(),
        ])->save();

        $request->session()->forget('citizen_registration_pending');

        AuditLogger::activity('Citizen account created', 'Email verified during sign-up', $user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->homeRoute());
    }

    /** Citizen · 02 Resend verification code */
    public function resendVerification(Request $request, EmailOtpService $emailOtpService)
    {
        $pending = $request->session()->get('citizen_registration_pending');

        if (! is_array($pending) || empty($pending['email'])) {
            return redirect()->route('register');
        }

        try {
            $emailOtpService->send($pending['email'], 'citizen_registration');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'A new 6-digit verification code has been sent.');
    }

    /** Staff · 01 Register */
    public function showStaff()
    {
        return view('auth.staff-register');
    }

    /** Staff · 01 Submit registration and send an email verification code */
    public function storeStaff(Request $request, EmailOtpService $emailOtpService)
    {
        $data = $request->validate([
            'first_name'  => ['required', 'string', 'max:80'],
            'last_name'   => ['required', 'string', 'max:80'],
            'email'       => ['required', 'email', 'max:190', 'unique:users,email'],
            'employee_id' => ['required', 'string', 'max:60', 'unique:users,employee_id'],
            'password'    => ['required', Password::min(8)],
            'terms'       => ['accepted'],
        ], [
            'password.min'   => 'Use at least 8 characters.',
            'terms.accepted' => 'You must agree to the Terms of use and Privacy policy.',
        ]);

        $email = strtolower(trim($data['email']));
        $fullName = trim($data['first_name']) . ' ' . trim($data['last_name']);
        $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', trim($data['first_name']) . '.' . trim($data['last_name'])));
        $username = $baseUsername;
        $suffix = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $suffix++;
        }

        $user = new User([
            'name'        => $fullName,
            'first_name'  => trim($data['first_name']),
            'last_name'   => trim($data['last_name']),
            'username'    => $username,
            'employee_id' => trim($data['employee_id']),
            'email'       => $email,
            'password'    => $data['password'],
        ]);
        $user->forceFill([
            'role'                => 'sb_member',
            'is_active'           => false,
            'status'              => 'pending',
            'terms_accepted_at'   => now(),
            'password_changed_at' => now(),
        ])->save();

        $request->session()->regenerate();
        $request->session()->put('staff_verification_user_id', $user->id);

        try {
            $emailOtpService->send($user->email, 'staff_registration');
        } catch (ValidationException $exception) {
            $user->delete();
            $request->session()->forget('staff_verification_user_id');

            return back()
                ->withInput($request->except('password'))
                ->withErrors([
                    'email' => $exception->errors()['otp'][0] ?? 'The verification email could not be sent.',
                ]);
        }

        return redirect()->route('staff.register.verify');
    }

    /** Staff · 02 Verify email */
    public function verifyStaffPage(Request $request)
    {
        $userId = $request->session()->get('staff_verification_user_id');
        $user = $userId ? User::find($userId) : null;

        if (! $user || $user->role !== 'sb_member' || $user->status !== 'pending') {
            $request->session()->forget('staff_verification_user_id');

            return redirect()->route('staff.register')->withErrors([
                'email' => 'Please complete your Staff account setup first.',
            ]);
        }

        return view('auth.staff-verify', [
            'user'        => $user,
            'maskedEmail' => $this->maskEmail($user->email),
        ]);
    }

    /** Staff · 02 Verify 6-digit OTP -> Staff · 03 Account under review */
    public function verifyStaff(Request $request, EmailOtpService $emailOtpService)
    {
        $request->merge([
            'otp' => preg_replace('/\D+/', '', (string) $request->input('otp', '')),
        ]);

        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Please enter the 6-digit verification code.',
            'otp.digits'   => 'The verification code must be 6 digits.',
        ]);

        $userId = $request->session()->get('staff_verification_user_id');
        $user = $userId ? User::find($userId) : null;

        if (! $user || $user->role !== 'sb_member' || $user->status !== 'pending') {
            $request->session()->forget('staff_verification_user_id');

            return redirect()->route('staff.register')->withErrors([
                'email' => 'Your verification session expired. Please start again.',
            ]);
        }

        if (! $emailOtpService->verify($user->email, 'staff_registration', $data['otp'])) {
            return back()->withInput()->withErrors([
                'otp' => 'The code is incorrect or expired. Please try again or resend a new code.',
            ]);
        }

        $user->forceFill([
            'verified_at'       => now(),
            'email_verified_at' => now(),
            'is_active'         => false,
            'status'            => 'pending',
        ])->save();

        $request->session()->forget('staff_verification_user_id');
        $request->session()->put('staff_pending_notice', true);

        AuditLogger::activity('Staff email verified', 'Awaiting municipal administrator approval', $user);

        return redirect()->route('staff.register.pending');
    }

    /** Staff · 02 Resend verification code */
    public function resendStaffVerification(Request $request, EmailOtpService $emailOtpService)
    {
        $userId = $request->session()->get('staff_verification_user_id');
        $user = $userId ? User::find($userId) : null;

        if (! $user || $user->role !== 'sb_member' || $user->status !== 'pending') {
            $request->session()->forget('staff_verification_user_id');

            return redirect()->route('staff.register');
        }

        try {
            $emailOtpService->send($user->email, 'staff_registration');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'A new 6-digit verification code has been sent.');
    }

    /** Staff · 03 Access pending */
    public function staffPending()
    {
        return view('auth.staff-pending');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $first = mb_substr($local, 0, 1);
        return $first . '••••@' . $domain;
    }
}
