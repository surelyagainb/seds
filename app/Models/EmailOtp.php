<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Stores a hashed, short-lived email verification code. */
class EmailOtp extends Model
{
    protected $fillable = [
        'email',
        'purpose',
        'code_hash',
        'expires_at',
        'attempts',
        'last_sent_at',
    ];

    /** Convert the expiry and send-time columns to date objects. */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
