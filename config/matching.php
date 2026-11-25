<?php

/**
 * Settings for the rule-based ordinance matching (see app/Services/OrdinanceMatcher.php).
 * Adjust these numbers to make matching stricter or looser—no code changes required.
 *
 * Example scoring with default values:
 *   Max possible score = 40 (category) + 36 (keyword cap) = 76 points.
 *   - Category match + 2 keywords = 40 + 24 = 64 points -> "high" confidence
 *   - Category match + 0 keywords = 40 +  0 = 40 points -> "medium" confidence
 *   - No category match + 2 keywords = 0 + 24 = 24 points -> "low" confidence
 *   - No category match + 1 keyword  = 0 + 12 = 12 points -> below min_score (24), ignored
 */
return [
    // Rule 1: Points awarded if the concern's categories include the ordinance's category
    'category_points' => 40,

    // Rule 2: Points per unique ordinance keyword found in the concern (description + location)
    'keyword_points' => 12,
    'keyword_cap'    => 36, // Maximum points possible from keywords (e.g., 3 keywords * 12 = 36)

    // Minimum total score required for an ordinance to be suggested
    'min_score' => 24,

    // Confidence thresholds (scores from min_score up to medium - 1 are labeled "low")
    'medium' => 40,
    'high'   => 64,

    // Maximum number of suggested ordinances saved per concern
    'max_results' => 5,
];