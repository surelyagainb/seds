<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use App\Support\TemporaryPasswordGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/** Admin page for checking and removing accounts. */
class MemberController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'username' => ['required','string','alpha_dash','max:60','unique:users,username'],
            'email' => ['required','email','max:190','unique:users,email'],
            'mobile' => ['nullable','string','max:32','unique:users,mobile'],
            'role' => ['required',Rule::in(['citizen','sb_member','admin'])],
            'assigned_categories' => ['array'],
            'assigned_categories.*' => [Rule::in(array_keys(config('concerns.categories'))) ],
        ]);
        $temporary = TemporaryPasswordGenerator::generate();
        $member = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'password' => Hash::make($temporary),
            'role' => $data['role'],
            'is_active' => true,
            'status' => 'active',
            'assigned_categories' => $data['assigned_categories'] ?? [],
            'password_changed_at' => now(),
            'verified_at' => now(),
        ]);
        AuditLogger::security('Account created', $member->username.' · '.$member->roleLabel());

        return back()->with('status','Account created.')->with('temporary_password',$temporary);
    }

    public function update(Request $request, User $member)
    {
        if ($member->isAdmin() && $member->id === $request->user()->id && $request->input('role') !== 'admin') {
            return back()->withErrors(['members'=>'You cannot change your own administrator role.']);
        }
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'username' => ['required','string','alpha_dash','max:60','unique:users,username,'.$member->id],
            'email' => ['required','email','max:190','unique:users,email,'.$member->id],
            'mobile' => ['nullable','string','max:32','unique:users,mobile,'.$member->id],
            'role' => ['required',Rule::in(['citizen','sb_member','admin'])],
            'assigned_categories' => ['array'],
            'assigned_categories.*' => [Rule::in(array_keys(config('concerns.categories'))) ],
        ]);
        $member->forceFill([
            'name'=>$data['name'],'username'=>$data['username'],'email'=>$data['email'] ?? null,
            'mobile'=>$data['mobile'] ?? null,'role'=>$data['role'],
            'assigned_categories'=>$data['assigned_categories'] ?? [],
        ])->save();
        AuditLogger::security('Account edited',$member->username.' · '.$member->roleLabel());

        return back()->with('status','Account updated.');
    }

    public function toggle(Request $request, User $member)
    {
        if ($member->isAdmin() && $member->id === $request->user()->id) {
            return back()->withErrors(['members'=>'You cannot deactivate or reactivate your own administrator account.']);
        }
        $deactivate = $member->is_active;
        $member->forceFill([
            'is_active' => ! $deactivate,
            'status' => $deactivate ? 'deactivated' : 'active',
        ])->save();
        AuditLogger::security($deactivate?'Account deactivated':'Account reactivated',$member->username);

        return back()->with('status',$deactivate?'Account deactivated.':'Account reactivated.');
    }

    public function resetPassword(Request $request, User $member)
    {
        $request->validate([
            'confirm_reset' => ['required','accepted'],
        ], ['confirm_reset.accepted'=>'Confirm the password reset before continuing.']);
        $temporary = TemporaryPasswordGenerator::generate();
        $member->forceFill([
            'password' => Hash::make($temporary),
            'password_changed_at' => now(),
        ])->save();
        AuditLogger::security('Temporary password set',$member->username);

        return back()->with('status','Temporary password created. It is shown once.')->with('temporary_password',$temporary);
    }

    /** Check accounts (read-only: no role changes, no new accounts). */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $role = $request->query('role');
        $status = $request->query('status');
        $members = User::query()
            ->when($q !== '', fn ($query) => $query->where(function ($where) use ($q) {
                $like = '%'.$q.'%';
                $where->where('name','like',$like)->orWhere('username','like',$like)->orWhere('email','like',$like);
            }))
            ->when($role && in_array($role,['citizen','sb_member','admin'],true), fn ($query) => $query->where('role',$role))
            ->when($status && in_array($status,['active','pending','deactivated'],true), fn ($query) => $query->where('status',$status))
            ->orderByDesc('is_active')->orderBy('name')->get();
        return view('admin.members', [
            'members' => $members,
            'filters' => ['q'=>$q,'role'=>$role,'status'=>$status],
            'activity' => AuditLog::latest()->limit(10)->get(),
        ]);
    }

    /** Remove an account. Administrator accounts (including your own) cannot be removed here. */
    public function destroy(Request $request, User $member)
    {
        if ($member->isAdmin()) {
            return back()->withErrors(['members' => 'Administrator accounts cannot be deleted.']);
        }
        if ($member->is_active) {
            return back()->withErrors(['members'=>'Deactivate this account before it can be permanently deleted.']);
        }
        $request->validateWithBag('remove', [
            'username_confirm' => ['required','in:'.$member->username],
        ], ['username_confirm.in'=>'The username you typed does not match.']);

        $label = $member->name.' · '.$member->email.' · '.$member->roleLabel();
        $member->delete();

        AuditLogger::security('Account removed', $label);

        return back()->with('status', 'The account was removed. Its history was kept.');
    }
}
