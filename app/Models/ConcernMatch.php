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

    /** The score as a percentage of the best possible score (shown as a bar for SB members). */
    public function getPercentAttribute(): int
    {
        $max = config('matching.category_points') + config('matching.keyword_cap');

        return (int) min(100, round($this->score / max(1, $max) * 100));
    }

    /** The concern this suggestion is for. */
    public function concern() { return $this->belongsTo(Concern::class); }

    /** The suggested ordinance. */
    public function ordinance() { return $this->belongsTo(Ordinance::class); }
}
