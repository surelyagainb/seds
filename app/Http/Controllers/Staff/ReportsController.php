<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Concern;
use App\Models\ConcernCategory;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period', 'month');
        $from = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            'all' => now()->subYears(20),
            default => now()->startOfMonth(),
        };
        $totalAll = Concern::whereNull('canonical_concern_id')->where('submitted_at','>=',$from)->count();
        $rows = ConcernCategory::where('is_active',true)->orderBy('name')->get()->map(function ($category) use ($from, $totalAll) {
            $concerns = Concern::whereNull('canonical_concern_id')->where('submitted_at','>=',$from)
                ->whereJsonContains('categories', $category->key);
            $total = (clone $concerns)->count();
            $matched = (clone $concerns)->whereNotNull('matched_at')->where(function ($query) {
                $query->whereNull('decision')->orWhere('decision','confirmed');
            })->count();
            $unmatched = (clone $concerns)->where(function ($query) {
                $query->whereNull('analyzed_at')->orWhereNull('matched_at')->orWhere('decision','unmatched');
            })->count();
            $reviewed = (clone $concerns)->whereNotNull('reviewed_at')->count();
            $rejected = (clone $concerns)->where('decision','rejected')->count();
            return [
                'key' => $category->key,
                'name' => $category->name,
                'total' => $total,
                'matched' => $matched,
                'unmatched' => $unmatched,
                'reviewed' => $reviewed,
                'rejected' => $rejected,
                'percent' => $totalAll ? round($total/$totalAll*100,1) : 0,
            ];
        });

        return view('staff.reports', [
            'rows' => $rows,
            'period' => $period,
            'totalAll' => $totalAll,
            'totals' => [
                'total' => $rows->sum('total'),
                'matched' => $rows->sum('matched'),
                'unmatched' => $rows->sum('unmatched'),
                'reviewed' => $rows->sum('reviewed'),
                'rejected' => $rows->sum('rejected'),
            ],
        ]);
    }
}
