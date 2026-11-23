<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Concern;
use App\Models\IssueFlag;
use App\Services\RecurringIssues;
use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;

/** SB member home page: community overview with stats, map, chart and a table per barangay. */
class DashboardController extends Controller
{
    /** Marker-map data for the SB dashboard. It is separate from the dashboard HTML. */
    public function mapData(Request $request): JsonResponse
    {
        $preset = $request->query('period', 'month');
        $from = match ($preset) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            'all' => now()->subYears(20),
            default => now()->startOfMonth(),
        };
        $barangay = trim((string) $request->query('barangay', ''));
        $category = trim((string) $request->query('category', ''));
        $assigned = $request->user()->assigned_categories ?: [];

        $siteQuery = \App\Models\Barangay::where('is_active', true);
        if ($barangay !== '') $siteQuery->where('name', $barangay);
        $sites = $siteQuery->orderBy('name')->get(['name', 'latitude', 'longitude'])
            ->map(fn ($site) => [
                'name' => $site->name,
                'latitude' => (float) $site->latitude,
                'longitude' => (float) $site->longitude,
            ])->values();

        $reports = Concern::whereNull('canonical_concern_id')
            ->where('submitted_at', '>=', $from)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($barangay !== '', fn ($query) => $query->where('barangay', $barangay))
            ->when($category !== '', fn ($query) => $query->whereJsonContains('categories', $category))
            ->latest('submitted_at')
            ->get(['id', 'reference', 'barangay', 'description', 'categories', 'latitude', 'longitude', 'reports_count', 'submitted_at'])
            ->filter(function ($concern) use ($assigned) {
                /** @var Concern $concern */
                if (! $assigned) return false;
                return collect($assigned)->intersect($concern->categoryKeys())->isNotEmpty();
            })
            ->map(fn ($concern) => [
                'id' => $concern->id,
                'reference' => $concern->reference,
                'barangay' => $concern->barangay,
                'title' => 'Concern '.$concern->reference,
                'detail' => \Illuminate\Support\Str::limit($concern->description, 140),
                'latitude' => (float) $concern->latitude,
                'longitude' => (float) $concern->longitude,
                'reports_count' => (int) $concern->reports_count,
                'submitted_at' => $concern->submitted_at->toIso8601String(),
                'url' => route('staff.concerns.show', $concern),
            ])->values();

        return response()->json([
            'sites' => $sites,
            'reports' => $reports,
            'cleanups' => [],
        ]);
    }

    /** Show the overview for the chosen barangay, category and period (3 or 6 months). */
    public function index(Request $request, RecurringIssues $recurring)
    {
        $preset = $request->query('period', 'month');
        $from = match ($preset) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            'all' => now()->subYears(20),
            default => now()->startOfMonth(),
        };
        $months = match ($preset) {
            'today', 'week' => 1,
            'year' => 12,
            'all' => 24,
            default => 1,
        };
        $barangay = $request->query('barangay');
        $category = $request->query('category');
        $labels   = RecurringIssues::monthLabels($months);
        $assigned = $request->user()->assigned_categories ?: [];

        $concerns = Concern::filter(['barangay' => $barangay, 'category' => $category])
            ->whereNull('canonical_concern_id')
            ->where('submitted_at', '>=', $from)
            ->get(['id','barangay','categories','submitted_at','matched_at','reviewed_at','decision','reports_count','rejection_reason']);
        if ($assigned) {
            $concerns = $concerns->filter(fn (Concern $concern) =>
                collect($assigned)->intersect($concern->categoryKeys())->isNotEmpty()
            );
        } else {
            $concerns = collect();
        }

        // One row per barangay: total, matched, unmatched
        $rows = collect(config('concerns.barangays'))
            ->when($barangay && \in_array($barangay, config('concerns.barangays'), true), fn ($c) => $c->filter(fn ($b) => $b === $barangay))
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
                if (\in_array($key, $c->categoryKeys(), true)) $perMonth[$c->submitted_at->format('Y-m')]++;
            }
            $chart[] = ['key' => $key, 'label' => $cat['label'], 'data' => array_values($perMonth)];
        }

        // Map pins (only barangays that have coordinates in config/staff.php)
        $pins = [];
        foreach (config('staff.pins') as $name => $p) {
            $pins[] = ['name' => $name, 'lat' => $p['lat'], 'lng' => $p['lng'], 'primary' => $p['primary'] ?? false,
                       'total' => $rows->firstWhere('name', $name)['total'] ?? 0];
        }

        $queue = Concern::whereNull('canonical_concern_id')
            ->where(function ($query) use ($assigned) {
                foreach ($assigned as $category) $query->orWhereJsonContains('categories', $category);
            })
            ->whereNull('reviewed_at')
            ->where(function ($query) {
                $query->whereNull('reminder_snoozed_at')->orWhere('reminder_snoozed_at', '<=', now());
            })
            ->get();
        $attention = [
            'due' => $queue->filter(fn ($c) => $c->submitted_at->diffInDays(now()) >= 5 && $c->submitted_at->diffInDays(now()) < 7)->count(),
            'overdue' => $queue->filter(fn ($c) => $c->submitted_at->diffInDays(now()) >= 7 && $c->submitted_at->diffInDays(now()) < 30)->count(),
            'critical' => $queue->filter(fn ($c) => $c->submitted_at->diffInDays(now()) >= 30)->count(),
        ];
        $gaps = collect($assigned ?: [])
            ->map(function ($category) use ($concerns) {
                $count = $concerns->filter(fn ($c) => \in_array($category, $c->categoryKeys(), true) && ! $c->isMatched())->count();
                return ['key' => $category, 'label' => config('concerns.categories.'.$category.'.label', ucfirst($category)), 'count' => $count];
            })->sortByDesc('count')->values();
        $reminders = $queue->filter(fn ($c) => $c->submitted_at->diffInDays(now()) >= 5)->sortBy('submitted_at')->take(8);
        foreach ($reminders as $concern) {
            $age = $concern->submitted_at->diffInDays(now());
            $level = $age >= 30 ? 'critical' : ($age >= 7 ? 'overdue' : 'due soon');
            Notification::firstOrCreate([
                'user_id' => $request->user()->id,
                'title' => 'Concern review reminder',
                'message' => $concern->reference.' is '.$level.' ('.$age.' days waiting).',
                'type' => 'reminder',
            ], ['link' => route('staff.concerns.show', $concern)]);
        }

        return view('staff.dashboard', [
            'months'    => $months,
            'period'    => $preset,
            'assigned'  => $assigned,
            'barangay'  => $barangay,
            'category'  => $category,
            'rows'      => $rows,
            'total'     => $total,
            'matched'   => $matched,
            'pending'   => $total - $matched,
            'recurring' => $recurring->find($months, $barangay, $category, $assigned)->count(),
            'flagged'   => IssueFlag::where('type', IssueFlag::REVIEW)->whereNotNull('ordinance_id')->distinct('ordinance_id')->count('ordinance_id'),
            'chart'     => $chart,
            'labels'    => array_values($labels),
            'pins'      => $pins,
            'attention' => $attention,
            'gaps'      => $gaps,
            'reminders' => $reminders,
        ]);
    }
}
