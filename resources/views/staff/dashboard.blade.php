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
  <div class="stat dark"><span>Total concerns</span><b>{{ number_format($total) }}</b><span>{{ $barangay ?: 'All barangays' }}</span></div>
  <div class="stat"><span>Matched</span><b>{{ number_format($matched) }}</b><span>{{ $total ? round($matched / $total * 100) : 0 }}% of total</span></div>
  <div class="stat"><span>Unmatched / pending</span><b>{{ number_format($pending) }}</b><span>Need staff attention</span></div>
  <div class="stat"><span>Recurring issues</span><b>{{ $recurring }}</b><span>{{ $barangay ?: 'Municipality-wide' }}</span></div>
  <div class="stat"><span>Ordinances flagged</span><b>{{ $flagged }}</b><span>For review</span></div>
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
    <select id="fd" name="months"><option value="6" @selected($months === 6)>Last 6 months</option><option value="3" @selected($months === 3)>Last 3 months</option></select></div>
  <div class="btns"><button class="btn primary" type="submit">Apply filters</button><a class="btn" href="{{ route('staff.dashboard') }}">Reset</a></div>
</form>

{{-- Map: one pin per barangay that has coordinates in config/staff.php. Clicking a pin filters the page to that barangay. --}}
<section class="card mb">
  <h2>Concern heatmap</h2>
  <p class="muted small">Report concentration by barangay <span aria-hidden="true">•</span> Drag, zoom or select a pin to inspect a barangay.</p>
  <div class="heat-wrap"><div class="map" id="map" aria-label="Interactive map of concern locations"></div></div>
</section>

{{-- Chart: concerns per month by category --}}
<section class="card mb">
  <h2>Concerns over time, by category</h2>
  <p class="muted small">Monthly concerns received{{ $barangay ? ' in '.$barangay : '' }}. A concern with several categories is counted in each. Click a legend item to hide a category.</p>
  <div class="chart-box"><canvas id="chart" aria-label="Stacked bar chart of concerns per month by category" role="img"></canvas></div>
  <p class="muted small" id="chartFallback" hidden>The chart could not load (Chart.js needs an internet connection).</p>
</section>

{{-- Table per barangay --}}
<section class="card flush">
  <div style="padding:16px 18px 6px"><h2>Concerns by barangay</h2><p class="muted small">Total, matched and unmatched concerns. Select a barangay to see its details.</p></div>
  <div class="scroll">
    <table class="table">
      <thead><tr><th>Barangay</th><th class="num">Total</th><th class="num">Matched</th><th class="num">Unmatched</th><th></th></tr></thead>
      <tbody>
      @foreach($rows as $r)
        <tr><td><b>{{ $r['name'] }}</b></td><td class="num">{{ $r['total'] }}</td><td class="num">{{ $r['matched'] }}</td><td class="num">{{ $r['unmatched'] }}</td>
          <td class="num"><a href="{{ route('staff.barangays.show', $r['name']) }}">View details</a></td></tr>
      @endforeach
      </tbody>
      <tfoot><tr><td>Total</td><td class="num">{{ $total }}</td><td class="num">{{ $matched }}</td><td class="num">{{ $pending }}</td><td></td></tr></tfoot>
    </table>
  </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
const PINS = @json($pins);
const SELECTED = @json($barangay);
const CENTER = @json(config('staff.map_center'));
const LABELS = @json($labels);
const SERIES = @json($chart);
const COLORS = { waste: "#2e9e6b", flood: "#3b78b8", health: "#d9577e", road: "#e0a02e", other: "#8b7fc7" };
const el = id => document.getElementById(id);
const esc = s => String(s).replace(/[&<>"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));

/* ---- map (OpenStreetMap tiles) ---- */
if (!window.L) {
  el("map").innerHTML = '<div class="map-fallback">Map tiles could not load. Check your internet connection.</div>';
} else {
  const map = L.map("map").setView([CENTER.lat, CENTER.lng], CENTER.zoom);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

  PINS.forEach(p => {
    const icon = L.divIcon({
      className: "concern-marker-wrap" + (SELECTED && SELECTED !== p.name ? " dim" : ""),
      html: '<div class="concern-marker' + (p.primary ? " primary" : "") + '"><span class="concern-marker-name">' + esc(p.name) + '</span><span class="concern-marker-value">' + p.total + '</span></div>',
      iconSize: [76, 82], iconAnchor: [38, 82], popupAnchor: [0, -78]
    });
    const marker = L.marker([p.lat, p.lng], { icon, keyboard: true, title: p.name + ": " + p.total + " concerns" }).addTo(map);
    marker.bindPopup("<strong>" + esc(p.name) + "</strong><br>" + p.total + " concerns<br><a href=\"" + @json(url('staff/barangays')) + "/" + encodeURIComponent(p.name) + "\">View barangay</a>");
    marker.on("click", () => { el("fb").value = p.name; el("filters").submit(); });
  });
  setTimeout(() => map.invalidateSize(), 0);
}

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
