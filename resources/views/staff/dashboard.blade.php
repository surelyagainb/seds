{{-- Community overview: stats, filters, map, chart and a table per barangay. --}}
@extends('layouts.staff')
@section('title', 'Community Overview')
@section('crumb', 'Community overview')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIINfQ3ynpFgt8N9F9ZQnSq+5kESx9e4lVg=" crossorigin="">
@endpush

@section('content')
<div class="page-head">
  <div>
    <h1>Community overview</h1>
    <p>See where concerns are coming from and how many have an ordinance match.</p>
  </div>
  <div class="muted small">Last updated: {{ now()->format('j M Y, h:i A') }}</div>
</div>

{{-- Stat cards --}}
<div class="stats">
  <a class="stat dark {{ $category?'':'active-kpi' }}" href="{{ route('staff.concerns.index') }}" title="Open all concerns assigned to you"><span>Total concerns</span><b>{{ number_format($total) }}</b><span>{{ $barangay ?: 'All barangays' }}</span></a>
  <a class="stat {{ $category?'':'active-kpi' }}" href="{{ route('staff.concerns.index',['status'=>'Reviewed']) }}" title="Concerns that have an ordinance match"><span>Matched</span><b>{{ number_format($matched) }}</b><span>{{ $total ? round($matched / $total * 100) : 0 }}% of total</span></a>
  <a class="stat {{ $category?'':'active-kpi' }}" href="{{ route('staff.concerns.index',['status'=>'Needs review']) }}" title="Concerns that still need a staff decision"><span>Pending</span><b>{{ number_format($pending) }}</b><span>Need staff attention</span></a>
  <a class="stat" href="{{ route('staff.recurring') }}" title="Issues reported repeatedly across months"><span>Recurring issues</span><b>{{ $recurring }}</b><span>{{ $barangay ?: 'Municipality-wide' }}</span></a>
  <a class="stat" href="{{ route('staff.ordinances.index') }}" title="Ordinances currently flagged or under review"><span>Ordinances flagged</span><b>{{ $flagged }}</b><span>For review</span></a>
</div>

{{-- Filters (the page reloads with the chosen values) --}}
<form class="filters card" id="filters" method="GET" action="{{ route('staff.dashboard') }}">
  <div><label for="fb">Barangay</label>
    <select id="fb" name="barangay"><option value="">All barangays</option>
      @foreach(collect(config('concerns.barangays'))->sort() as $b)<option value="{{ $b }}" @selected($barangay === $b)>{{ $b }}</option>@endforeach
    </select></div>
  <div><label for="fc">Category</label>
    <select id="fc" name="category"><option value="">All categories</option>
      @foreach(config('concerns.categories') as $k => $c)<option value="{{ $k }}" @selected($category === $k)>{{ $c['label'] }}</option>@endforeach
    </select></div>
  <div><label for="fd">Date range</label>
    <select id="fd" name="period">
      <option value="today" @selected($period==='today')>Today</option>
      <option value="week" @selected($period==='week')>This week</option>
      <option value="month" @selected($period==='month')>This month</option>
      <option value="year" @selected($period==='year')>This year</option>
      <option value="all" @selected($period==='all')>All time</option>
    </select></div>
  <div class="btns"><button class="btn primary" type="submit">Apply filters</button><a class="btn" href="{{ route('staff.dashboard') }}">Reset</a></div>
</form>

{{-- Interactive marker map: barangays and exact concern locations. --}}
<section class="card mb">
  <h2>Concern heatmap</h2>
  <p class="muted small">Report concentration by barangay <span aria-hidden="true">•</span> Drag, zoom or select a pin to inspect a barangay.</p>
  <div class="heat-wrap">
    <p class="muted small" id="mapStatus" role="status" aria-live="polite">Loading map markers...</p>
    <div class="map" id="overview-map" role="region" aria-label="Interactive map of concern locations" aria-live="polite"></div>
    <button class="btn" id="mapRetry" type="button" hidden>Retry map</button>
  </div>
</section>

{{-- Chart: concerns per month by category --}}
<section class="card mb">
  <h2>Concerns over time, by category</h2>
  <p class="muted small">Monthly concerns received{{ $barangay ? ' in '.$barangay : '' }}. A concern with several categories is counted in each. Click a legend item to hide a category.</p>
  <div class="chart-box"><canvas id="chart" aria-label="Stacked bar chart of concerns per month by category" role="img"></canvas></div>
  <p class="muted small" id="chartFallback" hidden>The chart could not load. <button type="button" class="btn" onclick="location.reload()">Retry</button></p>
</section>

{{-- Needs your attention --}}
<section class="card flush">
  <div style="padding:16px 18px 6px"><h2>Needs your attention</h2><p class="muted small">Review queue and ordinance gaps for your assigned categories.</p></div>
  <div class="grid-split" style="padding:0 18px 18px">
    <div>
      <h3>Review queue by age</h3>
      <div class="row gap" style="margin-top:10px">
        <a class="badge amber" href="{{ route('staff.concerns.index',['status'=>'Needs review']) }}" title="Waiting 5 to 6 days">Due soon: {{ $attention['due'] }}</a>
        <a class="badge red" href="{{ route('staff.concerns.index',['status'=>'Needs review']) }}" title="Waiting 7 to 29 days">Overdue: {{ $attention['overdue'] }}</a>
        <a class="badge red" href="{{ route('staff.concerns.index',['status'=>'Needs review']) }}" title="Waiting 30 days or more">Critical: {{ $attention['critical'] }}</a>
      </div>
      @forelse($reminders as $reminder)
        @php($age=$reminder->submitted_at->diffInDays(now()))
        <div class="kv"><span><b>{{ $reminder->reference }}</b><br>{{ $age }} days waiting</span><form method="POST" action="{{ route('staff.concerns.snooze',$reminder) }}">@csrf<button class="btn" type="submit">Snooze 1 day</button></form></div>
      @empty
        <p class="muted small mt">No review reminders right now.</p>
      @endforelse
    </div>
    <div>
      <h3>Ordinance gaps</h3>
      @forelse($gaps->where('count','>',0)->take(5) as $gap)
        <div class="kv"><span><b>{{ $gap['label'] }}</b><br>{{ $gap['count'] }} concerns without a match</span><a class="btn outline" href="{{ route('staff.ordinances.index',['category'=>$gap['key']]) }}">Suggest new ordinance</a></div>
      @empty
        <p class="muted small mt">No ordinance gaps found for assigned categories.</p>
      @endforelse
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
const LABELS = @json($labels);
const SERIES = @json($chart);
const COLORS = { waste: "#2e9e6b", flood: "#3b78b8", health: "#d9577e", road: "#e0a02e", other: "#8b7fc7" };
const el = id => document.getElementById(id);

document.addEventListener("DOMContentLoaded", () => {
  const mapUrl = @json(route('map.data'))
    + "?period=" + encodeURIComponent(@json($period))
    + "&barangay=" + encodeURIComponent(@json($barangay ?? ""))
    + "&category=" + encodeURIComponent(@json($category ?? ""));
  initOverviewMap(mapUrl, "overview-map");
});

/* ---- chart (Chart.js) ---- */
if (!window.Chart) { el("chartFallback").hidden = false; }
else {
  new Chart(el("chart"), {
    type: "bar",
    data: { labels: LABELS, datasets: SERIES.map(s => ({ label: s.label, data: s.data, backgroundColor: COLORS[s.key] })) },
    options: { responsive: true, maintainAspectRatio: false,
      scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: "Concerns" } } },
      plugins: { legend: { position: "bottom" } } }
  });
}
</script>
@endpush