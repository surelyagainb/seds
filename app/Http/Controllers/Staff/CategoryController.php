<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ConcernCategory;
use App\Models\User;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $status = $request->query('status');
        $categories = ConcernCategory::query()
            ->when($q !== '', fn ($query) => $query->where('name','like','%'.$q.'%')->orWhere('key','like','%'.$q.'%'))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')->get();
        $members = User::where('role','sb_member')->where('is_active',true)->orderBy('name')->get();

        return view('staff.categories', compact('categories','members','q','status'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'key' => ['required','string','max:32','alpha_dash','unique:concern_categories,key'],
            'color' => ['nullable','string','max:20'],
            'keywords_en' => ['nullable','string','max:500'],
            'keywords_fil' => ['nullable','string','max:500'],
        ]);
        ConcernCategory::create([
            'key' => strtolower($data['key']),
            'name' => $data['name'],
            'color' => $data['color'] ?: '#64748b',
            'keywords_en' => $this->keywords($data['keywords_en'] ?? ''),
            'keywords_fil' => $this->keywords($data['keywords_fil'] ?? ''),
            'is_active' => true,
        ]);

        return back()->with('status','Category added.');
    }

    public function update(Request $request, ConcernCategory $category)
    {
        if ($category->key === 'other') {
            $category->update(['keywords_en' => [], 'keywords_fil' => []]);
        }
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'color' => ['nullable','string','max:20'],
            'keywords_en' => ['nullable','string','max:500'],
            'keywords_fil' => ['nullable','string','max:500'],
        ]);
        $category->update([
            'name' => $data['name'],
            'color' => $data['color'] ?: $category->color,
            'keywords_en' => $category->key === 'other' ? [] : $this->keywords($data['keywords_en'] ?? ''),
            'keywords_fil' => $category->key === 'other' ? [] : $this->keywords($data['keywords_fil'] ?? ''),
        ]);

        return back()->with('status','Category updated.');
    }

    public function assign(Request $request, ConcernCategory $category)
    {
        $memberIds = collect($request->input('member_ids', []))->map(fn ($id) => (int) $id);
        foreach (User::whereIn('id', $memberIds)->get() as $member) {
            $assigned = collect($member->assigned_categories ?: []);
            $member->forceFill([
                'assigned_categories' => $assigned->push($category->key)->unique()->values()->all(),
            ])->save();
        }
        foreach (User::where('role','sb_member')->whereNotIn('id',$memberIds)->get() as $member) {
            $member->forceFill([
                'assigned_categories' => collect($member->assigned_categories ?: [])
                    ->reject(fn ($key) => $key === $category->key)->values()->all(),
            ])->save();
        }

        return back()->with('status','Category assignments updated.');
    }

    public function toggle(ConcernCategory $category)
    {
        if ($category->key === 'other') {
            return back()->withErrors(['category' => 'The Others category cannot be deactivated.']);
        }
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('status','Category status updated.');
    }

    protected function keywords(string $text): array
    {
        return collect(preg_split('/[,\\n]+/', $text))
            ->map(fn ($value) => mb_strtolower(trim($value)))
            ->filter()->unique()->values()->all();
    }
}
