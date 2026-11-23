<?php

namespace App\Services;

use App\Models\Concern;
use Illuminate\Support\Collection;

/**
 * Finds problems that keep coming back.
 * One "issue" = one barangay + one category. It counts as recurring when it was reported
 * in at least config('staff.recurring_min_months') different months inside the chosen period.
 */
class RecurringIssues
{
    /** The last $months calendar months as ['2026-06' => 'Jun', ...], oldest first. */
    public static function monthLabels(int $months): array
    {
        $out = [];
        $start = now()->startOfMonth()->subMonths($months - 1);

        for ($i = 0; $i < $months; $i++) {
            $m = $start->copy()->addMonths($i);
            $out[$m->format('Y-m')] = $m->format('M');
        }

        return $out;
    }

    /**
     * The recurring issues in the period, longest-recurring first.
     * Each item: key, barangay, category, times, span (months), first, last, months (counts per month), ordinance_id.
     */
    public function find(int $months, ?string $barangay = null, ?string $category = null, array $assigned = []): Collection
    {
        $labels = static::monthLabels($months);
        $from   = now()->startOfMonth()->subMonths($months - 1);

        $concerns = Concern::filter(['barangay' => $barangay])
            ->whereNull('canonical_concern_id')
            ->with('matches:id,concern_id,ordinance_id,score')
            ->where('submitted_at', '>=', $from)
            ->get(['id', 'barangay', 'categories', 'submitted_at', 'matched_ordinance_id']);

        // Group the concerns by barangay + category (a concern with 2 categories counts in both)
        $groups = [];
        foreach ($concerns as $c) {
            foreach ($c->categoryKeys() as $cat) {
                if ($category && $cat !== $category) continue;
                if ($assigned && ! in_array($cat, $assigned, true)) continue;
                $groups[$c->barangay.'|'.$cat][] = $c;
            }
        }

        $min = (int) config('staff.recurring_min_months', 2);
        $out = collect();

        foreach ($groups as $key => $list) {
            [$brgy, $cat] = explode('|', $key, 2);

            $perMonth = array_fill_keys(array_keys($labels), 0);
            foreach ($list as $c) $perMonth[$c->submitted_at->format('Y-m')]++;

            $span = count(array_filter($perMonth));
            if ($span < $min) continue;

            // The ordinance most of these concerns point to
            $ordinanceId = collect($list)->map(fn ($c) => $c->bestOrdinanceId())->filter()
                ->countBy()->sortDesc()->keys()->first();

            $dates = collect($list)->pluck('submitted_at');

            $out->push([
                'key'          => $key,
                'barangay'     => $brgy,
                'category'     => $cat,
                'times'        => count($list),
                'span'         => $span,
                'first'        => $dates->min(),
                'last'         => $dates->max(),
                'months'       => array_values($perMonth),
                'ordinance_id' => $ordinanceId,
            ]);
        }

        return $out->sort(fn ($a, $b) => [$b['span'], $b['times'], $a['barangay']] <=> [$a['span'], $a['times'], $b['barangay']])->values();
    }
}
