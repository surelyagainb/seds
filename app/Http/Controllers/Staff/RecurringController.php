<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\IssueFlag;
use App\Models\Ordinance;
use App\Services\AuditLogger;
use App\Services\RecurringIssues;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Problems that keep coming back, even where an ordinance already exists. */
class RecurringController extends Controller
{
    /** The list of recurring issues, the summary, a chart, and details of the selected issue. */
    public function index(Request $request, RecurringIssues $recurring)
    {
        $months   = $request->query('months') === '3' ? 3 : 6;
        $barangay = $request->query('barangay');
        $category = $request->query('category');

        $issues = $recurring->find($months, $barangay, $category);

        // Selected issue: ?issue=Barangay|category, else the first one
        $selected = $issues->firstWhere('key', $request->query('issue')) ?? $issues->first();

        $ordinances = Ordinance::whereIn('id', $issues->pluck('ordinance_id')->filter()->unique())->get()->keyBy('id');

        $flags = $selected
            ? IssueFlag::with('user')->where('barangay', $selected['barangay'])->where('category', $selected['category'])->get()->keyBy('type')
            : collect();

        return view('staff.recurring', [
            'issues'     => $issues,
            'selected'   => $selected,
            'ordinances' => $ordinances,
            'flags'      => $flags,
            'labels'     => array_values(RecurringIssues::monthLabels($months)),
            'chart'      => collect(range(0, $months - 1))->map(fn ($i) => $issues->sum(fn ($r) => $r['months'][$i]))->all(),
            'reports'    => $issues->sum('times'),
            'months'     => $months,
            'barangay'   => $barangay,
            'category'   => $category,
        ]);
    }

    /** Flag an issue: "review" (an ordinance exists but the problem persists) or "new_ordinance". */
    public function flag(Request $request)
    {
        $data = $request->validate([
            'type'         => ['required', Rule::in([IssueFlag::REVIEW, IssueFlag::NEW_ORDINANCE])],
            'barangay'     => ['required', Rule::in(config('concerns.barangays'))],
            'category'     => ['required', Rule::in(array_keys(config('concerns.categories')))],
            'ordinance_id' => ['nullable', 'exists:ordinances,id'],
        ]);

        IssueFlag::updateOrCreate(
            ['type' => $data['type'], 'barangay' => $data['barangay'], 'category' => $data['category']],
            ['ordinance_id' => $data['ordinance_id'] ?? null, 'user_id' => $request->user()->id]
        );

        AuditLogger::activity(
            $data['type'] === IssueFlag::REVIEW ? 'Recurring issue flagged for review' : 'Recurring issue flagged for a new ordinance',
            $data['barangay'].' · '.(config('concerns.categories')[$data['category']]['label'])
        );

        return back()->with('status', $data['type'] === IssueFlag::REVIEW ? 'Flagged for review.' : 'Noted as a possible new ordinance.');
    }
}
