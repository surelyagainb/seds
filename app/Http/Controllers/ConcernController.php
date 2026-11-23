<?php

namespace App\Http\Controllers;

use App\Models\Concern;
use App\Models\Ordinance;
use App\Models\Barangay;
use App\Services\AuditLogger;
use App\Services\OrdinanceMatcher;
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

        // Search and filters from the page address (?q=...&category=...&status=...&barangay=...)
        $filters  = $request->only(['q', 'category', 'status', 'barangay']);
        $filtered = collect($filters)->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty();

        $recent = (clone $mine)->filter($filters)->latest('submitted_at')->paginate(10)->withQueryString();
        $photoUsedBytes = (clone $mine)->sum('photo_size');
        $photoUsedMb = round($photoUsedBytes / 1048576, 1);

        return view('concerns.dashboard', compact('stats', 'recent', 'filters', 'filtered', 'photoUsedMb'));
    }

    /** Step 1: the form (restored from the draft when coming back from the review page). */
    public function create()
    {
        $barangays = Barangay::where('is_active',true)->orderBy('name')->pluck('name');
        return view('concerns.create', ['draft' => session(self::DRAFT, []), 'barangays' => $barangays]);
    }

    /** Validate the form and keep it as a draft in the session until the person confirms. */
    public function preview(Request $request)
    {
        $draft = session(self::DRAFT, []);

        $data = $request->validate([
            'barangay'     => ['required', Rule::in(Barangay::where('is_active',true)->pluck('name')->all())],
            'concern'      => ['required', 'string', 'min:10', 'max:2000'],
            'location'     => ['nullable', 'string', 'max:150'],
            'latitude'     => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'    => ['nullable', 'numeric', 'between:-180,180'],
            'categories'   => ['nullable', 'array'],
            'categories.*' => [Rule::in(array_keys(config('concerns.categories')))],
            'photo'        => [empty($draft['photo_path']) ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ], [
            'barangay.required' => 'Please select a barangay.',
            'barangay.in'       => 'Please select a barangay from the list.',
            'concern.required'  => 'Please describe your concern (at least 10 characters).',
            'concern.min'       => 'Please describe your concern (at least 10 characters).',
            'photo.required'    => 'Please upload a JPG or PNG photo.',
            'photo.mimes'       => 'Please upload a JPG or PNG photo.',
            'photo.max'         => 'Photo must be 5 MB or smaller.',
            'photo.uploaded'    => 'The photo could not be uploaded. It must be 5 MB or smaller.',
        ]);

        $photoPath = $draft['photo_path'] ?? null;
        $photoName = $draft['photo_name'] ?? null;

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $originalName = $file->getClientOriginalName();
            if (preg_match('/\.[^\/.]+\.[^\/.]+$/i', $originalName)) {
                return back()->withInput()->withErrors(['photo' => 'Blocked: double file extensions are not allowed.']);
            }
            $handle = fopen($file->getRealPath(), 'rb');
            $signature = $handle ? fread($handle, 16) : '';
            if ($handle) fclose($handle);
            $isJpeg = str_starts_with($signature, "\xFF\xD8\xFF");
            $isPng = str_starts_with($signature, "\x89PNG\r\n\x1A\n");
            if (! $isJpeg && ! $isPng) {
                return back()->withInput()->withErrors(['photo' => 'Blocked: the file signature does not match a JPG or PNG image.']);
            }
            if (str_contains(strtolower(file_get_contents($file->getRealPath())), 'eicar')) {
                return back()->withInput()->withErrors(['photo' => 'Blocked: infected test file detected (EICAR).']);
            }

            $used = Concern::where('user_id', $request->user()->id)->sum('photo_size');
            if ($used + $file->getSize() > 20 * 1024 * 1024) {
                return back()->withInput()->withErrors(['photo' => 'Storage limit reached: 20 MB is the maximum per citizen account.']);
            }
            if ($photoPath) Storage::disk('local')->delete($photoPath);
            $photoPath = $file->store('concern-drafts', 'local');
            $photoName = $originalName;
            $photoSize = $file->getSize();
        }

        session([self::DRAFT => [
            'barangay'   => $data['barangay'],
            'concern'    => $data['concern'],
            'location'   => $data['location'] ?? '',
            'latitude'   => isset($data['latitude']) ? round((float) $data['latitude'], 4) : null,
            'longitude'  => isset($data['longitude']) ? round((float) $data['longitude'], 4) : null,
            'categories' => array_values($data['categories'] ?? []),
            'photo_path' => $photoPath,
            'photo_name' => $photoName,
            'photo_size' => $photoSize ?? ($draft['photo_size'] ?? 0),
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

    /** Serve the uploaded photo from the current draft only to the signed-in citizen. */
    public function draftPhoto()
    {
        $draft = session(self::DRAFT);

        if (! $draft || empty($draft['photo_path']) || ! Storage::disk('local')->exists($draft['photo_path'])) {
            abort(404);
        }

        $path = Storage::disk('local')->path($draft['photo_path']);

        return response()->file($path, [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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

        $duplicate = $this->findLikelyDuplicate($draft);
        if ($duplicate && ! $request->boolean('duplicate_confirmed') && ! $request->boolean('duplicate_different')) {
            session(['duplicate_concern_id' => $duplicate->id]);
            return view('concerns.duplicate', ['concern' => $duplicate, 'draft' => $draft]);
        }
        if ($request->boolean('duplicate_confirmed') && $request->filled('duplicate_concern_id')) {
            $canonical = Concern::find($request->input('duplicate_concern_id'));
            if ($canonical) {
                $canonical->increment('reports_count');
                DB::table('concern_reports')->insertOrIgnore([
                    'user_id' => $request->user()->id,
                    'concern_id' => $canonical->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                session()->forget(self::DRAFT);
                if (! empty($draft['photo_path'])) Storage::disk('local')->delete($draft['photo_path']);
                AuditLogger::activity('Duplicate concern linked', $canonical->reference);
                return redirect()->route('concerns.status', ['ref' => $canonical->reference])
                    ->with('status', 'Your report was linked to the existing concern.');
            }
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
                'latitude'     => $draft['latitude'] ?? null,
                'longitude'    => $draft['longitude'] ?? null,
                'categories'   => $draft['categories'],
                'photo_path'   => $final,
                'photo_name'   => $draft['photo_name'],
                'photo_size'   => $draft['photo_size'] ?? 0,
                'submitted_at' => now(),
            ]);
        });

        session()->forget(self::DRAFT);

        AuditLogger::activity('Concern submitted', $concern->reference);

        // Analyze the concern and look for ordinances right away. If this fails, the concern is still saved.
        try {
            app(OrdinanceMatcher::class)->matchAndSave($concern);
        } catch (\Throwable $e) {
            report($e);
        }

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

    public function ordinances(Request $request)
    {
        $query = Ordinance::where('status', '!=', 'Draft');
        $q = trim((string) $request->query('q', ''));
        $category = $request->query('category');
        if ($q !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(function ($w) use ($like) {
                $w->where('number', 'like', $like)->orWhere('title', 'like', $like)->orWhere('summary', 'like', $like);
            });
        }
        if ($category && array_key_exists($category, config('concerns.categories'))) {
            $query->where('category', $category);
        }
        $ordinances = $query->orderBy('number')->paginate(12)->withQueryString();

        return view('concerns.ordinances', [
            'ordinances' => $ordinances,
            'filters' => ['q' => $q, 'category' => $category],
        ]);
    }

    public function ordinance(Ordinance $ordinance)
    {
        return view('concerns.ordinance', compact('ordinance'));
    }

    /** Track a concern by reference number. People only see their own concerns. */
    public function status(Request $request)
    {
        $user = $request->user();
        $ref  = strtoupper(trim((string) $request->query('ref', '')));

        $concern = $ref === ''
            ? null
            : Concern::where('reference', $ref)
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhereExists(function ($exists) use ($user) {
                            $exists->select(DB::raw(1))->from('concern_reports')
                                ->whereColumn('concern_reports.concern_id', 'concerns.id')
                                ->where('concern_reports.user_id', $user->id);
                        });
                })->first();

        $mine = Concern::where('user_id', $user->id)
            ->orWhereIn('id', DB::table('concern_reports')->where('user_id', $user->id)->pluck('concern_id'))
            ->latest('submitted_at')->limit(10)->get();

        return view('concerns.status', compact('ref', 'concern', 'mine'));
    }

    /** Delete a photo attached to one of the citizen's concerns, freeing storage. */
    public function deletePhoto(Request $request, Concern $concern)
    {
        abort_unless($concern->user_id === $request->user()->id, 404);
        if ($concern->photo_path) Storage::disk('local')->delete($concern->photo_path);
        $concern->forceFill(['photo_path' => '', 'photo_name' => '', 'photo_size' => 0])->save();

        return back()->with('status', 'Photo deleted and storage space released.');
    }

    protected function findLikelyDuplicate(array $draft): ?Concern
    {
        $candidates = Concern::query()
            ->whereNull('canonical_concern_id')
            ->where('barangay', $draft['barangay'])
            ->where('submitted_at', '>=', now()->subDays(14))
            ->where(function ($query) use ($draft) {
                $query->whereNull('decision')->orWhere('decision', '!=', 'rejected');
            })->get();

        $categories = collect($draft['categories'] ?? []);
        foreach ($candidates as $candidate) {
            $categorySimilar = $categories->intersect($candidate->categories ?: [])->isNotEmpty();
            $textSimilarity = similar_text(mb_strtolower($draft['concern']), mb_strtolower($candidate->description), $percent);
            if (! $categorySimilar && $percent < 55) continue;

            if (! empty($draft['latitude']) && $candidate->latitude) {
                $distance = $this->distanceMeters(
                    (float) $draft['latitude'], (float) $draft['longitude'],
                    (float) $candidate->latitude, (float) $candidate->longitude
                );
                if ($distance <= 150) return $candidate;
            } elseif ($categorySimilar) {
                return $candidate;
            }
        }

        return null;
    }

    protected function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
