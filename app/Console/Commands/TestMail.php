<?php

namespace App\Console\Commands;

use App\Services\EmailOtpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Command that checks the Gmail settings and sends a test email, showing the exact error if it fails. */
class TestMail extends Command
{
    protected $signature = 'mail:test {email? : Where to send the test email (default: the Gmail account itself)}';

    protected $description = 'Check the email settings and send a test email';

    /** Show the settings that are really being used, warn about common mistakes, then try to send. */
    public function handle(): int
    {
        $default = config('mail.default');
        $smtp    = config('mail.mailers.smtp', []);
        $user    = (string) ($smtp['username'] ?? '');
        $pass    = (string) ($smtp['password'] ?? '');
        $to      = $this->argument('email') ?: $user;

        $this->info('Settings Laravel is using right now:');
        $this->table(['Setting', 'Value'], [
            ['MAIL_MAILER', $default],
            ['MAIL_HOST', $smtp['host'] ?? ''],
            ['MAIL_PORT', $smtp['port'] ?? ''],
            ['MAIL_SCHEME', $smtp['scheme'] ?: '(default: smtp, encrypts with STARTTLS)'],
            ['MAIL_USERNAME', $user ?: '(empty)'],
            ['MAIL_PASSWORD', $pass === '' ? '(empty)' : str_repeat('*', strlen($pass)).'  ('.strlen($pass).' characters)'],
            ['From address', config('mail.from.address')],
            ['Timeout (seconds)', $smtp['timeout'] ?? ''],
            ['Verify certificate', ($smtp['verify_peer'] ?? true) ? 'yes' : 'NO (testing only)'],
        ]);

        // Common mistakes
        if (app()->configurationIsCached()) $this->warn('The configuration is cached, so changes in .env are ignored. Run: php artisan config:clear');
        if (! extension_loaded('openssl')) $this->warn('The PHP openssl extension is not enabled. Gmail needs it. Enable extension=openssl in php.ini.');
        if (in_array($default, ['log', 'array'], true)) $this->warn("MAIL_MAILER is \"{$default}\": nothing is really sent. Set MAIL_MAILER=smtp in .env.");
        if (str_contains($user, 'your-gmail') || str_contains($pass, 'your-16')) $this->warn('MAIL_USERNAME or MAIL_PASSWORD still shows the example value from .env.example.');
        if ($pass !== '' && strlen($pass) !== 16) $this->warn('A Google App Password has exactly 16 characters (without spaces). Yours has '.strlen($pass).'.');
        if ($user !== '' && ! str_ends_with(mb_strtolower($user), '@gmail.com') && str_contains((string) ($smtp['host'] ?? ''), 'gmail')) {
            $this->warn('MAIL_USERNAME is not a @gmail.com address. For Google Workspace accounts this is fine; for normal Gmail it must end with @gmail.com.');
        }

        if (! $to) {
            $this->error('No address to send to. Run: php artisan mail:test someone@example.com');
            return self::FAILURE;
        }

        $this->line('');
        $this->info("Sending a test email to {$to} ...");

        try {
            Mail::raw('This is a test email from the Victoria system. If you can read this, Gmail sending works.', function ($message) use ($to) {
                $message->to($to)->subject('Victoria mail test');
            });
        } catch (Throwable $e) {
            $this->error('FAILED: '.$e->getMessage());
            $this->line('');
            $this->warn('What it probably means:');
            $this->line(preg_replace('/^We could not send the verification email\. | \[Technical detail:.*$/s', '', $this->hintFor($e)));

            return self::FAILURE;
        }

        $this->info('Sent. Check the inbox (and the Spam folder) of '.$to.'.');

        return self::SUCCESS;
    }

    /** The same plain-language hint used on the login page (always in debug wording here). */
    private function hintFor(Throwable $e): string
    {
        $previous = config('app.debug');
        config(['app.debug' => true]);
        $hint = EmailOtpService::explain($e);
        config(['app.debug' => $previous]);

        return $hint;
    }
}
