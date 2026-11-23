<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Concern;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class BarangayController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $status = $request->query('status');
        $barangays = Barangay::query()
            ->when($q !== '', fn ($query) => $query->where('name','like','%'.$q.'%'))
            ->when($status === 'active', fn ($query) => $query->where('is_active',true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active',false))
            ->orderBy('name')->get();

        return view('admin.barangays', compact('barangays','q','status'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:60','unique:barangays,name'],
            'latitude' => ['required','numeric','between:-90,90'],
            'longitude' => ['required','numeric','between:-180,180'],
        ]);
        $barangay = Barangay::create($data);
        AuditLogger::security('Barangay added',$barangay->name);

        return back()->with('status','Barangay added.');
    }

    public function update(Request $request, Barangay $barangay)
    {
        $data = $request->validate([
            'name' => ['required','string','max:60','unique:barangays,name,'.$barangay->id],
            'latitude' => ['required','numeric','between:-90,90'],
            'longitude' => ['required','numeric','between:-180,180'],
        ]);
        $barangay->update($data);
        AuditLogger::security('Barangay edited',$barangay->name);

        return back()->with('status','Barangay updated.');
    }

    public function toggle(Barangay $barangay)
    {
        $barangay->update(['is_active' => ! $barangay->is_active]);
        AuditLogger::security($barangay->is_active?'Barangay reactivated':'Barangay deactivated',$barangay->name);

        return back()->with('status','Barangay status updated.');
    }

    public function destroy(Barangay $barangay)
    {
        if (Concern::where('barangay',$barangay->name)->exists()) {
            return back()->withErrors(['barangays'=>'A barangay with concern history cannot be permanently deleted.']);
        }
        $name = $barangay->name;
        $barangay->delete();
        AuditLogger::security('Barangay deleted',$name);

        return back()->with('status','Barangay permanently deleted.');
    }
}
