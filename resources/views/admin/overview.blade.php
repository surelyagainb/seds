{{-- Admin home page: account details, role, and the latest activity. --}}
@extends('layouts.admin')
@section('title', 'Administration')
@section('heading', 'Administration')
@section('lead', 'A little control. Everything in one place.')

@section('content')
{{-- Account --}}
<section class="card">
    <div class="card-head">
        <h2>Account</h2>
        <span class="pill {{ $user->is_active ? 'green' : 'grey' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
    </div>
    <div class="grid-2">
        <div><div class="label">Account name</div><div class="value">{{ $user->name }}</div></div>
        <div><div class="label">Email address</div><div class="value">{{ $user->email }}</div></div>
    </div>
    <div class="row-split top-rule">
        <div>
            <div class="value-sm">Password</div>
            <div class="muted small">••••••••••••&nbsp; · &nbsp;{{ $user->password_changed_at ? 'Updated '.$user->password_changed_at->diffForHumans() : 'Never changed' }}</div>
        </div>
        <a class="btn" href="{{ route('admin.password.edit') }}">Change password</a>
    </div>
</section>

{{-- Role (fixed) --}}
<section class="card">
    <div class="card-head"><h2>Role</h2></div>
    <div class="row-split">
        <span class="tag">{{ $user->roleLabel() }}</span>
        <span class="muted small">Can review the audit trail, check accounts, and remove accounts.</span>
    </div>
</section>

{{-- Activity: the 2 newest log entries, with tabs to switch between Audit trail and History --}}
@if($user->isAdmin())
<section class="card">
    <div class="card-head">
        <h2>Activity</h2>
        <a class="link" href="{{ route('admin.audit.index', ['tab' => $tab]) }}">View all activity →</a>
    </div>
    <div class="tabs">
        <a href="{{ route('admin.overview', ['tab' => 'audit']) }}" class="{{ $tab === 'audit' ? 'active' : '' }}">Audit trail</a>
        <a href="{{ route('admin.overview', ['tab' => 'history']) }}" class="{{ $tab === 'history' ? 'active' : '' }}">History</a>
    </div>
    @forelse($activity as $log)
        <div class="row-split {{ $loop->first ? '' : 'top-rule' }} act">
            <div>
                <div class="value-sm">{{ $log->event }}</div>
                <div class="muted small">{{ $log->actor_name }}{{ $log->detail ? ' · '.$log->detail : '' }}</div>
            </div>
            <div class="muted small">{{ $log->when_label }}</div>
        </div>
    @empty
        <p class="muted small">Nothing recorded yet.</p>
    @endforelse
</section>
@endif

@endsection
