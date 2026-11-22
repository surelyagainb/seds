<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Concern;
use App\Models\Ordinance;
use App\Services\AuditLogger;
use App\Services\OrdinanceMatcher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** SB members reviewing concerns: list, detail with candidate ordinances, and their decisions. */
class ConcernController extends Controller
{
    /** All concerns, with search, status, barangay and category filters. 15 per page. */
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'category', 'barangay']);
        $status  = $request->query('status');

        $concerns = Concern::filter($filters)->staffStatus($status)
            ->latest('submitted_at')->paginate(15)->withQueryString();

        return view('staff.concerns.index', [
            'concerns' => $concerns,
            'filters'  => $request->only(['q', 'category', 'barangay', 'status']),
            'total'    => Concern::count(),
        ]);
    }

    /**
     * One concern. The first time staff open it, it is analyzed (if needed) and the view is logged,
     * which also moves the citizen's tracker forward. Shows the candidate ordinances,
     * or the "no ordinance found" page when there are none.
     */
    public function show(Request $request, Concern $concern, OrdinanceMatcher $matcher)
    {
        if (! $concern->viewed_at) {
            if (! $concern->analyzed_at) $matcher->matchAndSave($concern);

            $concern->viewed_at = now();
            $concern->save();
            $concern->log('Viewed by staff');
        }

        $concern->load(['matches.ordinance', 'matchedOrdinance', 'logs.user']);
        $candidates = $concern->matches->sortByDesc('score')->values();

        // No suggestions: show the page for manual linking
        if ($candidates->isEmpty()) {
            return view('staff.concerns.nomatch', [
                'concern'   => $concern,
                'ordinances' => Ordinance::orderBy('title')->get(['id', 'title', 'number']),
            ]);
        }

        // For each suggestion, which keywords were found (used for highlighting and the explanation)
        $text  = trim($concern->description.' '.$concern->location);
        $rules = config('matching');
        $cands = $candidates->map(fn ($m) => [
            'match' => $m,
            'hits'  => OrdinanceMatcher::score($concern->categories ?: [], $text, $m->ordinance->category, $m->ordinance->keywords ?? [], $rules)['keywords'],
        ]);

        // Selected candidate: ?o=ID, else the one staff chose, else the best one
        $wanted   = (int) $request->query('o', $concern->matched_ordinance_id);
        $selected = $cands->first(fn ($c) => $c['match']->ordinance_id === $wanted) ?? $cands->first();

        return view('staff.concerns.show', [
            'concern'  => $concern,
            'cands'    => $cands,
            'selected' => $selected,
            'section'  => $selected['match']->ordinance->bestSection($selected['hits']),
            'offices'  => $this->offices($concern),
        ]);
    }

    /** Confirm or reject the selected ordinance match. Staff can change their mind later. */
    public function decide(Request $request, Concern $concern)
    {
        $data = $request->validate([
            'action'       => ['required', Rule::in(['confirm', 'reject'])],
            'ordinance_id' => ['required', 'exists:ordinances,id'],
            'note'         => ['nullable', 'string', 'max:500'],
        ]);

        $ordinance = Ordinance::findOrFail($data['ordinance_id']);
        $confirm   = $data['action'] === 'confirm';
        $note      = trim($data['note'] ?? '');

        $concern->forceFill([
            'decision'             => $confirm ? 'confirmed' : 'rejected',
            'matched_ordinance_id' => $ordinance->id,
            'reviewed_at'          => now(),
            'reviewed_by'          => $request->user()->id,
        ])->save();

        $concern->log(($confirm ? 'Match confirmed: ' : 'Match rejected: ').$ordinance->title.'.'.($note !== '' ? ' Note: '.$note : ''));
        AuditLogger::activity($confirm ? 'Match confirmed' : 'Match rejected', $concern->reference.' · '.$ordinance->title);

        return back()->with('status', $confirm ? "Match confirmed. The citizen's tracker now shows Reviewed." : 'Match rejected.');
    }

    /** Send the concern to another office, with a reason (shown in the activity log). */
    public function reassign(Request $request, Concern $concern)
    {
        $data = $request->validate([
            'assigned_to' => ['required', Rule::in($this->offices($concern))],
            'reason'      => ['required', 'string', 'min:5', 'max:500'],
        ], ['reason.min' => 'Please write a short reason (at least 5 characters).', 'reason.required' => 'Please write a short reason (at least 5 characters).']);

        $concern->forceFill(['assigned_to' => $data['assigned_to']])->save();
        $concern->log('Reassigned to '.$data['assigned_to'].'. Reason: '.trim($data['reason']));
        AuditLogger::activity('Concern reassigned', $concern->reference.' · '.$data['assigned_to']);

        return back()->with('status', 'Concern reassigned.');
    }

    /** No-match page: link an ordinance by hand. */
    public function link(Request $request, Concern $concern)
    {
        $data = $request->validate([
            'ordinance_id' => ['required', 'exists:ordinances,id'],
            'reason'       => ['nullable', 'string', 'max:500'],
        ]);

        $ordinance = Ordinance::findOrFail($data['ordinance_id']);
        $reason    = trim($data['reason'] ?? '');

        $concern->forceFill([
            'decision'             => 'confirmed',
            'matched_ordinance_id' => $ordinance->id,
            'reviewed_at'          => now(),
            'reviewed_by'          => $request->user()->id,
        ])->save();

        $concern->log('Manually linked to '.$ordinance->title.'.'.($reason !== '' ? ' Reason: '.$reason : ''));
        AuditLogger::activity('Ordinance linked manually', $concern->reference.' · '.$ordinance->title);

        return back()->with('status', 'Ordinance linked.');
    }

    /** No-match page: mark the concern as unmatched (it counts as reviewed). */
    public function unmatched(Request $request, Concern $concern)
    {
        $concern->forceFill(['decision' => 'unmatched', 'reviewed_at' => now(), 'reviewed_by' => $request->user()->id])->save();

        $concern->log('Marked as unmatched');
        AuditLogger::activity('Concern marked unmatched', $concern->reference);

        return back()->with('status', "Marked as unmatched. The citizen's tracker now shows Reviewed.");
    }

    /** No-match page: flag the concern as a possible new ordinance for the Sangguniang Bayan. */
    public function suggestNew(Concern $concern)
    {
        $concern->forceFill(['suggest_new' => true])->save();

        $concern->log('Flagged as a suggestion for a new ordinance');
        AuditLogger::activity('Flagged for a new ordinance', $concern->reference);

        return back()->with('status', 'Flagged as a possible new ordinance.');
    }

    /** The offices a concern can be sent to (includes the barangay captain of its barangay). */
    private function offices(Concern $concern): array
    {
        return array_merge(config('staff.offices'), ['Barangay Captain of '.$concern->barangay]);
    }
}
