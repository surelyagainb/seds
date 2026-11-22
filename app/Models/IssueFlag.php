<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A recurring issue (one barangay + one category) that staff flagged for review or for a new ordinance. */
class IssueFlag extends Model
{
    public const REVIEW = 'review';
    public const NEW_ORDINANCE = 'new_ordinance';

    protected $fillable = ['type', 'barangay', 'category', 'ordinance_id', 'user_id'];

    /** The staff member who flagged it. */
    public function user() { return $this->belongsTo(User::class); }
}
