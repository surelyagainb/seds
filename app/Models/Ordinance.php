<?php

namespace App\Models;

use App\Services\OrdinanceMatcher;
use Illuminate\Database\Eloquent\Model;

/** An ordinance that can be suggested for a concern.
 *
 * @property int $id
 * @property string|null $number
 * @property string $title
 * @property string $category
 * @property array|null $keywords
 * @property array|null $sections
 */
class Ordinance extends Model
{
    /** The statuses an ordinance can have. "Draft" ordinances are never suggested for concerns. */
    public const STATUSES = ['Active', 'Under review', 'Draft'];

    protected $fillable = ['number', 'title', 'category', 'status', 'version', 'enacted_on', 'keywords', 'summary', 'sections'];

    /** How columns are converted: keywords and sections become arrays, enacted_on becomes a date. */
    protected function casts(): array
    {
        return [
            'keywords'   => 'array',
            'sections'   => 'array',
            'enacted_on' => 'date',
        ];
    }

    /** The suggestions that point to this ordinance. */
    public function matches() { return $this->hasMany(ConcernMatch::class); }

    /** Colour of the status badge: green, amber or gray. */
    public function statusClass(): string
    {
        return match ($this->status) {
            'Active' => 'green',
            'Draft'  => 'gray',
            default  => 'amber',
        };
    }

    /** The section whose text shares the most keywords with the concern (first section if none). */
    public function bestSection(array $hits): ?array
    {
        $sections = $this->sections ?: [];
        $best = $sections[0] ?? null;
        $top  = -1;

        foreach ($sections as $s) {
            $score = 0;
            foreach ($hits as $h) {
                if ($h !== '' && preg_match('/\b'.preg_quote($h, '/').'/iu', $s['text'] ?? '')) $score++;
            }
            if ($score > $top) { $top = $score; $best = $s; }
        }

        return $best;
    }
}
