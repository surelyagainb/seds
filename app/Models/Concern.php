<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A concern a citizen submitted, with its reference number and review progress. */
class Concern extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'barangay', 'description', 'location', 'categories',
        'photo_path', 'photo_name', 'submitted_at', 'analyzed_at', 'matched_at', 'reviewed_at', 'resolved_at',
        'decision', 'matched_ordinance_id', 'reviewed_by', 'viewed_at', 'assigned_to', 'suggest_new',
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
            'viewed_at'    => 'datetime',
            'suggest_new'  => 'boolean',
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

    /** The ordinance staff chose for this concern (when confirmed or rejected). */
    public function matchedOrdinance() { return $this->belongsTo(Ordinance::class, 'matched_ordinance_id'); }

    /** The activity log (viewed, confirmed, reassigned, ...). */
    public function logs() { return $this->hasMany(ConcernLog::class); }

    /** Add a line to the activity log. */
    public function log(string $text, ?User $user = null): ConcernLog
    {
        return $this->logs()->create(['text' => $text, 'user_id' => ($user ?? auth()->user())?->id]);
    }

    /** True if the concern was matched to at least one ordinance and staff did not mark it unmatched. */
    public function isMatched(): bool
    {
        return $this->matched_at !== null && $this->decision !== 'unmatched';
    }

    /**
     * Status for SB members: [label, colour].
     * Rejected, Unmatched, Reviewed, No ordinance found, or Needs review.
     */
    public function staffStatus(): array
    {
        if ($this->decision === 'rejected')  return ['Rejected', 'red'];
        if ($this->decision === 'unmatched') return ['Unmatched', 'gray'];
        if ($this->reviewed_at)              return ['Reviewed', 'green'];
        if ($this->analyzed_at && ! $this->matched_at) return ['No ordinance found', 'gray'];

        return ['Needs review', 'amber'];
    }

    /** Filter by the SB member status label (same rules as staffStatus()). */
    public function scopeStaffStatus($query, ?string $label)
    {
        return match ($label) {
            'Rejected'           => $query->where('decision', 'rejected'),
            'Unmatched'          => $query->where('decision', 'unmatched'),
            'Reviewed'           => $query->whereNotNull('reviewed_at')->where(fn ($w) => $w->whereNull('decision')->orWhere('decision', 'confirmed')),
            'No ordinance found' => $query->whereNull('reviewed_at')->whereNotNull('analyzed_at')->whereNull('matched_at'),
            'Needs review'       => $query->whereNull('reviewed_at')->where(fn ($w) => $w->whereNull('analyzed_at')->orWhereNotNull('matched_at')),
            default              => $query,
        };
    }

    /** The ordinance that fits best: the one staff chose, otherwise the highest-scoring suggestion. */
    public function bestOrdinanceId(): ?int
    {
        if ($this->matched_ordinance_id) return $this->matched_ordinance_id;

        return $this->matches->sortByDesc('score')->first()?->ordinance_id;
    }

    /** The ordinance suggestions the matcher made for this concern. */
    public function matches() { return $this->hasMany(ConcernMatch::class); }

    /**
     * Search and filter, used by the citizen dashboard and ready for the SB member interface.
     * Keys (all optional): q (search text), category, status, barangay. Unknown values are ignored.
     * Usage: Concern::filter($request->only(['q', 'category', 'status', 'barangay']))->get()
     */
    public function scopeFilter($query, array $filters)
    {
        // Search text: reference number, description, location or barangay
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(function ($w) use ($like) {
                $w->where('reference', 'like', $like)
                  ->orWhere('description', 'like', $like)
                  ->orWhere('location', 'like', $like)
                  ->orWhere('barangay', 'like', $like);
            });
        }

        // Category, like "health" (sanitation) or "waste" (waste management)
        $category = $filters['category'] ?? null;
        if ($category && array_key_exists($category, config('concerns.categories'))) {
            if ($category === 'other') {
                // Concerns with no category picked are shown as "Other", so include them too
                $query->where(fn ($w) => $w->whereJsonContains('categories', 'other')->orWhereJsonLength('categories', 0));
            } else {
                $query->whereJsonContains('categories', $category);
            }
        }

        // Barangay (must be one from the list)
        $barangay = $filters['barangay'] ?? null;
        if ($barangay && in_array($barangay, config('concerns.barangays'), true)) {
            $query->where('barangay', $barangay);
        }

        // Status: same meaning as statusLabel()
        match ($filters['status'] ?? null) {
            'under_review' => $query->whereNull('reviewed_at')->whereNull('resolved_at'),
            'reviewed'     => $query->whereNotNull('reviewed_at')->whereNull('resolved_at'),
            'resolved'     => $query->whereNotNull('resolved_at'),
            default        => null,
        };

        return $query;
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
