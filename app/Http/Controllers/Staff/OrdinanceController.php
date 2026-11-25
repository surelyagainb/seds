<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Concern;
use App\Models\Ordinance;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

/** The ordinance register: list, add a new ordinance, and view one. */
class OrdinanceController extends Controller
{
    /** The register with search (number or title), category and status filters. */
    public function index(Request $request)
    {
        $q        = trim((string) $request->query('q', ''));
        $category = $request->query('category');
        $status   = $request->query('status');

        $ordinances = Ordinance::query()
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $w->where(fn ($x) => $x->where('number', 'like', $like)->orWhere('title', 'like', $like));
            })
            ->when($category && array_key_exists($category, config('concerns.categories')), fn ($w) => $w->where('category', $category))
            ->when($status && in_array($status, Ordinance::STATUSES, true), fn ($w) => $w->where('status', $status))
            ->orderBy('number')->get();

        return view('staff.ordinances.index', [
            'ordinances' => $ordinances,
            'all'        => Ordinance::count(),
            'keywords'   => Ordinance::pluck('keywords')->flatten()->unique()->sort()->values(),
            'filters'    => ['q' => $q, 'category' => $category, 'status' => $status],
        ]);
    }

    /** Save a new ordinance. Keywords are cleaned (letters, numbers, spaces and hyphens only). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'number'   => ['required', 'string', 'max:40', 'unique:ordinances,number'],
            'title'    => ['required', 'string', 'max:200'],
            'category' => ['required', Rule::in(array_keys(config('concerns.categories')))],
            'status'   => ['required', Rule::in(Ordinance::STATUSES)],
            'keywords' => ['required', 'string', 'max:500'],
            'text'     => ['nullable', 'string', 'max:10000'],
            'file'     => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'number.required'   => 'Please fill in the ordinance number.',
            'number.unique'     => 'An ordinance with this number already exists.',
            'title.required'    => 'Please fill in the title.',
            'keywords.required' => 'Please add at least one keyword.',
        ]);

        $keywords = collect(explode(',', $data['keywords']))
            ->map(fn ($k) => trim(preg_replace('/[^a-z0-9 -]/', '', mb_strtolower($k))))
            ->filter()->unique()->values()->all();

        if (! $keywords) {
            return back()->withInput()->withErrors(['keywords' => 'Please add at least one keyword (letters and numbers only).']);
        }

        $filePath = null;
        $fileName = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store('ordinance-files', 'local');
        }

        $ordinance = Ordinance::create([
            'number'   => $data['number'],
            'title'    => $data['title'],
            'category' => $data['category'],
            'status'   => $data['status'],
            'version'  => 'v1',
            'keywords' => $keywords,
            'sections' => [['n' => 1, 'title' => 'Main provisions', 'text' => trim($data['text'] ?? '') ?: 'No text added yet.']],
            'file_path' => $filePath,
            'file_name' => $fileName,
        ]);

        AuditLogger::activity('Ordinance added', $ordinance->number.' · '.$ordinance->title);

        return redirect()->route('staff.ordinances.show', $ordinance)
            ->with('status', 'Ordinance saved. New concerns will now be matched against it.');
    }

    /** One ordinance: text, record details, keywords and the concerns that match it. */
    public function show(Ordinance $ordinance)
    {
        $linked = Concern::whereHas('matches', fn ($w) => $w->where('ordinance_id', $ordinance->id))
            ->latest('submitted_at');

        return view('staff.ordinances.show', [
            'ordinance'   => $ordinance,
            'linkedCount' => (clone $linked)->count(),
            'linked'      => (clone $linked)->limit(5)->get(['reference', 'barangay']),
        ]);
    }
}
