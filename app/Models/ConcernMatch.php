<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One suggestion: "this ordinance may fit this concern", with a score and the reasons. */
class ConcernMatch extends Model
{
    protected $fillable = ['concern_id', 'ordinance_id', 'score', 'confidence', 'reasons'];

    /** How columns are converted: reasons become an array. */
    protected function casts(): array
    {
        return ['reasons' => 'array'];
    }

    /**
     * Convert the raw score into a 0-100% value for the staff progress bar,
     * based on the maximum possible score (category points + keyword cap).
     */
    public function getPercentAttribute(): int
    {
        $maxPossibleScore = config('matching.category_points') + config('matching.keyword_cap');

        if ($maxPossibleScore <= 0) {
            return 0;
        }

        $percentage = round(($this->score / $maxPossibleScore) * 100);

        return (int) min(100, $percentage);
    }

    /** The concern this suggestion is for. */
    public function concern() { return $this->belongsTo(Concern::class); }

    /** The suggested ordinance. */
    public function ordinance() { return $this->belongsTo(Ordinance::class); }
}