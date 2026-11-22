{{-- Recurring issue analysis: problems reported in several different months. --}}
@extends('layouts.staff')
@section('title', 'Recurring Issue Analysis')
@section('crumb', 'Recurring issue analysis')

@section('content')
@php
    $cats = config('concerns.categories');
    $WORD = ['flood' => 'flooding', 'waste' => 'waste problems', 'health' => 'health issues', 'road' => 'road issues', 'other' => 'other concerns', '' => 'concerns'];
    $lastMonth = $issues->sum(fn ($r) => $r['months'][$months - 1]);
    $o = ($selected && $selected['ordinance_id']) ? ($ordinances[$selected['ordinance_id']] ?? null) : null;
@endphp
<div class="page-head">
  <div>
    <h1>Recurring issue analysis</h1>
    <p>Find problems that keep coming back, even where an ordinance already exists.</p>
  </div>
</div>

<form class="filters card" method="GET" action="{{ route('staff.recurring') }}">
  <div><label for="fb">Barangay</label>
    <select id="fb" name="barangay"><option value="">All barangays</option>
      @foreach(collect(config('concerns.barangays'))->sort() as $b)<option value="{{ $b }}" @selected($barangay === $b)>{{ $b }}</option>@endforeach
    </select></div>
  <div><label for="fc">Category</label>
    <select id="fc" name="category"><option value="">All categories</option>
      @foreach($cats as $k => $c)<option value="{{ $k }}" @selected($category === $k)>{{ $c['label'] }}</option>@endforeach
    </select></div>
  <div><label for="fd">Date range</label>
    <select id="fd" name="months"><option value="6" @selected($months === 6)>Last 6 months</option><option value="3" @selected($months === 3)>Last 3 months</option></select></div>
  <div class="btns"><button class="btn primary" type="submit">Apply</button></div>
</form>

<div class="grid-main">
  <div class="stack">
    <section class="card summary">
      <div>
        <div class="big">{{ $reports }} {{ \Illuminate\Support\Str::plural('report', $reports) }} of {{ $WORD[$category ?? ''] ?? 'concerns' }}, {{ $issues->count() }} recurring</div>
        <p class="muted" style="margin:4px 0 0">{{ $issues->isNotEmpty() ? 'These issues were reported in more than one month.' : 'No recurring issues match these filters.' }}</p>
      </div>
      <div class="rate"><span class="eyebrow">Current monthly rate</span><b>{{ $lastMonth }} {{ \Illuminate\Support\Str::plural('report', $lastMonth) }} in {{ end($labels) }}</b>
        <span class="muted small">Average {{ number_format($reports / $months, 1) }} per month over {{ $months }} months</span></div>
    </section>

    <section class="card flush">
      <div style="padding:16px 18px 6px"><h2>Issues recurring the longest</h2><p class="muted small">Select a row to see details on the right.</p></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Description</th><th>Barangay</th><th>Category</th><th>First reported</th><th class="num">Times</th><th class="num">Months</th><th>Ordinance</th></tr></thead>
          <tbody>
          @forelse($issues as $r)
            @php $ro = $r['ordinance_id'] ? ($ordinances[$r['ordinance_id']] ?? null) : null; @endphp
            <tr class="clickable {{ $selected && $r['key'] === $selected['key'] ? 'sel' : '' }}"
                data-href="{{ route('staff.recurring', array_filter(['barangay' => $barangay, 'category' => $category, 'months' => $months, 'issue' => $r['key']])) }}">
              <td><b>{{ $cats[$r['category']]['label'] }} concerns</b></td>
              <td>{{ $r['barangay'] }}</td>
              <td><div class="chips"><span class="chip {{ $cats[$r['category']]['cls'] }}">{{ $cats[$r['category']]['label'] }}</span></div></td>
              <td>{{ $r['first']->format('j M Y') }}</td>
              <td class="num">{{ $r['times'] }}</td><td class="num">{{ $r['span'] }}</td>
              <td>@if($ro)<a href="{{ route('staff.ordinances.show', $ro) }}">{{ $ro->number }}</a>@else<span class="muted">None</span>@endif</td>
            </tr>
          @empty
            <tr><td colspan="7" class="empty">No recurring issues match these filters.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </section>

    @if($selected)
      @if($o)
        <div class="callout amber"><b>An ordinance exists. The issue persists.</b>
          {{ $cats[$selected['category']]['label'] }} concerns in {{ $selected['barangay'] }} keep being reported even though {{ $o->number }} ({{ $o->title }}) is on file. Check whether it is being enforced or needs changes.
          @if($flags->has('review'))
            <div class="mt small"><b style="display:inline">Flagged for review</b> on {{ $flags['review']->updated_at->format('j M Y') }}{{ $flags['review']->user ? ' by '.$flags['review']->user->name : '' }}.</div>
          @endif
          <div class="row mt">
            <form class="inline-form" method="POST" action="{{ route('staff.recurring.flag') }}">@csrf
              <input type="hidden" name="type" value="review"><input type="hidden" name="barangay" value="{{ $selected['barangay'] }}">
              <input type="hidden" name="category" value="{{ $selected['category'] }}"><input type="hidden" name="ordinance_id" value="{{ $o->id }}">
              <button class="btn primary" type="submit">Flag for review</button></form>
            <a class="btn outline" href="{{ route('staff.ordinances.show', $o) }}">Review ordinance</a>
          </div>
        </div>
      @else
        <div class="callout"><b>No ordinance covers this issue.</b>
          It has been reported in {{ $selected['span'] }} months with no matching ordinance on file.
          @if($flags->has('new_ordinance'))
            <div class="mt small"><b style="display:inline">Noted as a possible new ordinance</b> on {{ $flags['new_ordinance']->updated_at->format('j M Y') }}{{ $flags['new_ordinance']->user ? ' by '.$flags['new_ordinance']->user->name : '' }}.</div>
          @endif
          <div class="row mt">
            <form class="inline-form" method="POST" action="{{ route('staff.recurring.flag') }}">@csrf
              <input type="hidden" name="type" value="new_ordinance"><input type="hidden" name="barangay" value="{{ $selected['barangay'] }}"><input type="hidden" name="category" value="{{ $selected['category'] }}">
              <button class="btn primary" type="submit">Suggest new ordinance</button></form>
          </div>
        </div>
      @endif
    @endif

    <section class="card">
      <h2>Monthly recurrence timeline</h2>
      <p class="muted small">Reports per month for the issues listed above.</p>
      <div class="chart-box" style="height:240px"><canvas id="chart" role="img" aria-label="Bar chart of reports per month"></canvas></div>
      <p class="muted small" id="chartFallback" hidden>The chart could not load (Chart.js needs an internet connection).</p>
    </section>
  </div>

  {{-- Details of the selected issue --}}
  <aside class="card">
    @if($selected)
      <div class="eyebrow">Selected issue</div>
      <h2 style="margin-top:4px">{{ $cats[$selected['category']]['label'] }} concerns</h2>
      <div class="mt">
        <div class="kv"><span>Barangay</span><b>{{ $selected['barangay'] }}</b></div>
        <div class="kv"><span>Category</span><b>{{ $cats[$selected['category']]['label'] }}</b></div>
        <div class="kv"><span>First reported</span><b>{{ $selected['first']->format('j M Y') }}</b></div>
        <div class="kv"><span>Last reported</span><b>{{ $selected['last']->format('j M Y') }}</b></div>
        <div class="kv"><span>Times reported</span><b>{{ $selected['times'] }}</b></div>
        <div class="kv"><span>Months recurring</span><b>{{ $selected['span'] }}</b></div>
        <div class="kv"><span>Linked ordinance</span><b>{{ $o ? $o->title : 'None' }}</b></div>
      </div>
      <a class="btn outline block mt" href="{{ route('staff.concerns.index', ['barangay' => $selected['barangay'], 'category' => $selected['category']]) }}">View all reports</a>
    @else
      <h2>Details</h2><p class="muted">Nothing selected.</p>
    @endif
  </aside>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
if (!window.Chart) { document.getElementById("chartFallback").hidden = false; }
else {
  new Chart(document.getElementById("chart"), { type: "bar",
    data: { labels: @json($labels), datasets: [{ label: "Reports", data: @json($chart), backgroundColor: "#0B2A5B" }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: "Reports" } } } } });
}
</script>
@endpush
