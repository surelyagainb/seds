<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Concern;
use App\Models\IssueFlag;
use App\Services\RecurringIssues;
use Illuminate\Http\Request;

/** SB member home page: community overview with stats, map, chart and a table per barangay. */
class DashboardController extends Controller
{
    /** Show the overview for the chosen barangay, category and period (3 or 6 months). */
    public function __invoke(Request $request, RecurringIssues $recurring)
    {
        $months   = $request->query('months') === '3' ? 3 : 6;
        $barangay = $request->query('barangay');
        $category = $request->query('category');
        $labels   = RecurringIssues::monthLabels($months);
        $from     = now()->startOfMonth()->subMonths($months - 1);

        $concerns = Concern::filter(['barangay' => $barangay, 'category' => $category])
            ->where('submitted_at', '>=', $from)
            ->get(['id', 'barangay', 'categories', 'submitted_at', 'matched_at', 'decision']);

        // One row per barangay: total, matched, unmatched
        $rows = collect(config('concerns.barangays'))
            ->when($barangay && in_array($barangay, config('concerns.barangays'), true), fn ($c) => $c->filter(fn ($b) => $b === $barangay))
            ->map(function ($name) use ($concerns) {
                $mine = $concerns->where('barangay', $name);
                $matched = $mine->filter(fn ($c) => $c->isMatched())->count();

                return ['name' => $name, 'total' => $mine->count(), 'matched' => $matched, 'unmatched' => $mine->count() - $matched];
            })
            ->sort(fn ($a, $b) => [$b['total'], $a['name']] <=> [$a['total'], $b['name']])
            ->values();

        $total   = $rows->sum('total');
        $matched = $rows->sum('matched');

        // Chart: concerns per month, stacked by category (a concern with several categories counts in each)
        $chart = [];
        foreach (config('concerns.categories') as $key => $cat) {
            if ($category && $key !== $category) continue;

            $perMonth = array_fill_keys(array_keys($labels), 0);
            foreach ($concerns as $c) {
                if (in_array($key, $c->categoryKeys(), true)) $perMonth[$c->submitted_at->format('Y-m')]++;
            }
            $chart[] = ['key' => $key, 'label' => $cat['label'], 'data' => array_values($perMonth)];
        }

        // Map pins (only barangays that have coordinates in config/staff.php)
        $pins = [];
        foreach (config('staff.pins') as $name => $p) {
            $pins[] = ['name' => $name, 'lat' => $p['lat'], 'lng' => $p['lng'], 'primary' => $p['primary'] ?? false,
                       'total' => $rows->firstWhere('name', $name)['total'] ?? 0];
        }

        return view('staff.dashboard', [
            'months'    => $months,
            'barangay'  => $barangay,
            'category'  => $category,
            'rows'      => $rows,
            'total'     => $total,
            'matched'   => $matched,
            'pending'   => $total - $matched,
            'recurring' => $recurring->find($months, $barangay, $category)->count(),
            'flagged'   => IssueFlag::where('type', IssueFlag::REVIEW)->whereNotNull('ordinance_id')->distinct('ordinance_id')->count('ordinance_id'),
            'chart'     => $chart,
            'labels'    => array_values($labels),
            'pins'      => $pins,
        ]);
    }
}
