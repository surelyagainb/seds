<?php

// Settings for the rule-based ordinance matching (see app/Services/OrdinanceMatcher.php).
// Change the numbers here to make the matching stricter or looser. No code changes needed.
return [
    // Rule 1: the concern's category is the same as the ordinance's category
    'category_points' => 40,

    // Rule 2: each different ordinance keyword found in the concern text (description + location)
    'keyword_points' => 12,
    'keyword_cap'    => 36,   // keyword points never go above this

    // An ordinance is only suggested if its total score is at least this
    'min_score' => 24,

    // Score needed for each confidence label (anything between min_score and medium is "low")
    'medium' => 40,
    'high'   => 64,

    // How many suggestions to keep per concern
    'max_results' => 5,
];
