<?php

namespace App\Support;

/** Small helper that marks keywords inside a text (for the compare view). */
class Highlight
{
    /**
     * Returns safe HTML: the text is escaped first, then each word that STARTS with a keyword
     * is wrapped in <mark>. ("floods" is marked for the keyword "flood".)
     */
    public static function words(string $text, array $keywords): string
    {
        $safe  = e($text);
        $words = array_filter(array_map(fn ($k) => preg_replace('/[^a-z0-9]/i', '', (string) $k), $keywords));

        if (! $words) return $safe;

        $pattern = '/(?<![&#\w])('.implode('|', array_map(fn ($w) => preg_quote($w, '/'), $words)).')\w*/iu';

        return preg_replace($pattern, '<mark>$0</mark>', $safe);
    }
}
