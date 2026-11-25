<?php

namespace App\Services;

use App\Models\EmailOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Creates, sends, and checks one-time email codes for staff verification and password reset. */
class EmailOtpService
{
    private const CODE_MINUTES = 10;
    private const RESEND_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    /** Generate and email a new six-digit code. */
    public function send(string $email, string $purpose): void
    {
        if (! in_array($purpose, ['citizen_registration', 'staff_registration', 'password_reset'], true)) {
            throw ValidationException::withMessages([
                'otp' => 'The verification request is invalid. Please try again.',
            ]);
        }

        $this->assertMailIsSetUp();

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
            ], function ($message) use ($email, $purpose) {
                $subject = $purpose === 'password_reset'
                    ? 'Your Victoria password reset code'
                    : 'Your Victoria account verification code';
                $message->to($email)->subject($subject);
            });
        } catch (Throwable $exception) {
            $otp->delete();
            report($exception);

            throw ValidationException::withMessages(['otp' => self::explain($exception)]);
        }
    }

    /** Send a newly created citizen temporary password by email. */
    public function sendTemporaryPassword(string $email, string $name, string $temporaryPassword): void
    {
        $this->assertMailIsSetUp();

        try {
            Mail::send('emails.temporary-password', [
                'name' => $name,
                'email' => $email,
                'temporaryPassword' => $temporaryPassword,
            ], function ($message) use ($email) {
                $message->to($email)->subject('Your Victoria Citizen Account Temporary Password');
            });
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages(['email' => self::explain($exception)]);
        }
    }

    private function assertMailIsSetUp(): void
    {
        $default = config('mail.default');

        if (in_array($default, ['log', 'array'], true)) {
            $this->fail('Email sending is switched off (MAIL_MAILER is "'.$default.'", so nothing is really sent). '
                .'Set MAIL_MAILER=smtp and your Gmail details in .env, then run php artisan config:clear.');
        }

        $smtp = config('mail.mailers.smtp', []);
        $user = (string) ($smtp['username'] ?? '');
        $pass = (string) ($smtp['password'] ?? '');

        if ($default === 'smtp' && str_contains((string) ($smtp['host'] ?? ''), 'gmail')
            && ($user === '' || $pass === '' || str_contains($user, 'your-gmail') || str_contains($pass, 'your-16'))) {
            $this->fail('Gmail is not set up yet: MAIL_USERNAME and MAIL_PASSWORD in .env are empty or still show the example values. '
                .'Put your Gmail address and a Google App Password there, then run php artisan config:clear.');
        }
    }

    private function fail(string $debugMessage): never
    {
        throw ValidationException::withMessages([
            'otp' => config('app.debug') ? $debugMessage : 'We could not send the verification email right now. Please try again later or contact the Municipal Hall.',
        ]);
    }

    public static function explain(Throwable $exception): string
    {
        if (! config('app.debug')) {
            return 'We could not send the verification email right now. Please try again later or contact the Municipal Hall.';
        }

        $text = mb_strtolower($exception->getMessage());

        $hint = match (true) {
            str_contains($text, 'application-specific password'), str_contains($text, '5.7.9'),
            str_contains($text, 'username and password not accepted'), str_contains($text, '535'),
            str_contains($text, 'failed to authenticate')
                => 'Gmail rejected the login. In .env, MAIL_PASSWORD must be a 16-character Google App Password (not your normal Gmail password), and MAIL_USERNAME must be the same Gmail account. 2-Step Verification has to be turned on to create an App Password.',
            str_contains($text, '530'), str_contains($text, 'authentication required')
                => 'Gmail needs a username and password. Fill in MAIL_USERNAME and MAIL_PASSWORD in .env.',
            str_contains($text, 'certificate'), str_contains($text, 'stream_socket_enable_crypto'), str_contains($text, 'starttls')
                => 'PHP could not verify the security certificate of Gmail (common on Windows). Set openssl.cafile in php.ini, or for local testing only add MAIL_VERIFY_PEER=false to .env.',
            str_contains($text, 'connection could not be established'), str_contains($text, 'timed out'),
            str_contains($text, 'unable to connect'), str_contains($text, 'getaddrinfo'), str_contains($text, 'php_network')
                => 'This computer could not reach smtp.gmail.com. Check the internet connection and that no firewall or antivirus blocks port 587. You can also try MAIL_PORT=465 with MAIL_SCHEME=smtps.',
            str_contains($text, '5.4.5'), str_contains($text, 'daily user sending limit')
                => 'This Gmail account reached its daily sending limit. Try again tomorrow or use another Gmail account.',
            str_contains($text, 'does not comply with addressing'), str_contains($text, 'invalid address')
                => 'The email address looks invalid. Please check it and try again.',
            default
                => 'Check the Gmail settings in .env, run php artisan config:clear, and run php artisan mail:test to see the exact problem.',
        };

        return 'We could not send the verification email. '.$hint.' [Technical detail: '.Str::limit($exception->getMessage(), 300).']';
    }

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
