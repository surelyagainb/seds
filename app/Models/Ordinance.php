<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An ordinance that can be suggested for a concern.
 *
 * @property int $id
 * @property string|null $number
 * @property string $title
 * @property string $category
 * @property array|null $keywords
 * @property array|null $sections
 * @property string|null $file_path
 * @property string|null $file_name
 */
class Ordinance extends Model
{
    /** The statuses an ordinance can have. "Draft" ordinances are never suggested for concerns. */
    public const STATUSES = ['Active', 'Under review', 'Draft'];

    protected $fillable = ['number', 'title', 'category', 'status', 'version', 'enacted_on', 'keywords', 'summary', 'sections', 'file_path', 'file_name'];

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

    /**
     * Pick the ordinance section that mentions the most matched keywords.
     * Falls back to the first section if no keywords match any section text.
     */
    public function bestSection(array $matchedKeywords): ?array
    {
        $sections = $this->sections ?: [];

        if (empty($sections)) {
            return null;
        }

        $bestSection = $sections[0];
        $highestHitCount = -1;

        foreach ($sections as $section) {
            $sectionText = $section['text'] ?? '';
            $hitCount = 0;

            foreach ($matchedKeywords as $keyword) {
                if ($keyword === '') {
                    continue;
                }

                // Count how many of the concern's matched keywords appear in this section
                if (preg_match('/\b' . preg_quote($keyword, '/') . '/iu', $sectionText) === 1) {
                    $hitCount++;
                }
            }

            if ($hitCount > $highestHitCount) {
                $highestHitCount = $hitCount;
                $bestSection = $section;
            }
        }

        return $bestSection;
    }
}