<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/** Command that creates an SB member account (there is no sign-up page for staff). */
class CreateSbMember extends Command
{
    protected $signature = 'sb:create {name : Full name} {email : Email address (used to sign in)} {--password= : Leave out to be asked}';

    protected $description = 'Create an SB member (staff) account';

    /** Ask for a password if none was given, check everything, then save the account. */
    public function handle(): int
    {
        $password = $this->option('password') ?: $this->secret('Password (12+ characters, with letters, numbers and a symbol)');

        $v = Validator::make(
            ['name' => $this->argument('name'), 'email' => $this->argument('email'), 'password' => $password],
            [
                'name'     => ['required', 'string', 'max:120'],
                'email'    => ['required', 'email', 'max:190', 'unique:users,email'],
                'password' => ['required', Password::min(12)->letters()->numbers()->symbols()],
            ]
        );

        if ($v->fails()) {
            foreach ($v->errors()->all() as $error) $this->error($error);

            return self::FAILURE;
        }

        $user = new User(['name' => $this->argument('name'), 'email' => $this->argument('email'), 'password' => $password]);
        $user->forceFill(['role' => 'sb_member', 'is_active' => true, 'password_changed_at' => now()])->save();

        AuditLogger::security('SB member account created', $user->name.' · '.$user->email);

        $this->info("SB member account created. They can sign in at /login with {$user->email}.");

        return self::SUCCESS;
    }
}
