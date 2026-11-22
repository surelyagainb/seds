<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Concern;
use App\Models\Ordinance;
use App\Services\RecurringIssues;
use Illuminate\Http\Request;

/** Barangay directory and the detail page of one barangay. */
class BarangayController extends Controller
{
    /** All barangays with their concern counts (all time). Filter by name, or show only some. */
    public function index(Request $request)
    {
        $q    = trim((string) $request->query('q', ''));
        $show = $request->query('show');

        $concerns = Concern::get(['id', 'barangay', 'matched_at', 'decision']);

        $all = collect(config('concerns.barangays'))->map(function ($name) use ($concerns) {
            $mine    = $concerns->where('barangay', $name);
            $matched = $mine->filter(fn ($c) => $c->isMatched())->count();

            return ['name' => $name, 'total' => $mine->count(), 'matched' => $matched, 'unmatched' => $mine->count() - $matched];
        })->sort(fn ($a, $b) => [$b['total'], $a['name']] <=> [$a['total'], $b['name']])->values();

        $rows = $all->when($q !== '', fn ($c) => $c->filter(fn ($b) => str_contains(mb_strtolower($b['name']), mb_strtolower($q))));
        if ($show === 'un')  $rows = $rows->filter(fn ($b) => $b['unmatched'] >= 5);
        if ($show === 'top') $rows = $rows->take(10);

        return view('staff.barangays.index', ['rows' => $rows->values(), 'all' => $all, 'q' => $q, 'show' => $show]);
    }

    /** One barangay: overview, its concerns (with a status filter) and the ordinances matched there. */
    public function show(Request $request, string $barangay, RecurringIssues $recurring)
    {
        abort_unless(in_array($barangay, config('concerns.barangays'), true), 404);

        $mine    = Concern::where('barangay', $barangay);
        $total   = (clone $mine)->count();
        $matched = (clone $mine)->whereNotNull('matched_at')->where(fn ($w) => $w->whereNull('decision')->orWhere('decision', '!=', 'unmatched'))->count();
        $allTotal = Concern::count();

        // "By primary category": the first category of each concern
        $primary = [];
        foreach ((clone $mine)->get(['categories']) as $c) {
            $k = $c->categoryKeys()[0];
            $primary[$k] = ($primary[$k] ?? 0) + 1;
        }

        // Ordinances matched here: count each concern once, under its best ordinance
        $counts = (clone $mine)->with('matches:id,concern_id,ordinance_id,score')->get(['id', 'matched_ordinance_id'])
            ->map(fn ($c) => $c->bestOrdinanceId())->filter()->countBy()->sortDesc();
        $ordinances = Ordinance::whereIn('id', $counts->keys())->get()->keyBy('id');

        $status = $request->query('status');

        return view('staff.barangays.show', [
            'name'        => $barangay,
            'total'       => $total,
            'matched'     => $matched,
            'unmatched'   => $total - $matched,
            'share'       => $allTotal ? round($total / $allTotal * 100, 1) : 0,
            'recurring'   => $recurring->find(6, $barangay)->count(),
            'primary'     => $primary,
            'overview'    => (clone $mine)->latest('submitted_at')->paginate(5, ['*'], 'page')->withQueryString(),
            'concerns'    => (clone $mine)->staffStatus($status)->latest('submitted_at')->paginate(15, ['*'], 'cpage')->withQueryString()->appends(['tab' => 'co']),
            'status'      => $status,
            'counts'      => $counts,
            'ordinances'  => $ordinances,
            'tab'         => in_array($request->query('tab'), ['ov', 'co', 'or'], true) ? $request->query('tab') : 'ov',
        ]);
    }
}
