<?php

namespace App\Services;

use App\Models\Concern;
use App\Models\ConcernMatch;
use App\Models\Ordinance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rule-based matching of a citizen's concern to municipal ordinances.
 *
 * Why rule-based (no AI)?
 * Every suggestion comes from two transparent rules so SB members can always
 * see and explain WHY an ordinance was suggested:
 *   1. Category match -> +40 points (from config/matching.php)
 *   2. Keyword match  -> +12 points per unique keyword found, up to +36 points
 */
class OrdinanceMatcher
{
    /**
     * Find and rank ordinances that fit a concern without saving anything to the database.
     *
     * @return Collection<int, array{ordinance: Ordinance, score: int, confidence: string, reasons: array<int, string>}>
     */
    public function match(Concern $concern): Collection
    {
        $rules            = config('matching');
        $categoryLabels   = $this->categoryLabels();
        $concernText      = self::buildSearchableText($concern);
        $concernCategories = $concern->categories ?: [];

        // Draft ordinances are excluded so unfinished ordinances are never suggested.
        $activeOrdinances = Ordinance::where('status', '!=', 'Draft')->get();

        $suggestions = [];

        foreach ($activeOrdinances as $ordinance) {
            $scoreResult = self::score(
                $concernCategories,
                $concernText,
                $ordinance->category,
                $ordinance->keywords ?? [],
                $rules
            );

            // Skip ordinances that do not meet the minimum required score.
            if ($scoreResult['score'] < $rules['min_score']) {
                continue;
            }

            $suggestions[] = [
                'ordinance'  => $ordinance,
                'score'      => $scoreResult['score'],
                'confidence' => self::confidence($scoreResult['score'], $rules),
                'reasons'    => $this->buildReasons($scoreResult, $ordinance->category, $categoryLabels),
            ];
        }

        // Sort highest score first; if tied, sort alphabetically by ordinance title.
        usort($suggestions, function (array $first, array $second): int {
            if ($first['score'] !== $second['score']) {
                return $second['score'] <=> $first['score'];
            }

            return strcmp($first['ordinance']->title, $second['ordinance']->title);
        });

        // Keep only the top N results defined in config/matching.php.
        $topSuggestions = array_slice($suggestions, 0, $rules['max_results']);

        return collect($topSuggestions);
    }

    /**
     * Run the matching rules and save the results to the database.
     * Returns the number of suggested ordinances saved.
     */
    public function matchAndSave(Concern $concern): int
    {
        // Never overwrite matches after a staff member has already reviewed the concern.
        if ($concern->reviewed_at) {
            return 0;
        }

        $matches = $this->match($concern);

        DB::transaction(function () use ($concern, $matches) {
            // Remove old suggestions first so re-running the matcher never creates duplicates.
            ConcernMatch::where('concern_id', $concern->id)->delete();

            foreach ($matches as $match) {
                ConcernMatch::create([
                    'concern_id'   => $concern->id,
                    'ordinance_id' => $match['ordinance']->id,
                    'score'        => $match['score'],
                    'confidence'   => $match['confidence'],
                    'reasons'      => $match['reasons'],
                ]);
            }

            // Update the citizen's 4-stage tracker timestamps:
            // - analyzed_at is always set once checked.
            // - matched_at is set only if at least one ordinance met the minimum score.
            if (! $concern->analyzed_at) {
                $concern->analyzed_at = now();
            }

            $concern->matched_at = $matches->isEmpty()
                ? null
                : ($concern->matched_at ?? now());

            $concern->save();
        });

        return $matches->count();
    }

    /**
     * Score ONE ordinance against a concern using Rule 1 (category) and Rule 2 (keywords).
     * Kept database-free so it is easy to understand and unit test.
     *
     * @return array{score: int, category_match: bool, keywords: array<int, string>}
     */
    public static function score(
        array $categories,
        string $text,
        string $ordinanceCategory,
        array $keywords,
        array $rules
    ): array {
        $totalScore = 0;

        // Rule 1: Does the concern have the same category as the ordinance?
        $isCategoryMatch = in_array($ordinanceCategory, $categories, true);
        if ($isCategoryMatch) {
            $totalScore += $rules['category_points'];
        }

        // Rule 2: Which unique ordinance keywords appear in the concern text?
        $foundKeywords = self::findMatchingKeywords($text, $keywords);

        $keywordPoints = count($foundKeywords) * $rules['keyword_points'];
        $cappedKeywordPoints = min($keywordPoints, $rules['keyword_cap']);
        $totalScore += $cappedKeywordPoints;

        return [
            'score'          => $totalScore,
            'category_match' => $isCategoryMatch,
            'keywords'       => $foundKeywords,
        ];
    }

    /**
     * Find which keywords from an ordinance appear in the given text.
     * Each unique keyword is counted at most once.
     *
     * @return array<int, string>
     */
    public static function findMatchingKeywords(string $text, array $keywords): array
    {
        // Step 1: Clean, lowercase, and remove empty or duplicate keywords.
        $cleanedKeywords = [];
        foreach ($keywords as $rawKeyword) {
            $normalized = mb_strtolower(trim((string) $rawKeyword));

            if ($normalized !== '' && ! in_array($normalized, $cleanedKeywords, true)) {
                $cleanedKeywords[] = $normalized;
            }
        }

        // Step 2: Check whether each keyword appears at the start of a word in the text.
        $matchedKeywords = [];
        foreach ($cleanedKeywords as $keyword) {
            // "\b" only at the START allows word endings/variations to match too:
            //   - "flood" matches "flood" and "flooding"
            //   - "rain" does NOT match "train"
            // Flags: "i" = case-insensitive, "u" = proper UTF-8 support.
            $pattern = '/\b' . preg_quote($keyword, '/') . '/iu';

            if (preg_match($pattern, $text) === 1) {
                $matchedKeywords[] = $keyword;
            }
        }

        return $matchedKeywords;
    }

    /**
     * Convenience helper for controllers/views to get the matched keywords
     * between one concern and one ordinance (used for text highlighting).
     *
     * @return array<int, string>
     */
    public static function matchedKeywordsForConcern(Concern $concern, Ordinance $ordinance): array
    {
        return self::findMatchingKeywords(
            self::buildSearchableText($concern),
            $ordinance->keywords ?? []
        );
    }

    /**
     * Convert a numeric score into a human-readable confidence level.
     */
    public static function confidence(int $score, array $rules): string
    {
        if ($score >= $rules['high']) {
            return 'high';
        }

        if ($score >= $rules['medium']) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Combine description and location into one string for keyword scanning.
     */
    private static function buildSearchableText(Concern $concern): string
    {
        return trim($concern->description . ' ' . $concern->location);
    }

    /**
     * Build plain-text explanations so staff can see why an ordinance was suggested.
     *
     * @return array<int, string>
     */
    private function buildReasons(array $scoreResult, string $ordinanceCategory, array $categoryLabels): array
    {
        $reasons = [];

        if ($scoreResult['category_match']) {
            $label = $categoryLabels[$ordinanceCategory] ?? $ordinanceCategory;
            $reasons[] = 'Category match: ' . $label;
        }

        if (! empty($scoreResult['keywords'])) {
            $reasons[] = 'Keywords found: ' . implode(', ', $scoreResult['keywords']);
        }

        return $reasons;
    }

    /**
     * Map category keys (e.g. "flood") to display names (e.g. "Drainage & Waterway Obstruction / Flooding").
     *
     * @return array<string, string>
     */
    private function categoryLabels(): array
    {
        $labels = [];

        foreach (config('concerns.categories', []) as $key => $category) {
            $labels[$key] = $category['label'];
        }

        return $labels;
    }
}