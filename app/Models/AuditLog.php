<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One row in the audit trail or history: who did what, and when. */
class AuditLog extends Model
{
    protected $fillable = ['actor_id', 'actor_name', 'type', 'event', 'detail', 'ip_address'];

    /** The account that did the action. */
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }

    /** "Today, 10:42 AM" / "Yesterday, 4:18 PM" / "Nov 2, 9:15 AM" */
    public function getWhenLabelAttribute(): string
    {
        $d = $this->created_at;

        if ($d->isToday()) return 'Today, '.$d->format('g:i A');
        if ($d->isYesterday()) return 'Yesterday, '.$d->format('g:i A');

        return $d->format($d->isCurrentYear() ? 'M j, g:i A' : 'M j, Y, g:i A');
    }
}
