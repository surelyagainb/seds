<?php

namespace App\Support;

use Illuminate\Support\Str;

final class TemporaryPasswordGenerator
{
    private const LENGTH = 8;

    public static function generate(): string
    {
        return Str::random(self::LENGTH);
    }
}
