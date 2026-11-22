<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One line in a concern's activity log, like "Viewed by staff" or "Match confirmed". */
class ConcernLog extends Model
{
    protected $fillable = ['concern_id', 'user_id', 'text'];

    /** The staff member who did it (empty for automatic entries). */
    public function user() { return $this->belongsTo(User::class); }
}
