<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Concern extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'barangay', 'description', 'location', 'categories',
        'photo_path', 'photo_name', 'submitted_at', 'analyzed_at', 'matched_at', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'categories'   => 'array',
            'submitted_at' => 'datetime',
            'analyzed_at'  => 'datetime',
            'matched_at'   => 'datetime',
            'reviewed_at'  => 'datetime',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }

    public function getRouteKeyName(): string { return 'reference'; }

    /** Index of the latest completed stage: 0 received, 1 analyzed, 2 matched, 3 reviewed. */
    public function lastStage(): int
    {
        if ($this->reviewed_at) return 3;
        if ($this->matched_at)  return 2;
        if ($this->analyzed_at) return 1;

        return 0;
    }

    /** Category keys to display ("other" when none were picked). */
    public function categoryKeys(): array
    {
        return $this->categories ?: ['other'];
    }
}
