<?php

namespace App\Services;

use App\Models\Concern;
use App\Models\ConcernMatch;
use App\Models\Ordinance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rule-based matching of a concern to ordinances. No AI: every suggestion comes from
 * two simple rules, so SB members can always see WHY an ordinance was suggested.
 *
 *   Rule 1 (category): the concern's category equals the ordinance's category  -> +40 points
 *   Rule 2 (keywords): each different ordinance keyword found in the concern    -> +12 points (max 36)
 *
 * Total score 0 to 76. Below min_score (24) the ordinance is not suggested.
 * Confidence: high (64+), medium (40-63), low (24-39). The numbers are in config/matching.php.
 */
class OrdinanceMatcher
{
    /** Find and rank the ordinances that fit a concern. Saves nothing. */
    public function match(Concern $concern): Collection
    {
        $rules      = config('matching');
        $labels     = collect(config('concerns.categories'))->map(fn ($c) => $c['label']);
        $text       = trim($concern->description.' '.$concern->location);
        $categories = $concern->categories ?: [];

        $results = [];

        foreach (Ordinance::where('status', '!=', 'Draft')->get() as $ordinance) {
            $r = self::score($categories, $text, $ordinance->category, $ordinance->keywords ?? [], $rules);

            if ($r['score'] < $rules['min_score']) continue;

            $reasons = [];
            if ($r['category_match']) {
                $reasons[] = 'Category match: '.($labels[$ordinance->category] ?? $ordinance->category);
            }
            if ($r['keywords']) {
                $reasons[] = 'Keywords found: '.implode(', ', $r['keywords']);
            }

            $results[] = [
                'ordinance'  => $ordinance,
                'score'      => $r['score'],
                'confidence' => self::confidence($r['score'], $rules),
                'reasons'    => $reasons,
            ];
        }

        // Highest score first; same score = alphabetical by title
        usort($results, fn ($a, $b) => $b['score'] <=> $a['score'] ?: strcmp($a['ordinance']->title, $b['ordinance']->title));

        return collect(array_slice($results, 0, $rules['max_results']));
    }

    /** Run the matching and save the suggestions. Returns how many ordinances were suggested. */
    public function matchAndSave(Concern $concern): int
    {
        // Never change a concern that staff already reviewed
        if ($concern->reviewed_at) return 0;

        $matches = $this->match($concern);

        DB::transaction(function () use ($concern, $matches) {
            // Start fresh so running it twice does not create duplicates
            ConcernMatch::where('concern_id', $concern->id)->delete();

            foreach ($matches as $m) {
                ConcernMatch::create([
                    'concern_id'   => $concern->id,
                    'ordinance_id' => $m['ordinance']->id,
                    'score'        => $m['score'],
                    'confidence'   => $m['confidence'],
                    'reasons'      => $m['reasons'],
                ]);
            }

            // Move the citizen's tracker forward: analyzed always, matched only if something was found
            if (! $concern->analyzed_at) $concern->analyzed_at = now();
            $concern->matched_at = $matches->isEmpty() ? null : ($concern->matched_at ?? now());
            $concern->save();
        });

        return $matches->count();
    }

    /**
     * Score ONE ordinance against a concern using the two rules.
     * Plain function with no database, so it is easy to test.
     * Returns: score, whether the category matched, and which keywords were found.
     */
    public static function score(array $categories, string $text, string $ordinanceCategory, array $keywords, array $rules): array
    {
        $score = 0;

        // Rule 1: same category
        $categoryMatch = in_array($ordinanceCategory, $categories, true);
        if ($categoryMatch) $score += $rules['category_points'];

        // Rule 2: keywords. A keyword counts once, and also matches word endings
        // (so "flood" finds "flooding", but "rain" does not find "train").
        $found = [];
        foreach (array_unique(array_map('mb_strtolower', array_map('trim', $keywords))) as $kw) {
            if ($kw !== '' && preg_match('/\b'.preg_quote($kw, '/').'/iu', $text)) $found[] = $kw;
        }
        $score += min(count($found) * $rules['keyword_points'], $rules['keyword_cap']);

        return ['score' => $score, 'category_match' => $categoryMatch, 'keywords' => $found];
    }

    /** Turn a score into a label: high, medium or low. */
    public static function confidence(int $score, array $rules): string
    {
        if ($score >= $rules['high'])   return 'high';
        if ($score >= $rules['medium']) return 'medium';

        return 'low';
    }
}
