<?php

namespace App\Http\Controllers;

use App\Models\Concern;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Everything a citizen does with concerns: dashboard, submit (2 steps), confirmation, and status tracking. */
class ConcernController extends Controller
{
    private const DRAFT = 'concern_draft';

    /** "My Dashboard": counts and the latest submissions for the signed-in citizen. */
    public function dashboard(Request $request)
    {
        $mine = Concern::where('user_id', $request->user()->id);

        $stats = [
            'total'        => (clone $mine)->count(),
            'under_review' => (clone $mine)->whereNull('reviewed_at')->whereNull('resolved_at')->count(),
            'reviewed'     => (clone $mine)->whereNotNull('reviewed_at')->whereNull('resolved_at')->count(),
            'resolved'     => (clone $mine)->whereNotNull('resolved_at')->count(),
        ];

        $recent = (clone $mine)->latest('submitted_at')->limit(5)->get();

        return view('concerns.dashboard', compact('stats', 'recent'));
    }

    /** Step 1: the form (restored from the draft when coming back from the review page). */
    public function create()
    {
        return view('concerns.create', ['draft' => session(self::DRAFT, [])]);
    }

    /** Validate the form and keep it as a draft in the session until the person confirms. */
    public function preview(Request $request)
    {
        $draft = session(self::DRAFT, []);

        $data = $request->validate([
            'barangay'     => ['required', Rule::in(config('concerns.barangays'))],
            'concern'      => ['required', 'string', 'min:10', 'max:2000'],
            'location'     => ['nullable', 'string', 'max:150'],
            'categories'   => ['nullable', 'array'],
            'categories.*' => [Rule::in(array_keys(config('concerns.categories')))],
            'photo'        => [empty($draft['photo_path']) ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ], [
            'barangay.required' => 'Please select a barangay.',
            'barangay.in'       => 'Please select a barangay from the list.',
            'concern.required'  => 'Please describe your concern (at least 10 characters).',
            'concern.min'       => 'Please describe your concern (at least 10 characters).',
            'photo.required'    => 'Please upload a JPG or PNG photo.',
            'photo.mimes'       => 'Please upload a JPG or PNG photo.',
            'photo.max'         => 'Photo must be 10 MB or smaller.',
            'photo.uploaded'    => 'The photo could not be uploaded. It must be 10 MB or smaller.',
        ]);

        $photoPath = $draft['photo_path'] ?? null;
        $photoName = $draft['photo_name'] ?? null;

        if ($request->hasFile('photo')) {
            if ($photoPath) Storage::disk('local')->delete($photoPath);

            $file      = $request->file('photo');
            $photoPath = $file->store('concern-drafts', 'local');
            $photoName = $file->getClientOriginalName();
        }

        session([self::DRAFT => [
            'barangay'   => $data['barangay'],
            'concern'    => $data['concern'],
            'location'   => $data['location'] ?? '',
            'categories' => array_values($data['categories'] ?? []),
            'photo_path' => $photoPath,
            'photo_name' => $photoName,
        ]]);

        return redirect()->route('concerns.review');
    }

    /** Step 2: review before sending. */
    public function review()
    {
        $draft = session(self::DRAFT);

        if (! $draft) return redirect()->route('concerns.create');

        return view('concerns.review', ['draft' => $draft]);
    }

    /** Save the concern and give back a reference number. */
    public function store(Request $request)
    {
        $request->validate(['agree' => ['accepted']], ['agree.accepted' => 'Please confirm the details before submitting.']);

        $draft = session(self::DRAFT);

        if (! $draft || empty($draft['photo_path']) || ! Storage::disk('local')->exists($draft['photo_path'])) {
            session()->forget(self::DRAFT);

            return redirect()->route('concerns.create')->withErrors(['photo' => 'Please upload your photo again.']);
        }

        $concern = DB::transaction(function () use ($draft, $request) {
            do {
                $ref = 'VIC-'.now()->year.'-'.random_int(100000, 999999);
            } while (Concern::where('reference', $ref)->exists());

            $ext   = pathinfo($draft['photo_path'], PATHINFO_EXTENSION) ?: 'jpg';
            $final = "concerns/{$ref}.{$ext}";
            Storage::disk('local')->move($draft['photo_path'], $final);

            return Concern::create([
                'reference'    => $ref,
                'user_id'      => $request->user()->id,
                'barangay'     => $draft['barangay'],
                'description'  => $draft['concern'],
                'location'     => $draft['location'] ?: null,
                'categories'   => $draft['categories'],
                'photo_path'   => $final,
                'photo_name'   => $draft['photo_name'],
                'submitted_at' => now(),
            ]);
        });

        session()->forget(self::DRAFT);

        AuditLogger::activity('Concern submitted', $concern->reference);

        return redirect()->route('concerns.confirmation', $concern);
    }

    /** Throw away the draft ("Clear form"). */
    public function clear()
    {
        $draft = session(self::DRAFT);

        if (! empty($draft['photo_path'])) Storage::disk('local')->delete($draft['photo_path']);

        session()->forget(self::DRAFT);

        return redirect()->route('concerns.create');
    }

    /** Show the "concern received" page with the reference number. Only the owner can open it. */
    public function confirmation(Request $request, Concern $concern)
    {
        abort_unless($concern->user_id === $request->user()->id, 404);

        return view('concerns.confirmation', compact('concern'));
    }

    /** Track a concern by reference number. People only see their own concerns. */
    public function status(Request $request)
    {
        $user = $request->user();
        $ref  = strtoupper(trim((string) $request->query('ref', '')));

        $concern = $ref === ''
            ? null
            : Concern::where('reference', $ref)->where('user_id', $user->id)->first();

        $mine = Concern::where('user_id', $user->id)->latest('submitted_at')->limit(10)->get();

        return view('concerns.status', compact('ref', 'concern', 'mine'));
    }
}
