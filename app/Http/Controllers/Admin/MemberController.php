<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    /** Check accounts (read-only: no role changes, no new accounts). */
    public function index()
    {
        return view('admin.members', [
            'members' => User::orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }

    /** Remove an account. Administrator accounts (including your own) cannot be removed here. */
    public function destroy(Request $request, User $member)
    {
        if ($member->isAdmin()) {
            return back()->withErrors(['members' => 'Administrator accounts cannot be removed.']);
        }

        $request->validateWithBag('remove', [
            'password' => ['required', 'current_password'],
        ], [
            'password.current_password' => 'Your password is incorrect.',
            'password.required'         => 'Enter your password to confirm.',
        ]);

        $label = $member->name.' · '.$member->email.' · '.$member->roleLabel();
        $member->delete();

        AuditLogger::security('Account removed', $label);

        return back()->with('status', 'The account was removed. Its history was kept.');
    }
}
