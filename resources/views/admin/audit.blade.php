{{-- Audit trail / History page: a list of recent events with two tabs and a CSV download. --}}
@extends('layouts.admin')
@section('title', 'Audit trail & history')
@section('heading', 'Audit trail & history')
@section('lead', 'A clear record of changes across your workspace.')

@section('content')
{{-- Tabs: Audit trail (security changes) and History (general activity) --}}
<div class="tabs page-tabs">
    <a href="{{ route('admin.audit.index', ['tab' => 'audit']) }}" class="{{ $tab === 'audit' ? 'active' : '' }}">Audit trail</a>
    <a href="{{ route('admin.audit.index', ['tab' => 'history']) }}" class="{{ $tab === 'history' ? 'active' : '' }}">History</a>
</div>

<section class="card">
    <div class="card-head">
        <div>
            <h2>{{ $tab === 'audit' ? 'Recent activity' : 'Workspace history' }}</h2>
            <p class="muted small">{{ $tab === 'audit' ? 'Account security and permission changes.' : 'Sign-ins, exports and other general activity.' }}</p>
        </div>
        <a class="btn" href="{{ route('admin.audit.export', ['tab' => $tab, 'q' => $filters['q'], 'user' => $filters['user']]) }}">Export CSV</a>
    </div>

    <div class="row-split small">
        <span>Last {{ $days }} days</span>
        <span class="muted">{{ $logs->total() }} {{ \Illuminate\Support\Str::plural('event', $logs->total()) }}</span>
    </div>
    <form class="row-split mt" method="GET">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search event or detail" aria-label="Search audit log">
        <input type="search" name="user" value="{{ $filters['user'] }}" placeholder="Filter by user" aria-label="Filter by user">
        <button type="submit" class="btn">Filter</button>
        <a class="btn" href="{{ route('admin.audit.index',['tab'=>$tab]) }}">Reset</a>
    </form>

    {{-- One row per event: what happened, who did it, when --}}
    <table class="table responsive-table" data-sortable>
        <caption>{{ $tab === 'audit' ? 'Audit trail events' : 'Workspace history events' }}</caption>
        <thead><tr><th scope="col" data-sort-type="text">Event</th><th scope="col" data-sort-type="text">User</th><th scope="col" data-sort-type="text">Target</th><th scope="col" data-sort-type="date">Time</th></tr></thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td data-label="Event"><div class="value-sm">{{ $log->event }}</div><div class="muted small">{{ $log->detail }}</div></td>
                <td data-label="User">{{ $log->actor_name }}</td>
                <td data-label="Target">{{ $log->detail ?: '—' }}</td>
                <td data-label="Time" data-sort="{{ $log->created_at->toIso8601String() }}" class="muted">{{ $log->when_label }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted empty">No events in the last {{ $days }} days.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- Footer: "Showing x of y" and the Previous / Next page links --}}
    <div class="row-split small muted">
        <span>
            @if($logs->hasPages())
                Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} events
            @else
                Showing all {{ $logs->total() }} {{ \Illuminate\Support\Str::plural('event', $logs->total()) }}
            @endif
        </span>
        @if($logs->hasPages())
            <span class="pager">
                @if($logs->onFirstPage())<span>Previous</span>@else<a href="{{ $logs->previousPageUrl() }}">Previous</a>@endif
                @if($logs->hasMorePages())<a href="{{ $logs->nextPageUrl() }}">Next</a>@else<span>Next</span>@endif
            </span>
        @endif
    </div>
</section>
<p class="foot-note">Audit trail records security changes. History shows general workspace activity.</p>
@endsection
