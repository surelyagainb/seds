<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/** The admin home page: account details, role, and the latest activity. */
class OverviewController extends Controller
{
    /** Show the overview (runs when someone opens /admin). */
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $tab = $request->query('tab') === 'history' ? 'history' : 'audit';

        $activity = $user->allows('view_audit')
            ? AuditLog::where('type', $tab)->latest()->latest('id')->limit(2)->get()
            : collect();

        return view('admin.overview', compact('user', 'tab', 'activity'));
    }
}
