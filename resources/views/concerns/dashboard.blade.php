@extends('layouts.citizen')
@section('title', 'My Dashboard')

@section('content')
@php $cats = config('concerns.categories'); @endphp
<h1>My Dashboard</h1>
<p class="lead">A quick look at the concerns you have submitted.</p>

<div class="stats">
  <div class="card stat"><div class="num">{{ $stats['total'] }}</div><div class="lbl">My Concerns</div></div>
  <div class="card stat"><div class="num">{{ $stats['under_review'] }}</div><div class="lbl">Under Review</div></div>
  <div class="card stat"><div class="num">{{ $stats['reviewed'] }}</div><div class="lbl">Reviewed</div></div>
  <div class="card stat"><div class="num">{{ $stats['resolved'] }}</div><div class="lbl">Resolved</div></div>
</div>

<section class="card">
  <div class="card-head">
    <h2>{{ $filtered ? 'Search results' : 'My Recent Submissions' }}</h2>
    <a class="btn primary" href="{{ route('concerns.create') }}" style="padding:8px 14px;font-size:12px">+ Submit a concern</a>
  </div>

  <form class="filters" method="GET" action="{{ route('citizen.dashboard') }}">
    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search reference, barangay or keywords" aria-label="Search">
    <select name="category" aria-label="Category">
      <option value="">All categories</option>
      @foreach($cats as $key => $c)
        <option value="{{ $key }}" @selected(($filters['category'] ?? '') === $key)>{{ $c['label'] }}</option>
      @endforeach
    </select>
    <select name="status" aria-label="Status">
      <option value="">All statuses</option>
      <option value="under_review" @selected(($filters['status'] ?? '') === 'under_review')>Under Review</option>
      <option value="reviewed" @selected(($filters['status'] ?? '') === 'reviewed')>Reviewed</option>
      <option value="resolved" @selected(($filters['status'] ?? '') === 'resolved')>Resolved</option>
    </select>
    <select name="barangay" aria-label="Barangay">
      <option value="">All barangays</option>
      @foreach(config('concerns.barangays') as $b)
        <option value="{{ $b }}" @selected(($filters['barangay'] ?? '') === $b)>{{ $b }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn primary">Filter</button>
    @if($filtered)<a class="btn" href="{{ route('citizen.dashboard') }}">Reset</a>@endif
  </form>

  @if($recent->isEmpty())
    <p class="sub" style="margin:14px 0 0">
      {{ $filtered ? 'No concerns match your search or filters.' : "You haven't submitted a concern yet. Use \"Submit a concern\" to report one." }}
    </p>
  @else
    @if($filtered)<div class="hint" style="margin:10px 0 0">{{ $recent->total() }} {{ \Illuminate\Support\Str::plural('concern', $recent->total()) }} found</div>@endif
    <div class="table-wrap">
      <table class="dtable">
        <thead><tr><th>Reference</th><th>Category</th><th>Status</th></tr></thead>
        <tbody>
        @foreach($recent as $c)
          <tr>
            <td><a class="ref-link" href="{{ route('concerns.status', ['ref' => $c->reference]) }}">{{ $c->reference }}</a></td>
            <td>
              <div class="chips">
                @foreach($c->categoryKeys() as $k)<span class="chip {{ $cats[$k]['cls'] }}">{{ $cats[$k]['label'] }}</span>@endforeach
              </div>
            </td>
            <td><span class="badge {{ $c->statusClass() }}">{{ $c->statusLabel() }}</span></td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    @if($recent->hasPages())
      <div class="pager">
        @if($recent->onFirstPage())<span>Previous</span>@else<a href="{{ $recent->previousPageUrl() }}">Previous</a>@endif
        <span class="muted">Page {{ $recent->currentPage() }} of {{ $recent->lastPage() }}</span>
        @if($recent->hasMorePages())<a href="{{ $recent->nextPageUrl() }}">Next</a>@else<span>Next</span>@endif
      </div>
    @endif
    <div class="hint" style="margin-top:12px">Select a reference number to see its full progress.</div>
  @endif
</section>
@endsection
