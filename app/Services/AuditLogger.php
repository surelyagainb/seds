<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

/** Helper for writing audit trail and history entries. */
class AuditLogger
{
    /** Security change: password, permissions, roles, deactivation. Shown under "Audit trail". */
    public static function security(string $event, ?string $detail = null, ?User $actor = null): AuditLog
    {
        return static::write('audit', $event, $detail, $actor);
    }

    /** General workspace activity. Shown under "History". */
    public static function activity(string $event, ?string $detail = null, ?User $actor = null): AuditLog
    {
        return static::write('history', $event, $detail, $actor);
    }

    /** Save one log row with the person who did it (or "System") and their IP address. */
    protected static function write(string $type, string $event, ?string $detail, ?User $actor): AuditLog
    {
        $actor ??= auth()->user();

        return AuditLog::create([
            'actor_id'   => $actor?->id,
            'actor_name' => $actor?->name ?? 'System',
            'type'       => $type,
            'event'      => $event,
            'detail'     => $detail,
            'ip_address' => request()?->ip(),
        ]);
    }
}
