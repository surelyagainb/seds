<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = ['role', 'permission', 'allowed'];
    protected $casts = ['allowed' => 'boolean'];

    protected static array $cache = [];

    /** [permission => bool] for one role, cached for the request. */
    public static function mapFor(string $role): array
    {
        return static::$cache[$role] ??= static::where('role', $role)
            ->pluck('allowed', 'permission')
            ->map(fn ($v) => (bool) $v)
            ->all();
    }

    public static function flushCache(): void
    {
        static::$cache = [];
    }
}
