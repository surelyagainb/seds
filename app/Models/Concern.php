<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A concern a citizen submitted, with its reference number and review progress. */
class Concern extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'barangay', 'description', 'location', 'categories',
        'photo_path', 'photo_name', 'submitted_at', 'analyzed_at', 'matched_at', 'reviewed_at', 'resolved_at',
    ];

    /** How columns are converted: categories become an array, timestamps become date objects. */
    protected function casts(): array
    {
        return [
            'categories'   => 'array',
            'submitted_at' => 'datetime',
            'analyzed_at'  => 'datetime',
            'matched_at'   => 'datetime',
            'reviewed_at'  => 'datetime',
            'resolved_at'  => 'datetime',
        ];
    }

    /** The citizen who submitted this concern. */
    public function user() { return $this->belongsTo(User::class); }

    /** Use the reference number (not the id) in URLs. */
    public function getRouteKeyName(): string { return 'reference'; }

    /** Index of the latest completed stage: 0 received, 1 analyzed, 2 matched, 3 reviewed. */
    public function lastStage(): int
    {
        if ($this->reviewed_at) return 3;
        if ($this->matched_at)  return 2;
        if ($this->analyzed_at) return 1;

        return 0;
    }

    /** Short status shown on the dashboard: Under Review, Reviewed or Resolved. */
    public function statusLabel(): string
    {
        if ($this->resolved_at) return 'Resolved';
        if ($this->reviewed_at) return 'Reviewed';

        return 'Under Review';
    }

    public function statusClass(): string
    {
        return match ($this->statusLabel()) {
            'Resolved' => 'resolved',
            'Reviewed' => 'reviewed',
            default    => 'review',
        };
    }

    /** Category keys to display ("other" when none were picked). */
    public function categoryKeys(): array
    {
        return $this->categories ?: ['other'];
    }
}
