@extends('layouts.admin')
@section('title','Accounts')
@section('heading','Accounts')
@section('lead','Create and manage citizen and Sangguniang Bayan accounts.')

@section('content')
@if(session('temporary_password'))
<div class="notice-banner" role="status"><b>Temporary password: {{ session('temporary_password') }}</b><br>Shown once. Share it securely with the account holder.</div>
@endif
<form class="card row-split" method="GET">
  <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search name, username or email" aria-label="Search accounts">
  <select name="role" aria-label="Filter by role"><option value="">All roles</option><option value="citizen" @selected($filters['role']==='citizen')>Citizen</option><option value="sb_member" @selected($filters['role']==='sb_member')>SB member</option><option value="admin" @selected($filters['role']==='admin')>Administrator</option></select>
  <select name="status" aria-label="Filter by status"><option value="">All statuses</option><option value="active" @selected($filters['status']==='active')>Active</option><option value="pending" @selected($filters['status']==='pending')>Pending</option><option value="deactivated" @selected($filters['status']==='deactivated')>Deactivated</option></select>
  <button type="submit" class="btn">Filter</button>
  <a class="btn" href="{{ route('admin.members.index') }}">Reset</a>
</form>
<div class="callout mb"><b>Role explanations</b> Citizen submits and tracks concerns. SB member reviews concerns and ordinances for assigned categories. Administrator manages accounts, barangays, health and audit logs.</div>
<section class="card">
  <div class="card-head"><h2>All accounts</h2><button type="button" class="btn primary" data-open="createAccount">Create account</button></div>
  <table class="table responsive-table" data-sortable>
    <caption>All system accounts</caption>
    <thead><tr><th scope="col" data-sort-type="text">Account</th><th scope="col" data-sort-type="text">Username</th><th scope="col" data-sort-type="text">Role</th><th scope="col" data-sort-type="text">Status</th><th scope="col">Actions</th></tr></thead>
    <tbody>
    @forelse($members as $member)
      <tr>
        <td data-label="Account"><div class="value-sm">{{ $member->name }}</div><div class="muted small">{{ $member->email ?: $member->mobile }}</div></td>
        <td data-label="Username">{{ $member->username }}</td>
        <td data-label="Role"><span class="tag">{{ $member->roleLabel() }}</span></td>
        <td data-label="Status"><span class="pill {{ $member->is_active?'green':'grey' }}">{{ ucfirst($member->status ?: ($member->is_active?'active':'deactivated')) }}</span></td>
        <td data-label="Actions">
          <button type="button" class="btn small-btn" data-open="edit-{{ $member->id }}">Edit</button>
          @if(!($member->isAdmin() && $member->id===auth()->id()))
            <form class="inline" method="POST" action="{{ route('admin.members.toggle',$member) }}">@csrf<button type="submit" class="btn small-btn">{{ $member->is_active?'Deactivate':'Reactivate' }}</button></form>
            @if(! $member->is_active)<button type="button" class="btn small-btn danger" data-open="hard-{{ $member->id }}">Delete</button>@endif
          @endif
          <button type="button" class="btn small-btn" data-open="reset-{{ $member->id }}">Reset password</button>
        </td>
      </tr>
    @empty<tr><td colspan="5" class="empty">No accounts match the filters.</td></tr>@endforelse
    </tbody>
  </table>
</section>
<section class="card"><h2>Activity summary</h2><p class="muted small">Latest 10 account actions.</p>
@forelse($activity as $log)<div class="top-rule act"><b>{{ $log->event }}</b><div class="muted small">{{ $log->actor_name }} · {{ $log->detail }}</div><div class="muted small">{{ $log->created_at->format('j M Y, h:i A') }}</div></div>@empty<p class="muted">No activity recorded yet.</p>@endforelse
</section>

@foreach($members as $member)
<dialog id="edit-{{ $member->id }}" class="dialog">
  <form method="POST" action="{{ route('admin.members.update',$member) }}" class="stack">@csrf @method('PUT')
    <h2>Edit {{ $member->username }}</h2>
    <div class="field"><label for="en-{{ $member->id }}">Full name *</label><input id="en-{{ $member->id }}" name="name" value="{{ $member->name }}" required></div>
    <div class="field"><label for="eu-{{ $member->id }}">Username *</label><input id="eu-{{ $member->id }}" name="username" value="{{ $member->username }}" required></div>
    <div class="field"><label for="ee-{{ $member->id }}">Email * (required for email verification)</label><input id="ee-{{ $member->id }}" type="email" name="email" value="{{ $member->email }}" required></div>
    <div class="field"><label for="em-{{ $member->id }}">Mobile (optional)</label><input id="em-{{ $member->id }}" name="mobile" value="{{ $member->mobile }}"></div>
    <div class="field"><label for="er-{{ $member->id }}">Role *</label><select id="er-{{ $member->id }}" name="role"><option value="citizen" @selected($member->role==='citizen')>Citizen</option><option value="sb_member" @selected($member->role==='sb_member')>SB member</option><option value="admin" @selected($member->role==='admin')>Administrator</option></select></div>
    @if($member->role==='sb_member')
      <fieldset><legend>Assigned categories</legend>@foreach(config('concerns.categories') as $key=>$category)<label class="check"><input type="checkbox" name="assigned_categories[]" value="{{ $key }}" {{ in_array($key,$member->assigned_categories ?: [],true)?'checked':'' }}> {{ $category['label'] }}</label>@endforeach</fieldset>
    @endif
    <div class="actions"><button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button><button type="submit" class="btn primary">Save changes</button></div>
  </form>
</dialog>
<dialog id="reset-{{ $member->id }}" class="dialog">
  <form method="POST" action="{{ route('admin.members.reset-password',$member) }}" class="stack">@csrf
    <h2>Set temporary password</h2><p class="muted">A temporary password will replace this person's current password.</p>
    <label class="check"><input type="checkbox" name="confirm_reset" value="1" required> I confirm this password reset</label>
    <div class="actions"><button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button><button type="submit" class="btn primary">Set password</button></div>
  </form>
</dialog>
@if(! $member->is_active)
<dialog id="hard-{{ $member->id }}" class="dialog">
  <form method="POST" action="{{ route('admin.members.destroy',$member) }}" class="stack">@csrf @method('DELETE')
    <h2>Permanently delete account</h2><p class="muted">Type <b>{{ $member->username }}</b> to confirm. Deactivated accounts only.</p>
    <input type="text" name="username_confirm" required placeholder="{{ $member->username }}">
    <div class="actions"><button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button><button type="submit" class="btn danger solid">Delete permanently</button></div>
  </form>
</dialog>
@endif
@endforeach

<dialog id="createAccount" class="dialog">
  <form method="POST" action="{{ route('admin.members.store') }}" class="stack">@csrf
    <h2>Create account</h2>
    <div class="field"><label for="cn">Full name *</label><input id="cn" name="name" required></div>
    <div class="field"><label for="cu">Username *</label><input id="cu" name="username" required></div>
    <div class="field"><label for="ce">Email * (required for email verification)</label><input id="ce" type="email" name="email" required></div>
    <div class="field"><label for="cm">Mobile (optional)</label><input id="cm" name="mobile"></div>
    <div class="field"><label for="cr">Role *</label><select id="cr" name="role"><option value="citizen">Citizen</option><option value="sb_member">SB member</option><option value="admin">Administrator</option></select></div>
    <fieldset><legend>SB assigned categories</legend>@foreach(config('concerns.categories') as $key=>$category)<label class="check"><input type="checkbox" name="assigned_categories[]" value="{{ $key }}"> {{ $category['label'] }}</label>@endforeach</fieldset>
    <div class="actions"><button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button><button type="submit" class="btn primary">Create account</button></div>
  </form>
</dialog>
@endsection
