<?php

namespace App\Services;

use App\Models\EmailOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Creates, sends, and checks one-time email codes for login and registration. */
class EmailOtpService
{
    private const CODE_MINUTES = 5;
    private const RESEND_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    /** Generate and email a new six-digit code. */
    public function send(string $email, string $purpose): void
    {
        if (! in_array($purpose, ['login', 'registration'], true)) {
            throw ValidationException::withMessages([
                'otp' => 'The verification request is invalid. Please try again.',
            ]);
        }

        $otp = EmailOtp::firstOrNew([
            'email' => $email,
            'purpose' => $purpose,
        ]);

        if ($otp->exists && $otp->last_sent_at && $otp->last_sent_at->gt(now()->subSeconds(self::RESEND_SECONDS))) {
            throw ValidationException::withMessages([
                'otp' => 'Please wait 60 seconds before requesting another code.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        $otp->fill([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_MINUTES),
            'attempts' => 0,
            'last_sent_at' => now(),
        ])->save();

        try {
            Mail::send('emails.otp', [
                'code' => $code,
                'minutes' => self::CODE_MINUTES,
                'purpose' => $purpose,
            ], function ($message) use ($email) {
                $message->to($email)->subject('Your Victoria verification code');
            });
        } catch (Throwable $exception) {
            $otp->delete();
            report($exception);

            throw ValidationException::withMessages([
                'otp' => 'We could not send the email. Please check the Gmail mail settings and try again.',
            ]);
        }
    }

    /** Check a code. It expires after five minutes and allows five incorrect attempts. */
    public function verify(string $email, string $purpose, string $code): bool
    {
        $otp = EmailOtp::where('email', $email)
            ->where('purpose', $purpose)
            ->first();

        if (! $otp) {
            return false;
        }

        if ($otp->expires_at->isPast() || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->delete();

        return true;
    }
}
