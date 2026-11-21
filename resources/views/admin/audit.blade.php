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
        <a class="btn" href="{{ route('admin.audit.export', ['tab' => $tab]) }}">Export CSV</a>
    </div>

    <div class="row-split small">
        <span>Last {{ $days }} days</span>
        <span class="muted">{{ $logs->total() }} {{ \Illuminate\Support\Str::plural('event', $logs->total()) }}</span>
    </div>

    {{-- One row per event: what happened, who did it, when --}}
    <table class="table">
        <thead><tr><th>Event</th><th>By</th><th>When</th></tr></thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td><div class="value-sm">{{ $log->event }}</div><div class="muted small">{{ $log->detail }}</div></td>
                <td>{{ $log->actor_name }}</td>
                <td class="muted">{{ $log->when_label }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted empty">No events in the last {{ $days }} days.</td></tr>
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
