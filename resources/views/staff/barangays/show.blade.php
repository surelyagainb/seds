{{-- One barangay: overview, its concerns, and the ordinances matched there. --}}
@extends('layouts.staff')
@section('title', 'Barangay Detail')
@section('crumb', 'Barangay detail')

@section('content')
@php
    $cats = config('concerns.categories');
    $row = function ($c) use ($cats) {
        [$l, $k] = $c->staffStatus();
        return [$l, $k, $cats[$c->categoryKeys()[0]]];
    };
@endphp
<a class="back" href="{{ route('staff.barangays.index') }}">← Barangay directory</a>
<div class="page-head"><div><h1>{{ $name }}</h1><p>{{ $total }} concerns recorded • {{ $matched }} matched • {{ $unmatched }} unmatched</p></div></div>

<div data-tabs data-initial="{{ $tab }}">
  <div class="tabs" role="tablist">
    <button type="button" data-tab="ov" role="tab">Overview</button>
    <button type="button" data-tab="co" role="tab">Concerns</button>
    <button type="button" data-tab="or" role="tab">Ordinances</button>
  </div>

  {{-- Overview --}}
  <div data-panel="ov">
    <div class="stats" style="grid-template-columns:repeat(3,1fr)">
      <div class="stat"><span>Unmatched concerns</span><b>{{ $unmatched }}</b><span>Need an ordinance link</span></div>
      <div class="stat"><span>Recurring issues</span><b>{{ $recurring }}</b><a class="small" href="{{ route('staff.recurring', ['barangay' => $name]) }}">View recurring</a></div>
      <div class="stat dark"><span>Total concerns</span><b>{{ $total }}</b><span>All time</span></div>
    </div>
    <div class="grid-main mb">
      <section class="card">
        <h2>By primary category</h2>
        <p class="muted small">Counted by the first category of each concern.</p>
        <div class="bars mt">
          @foreach($cats as $k => $c)
            @php $n = $primary[$k] ?? 0; @endphp
            <div class="b"><span>{{ $c['label'] }}</span><div class="bar"><i style="width:{{ $total ? round($n / $total * 100) : 0 }}%"></i></div><em>{{ $n }}</em></div>
          @endforeach
        </div>
      </section>
      <section class="card">
        <h2>Quick facts</h2>
        <div class="kv"><span>Match rate</span><b>{{ $total ? round($matched / $total * 100) : 0 }}%</b></div>
        <div class="kv"><span>Share of all concerns</span><b>{{ $share }}%</b></div>
        <div class="kv"><span>Concerns listed</span><b>{{ $total }}</b></div>
      </section>
    </div>
    <section class="card flush">
      <div style="padding:16px 18px 6px"><h2>Concerns linked to {{ $name }}</h2><p class="muted small">Newest first.</p></div>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Reference</th><th>Concern</th><th>Category</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        @forelse($overview as $c)
          @php [$l, $k, $cat] = $row($c); @endphp
          <tr><td><b>{{ $c->reference }}</b></td><td>{{ \Illuminate\Support\Str::limit($c->description, 90) }}</td>
            <td><div class="chips"><span class="chip {{ $cat['cls'] }}">{{ $cat['label'] }}</span></div></td>
            <td><span class="badge {{ $k }}">{{ $l }}</span></td><td>{{ $c->submitted_at->format('j M Y') }}</td>
            <td><a href="{{ route('staff.concerns.show', $c) }}">View</a></td></tr>
        @empty
          <tr><td colspan="6" class="empty">No concerns to show.</td></tr>
        @endforelse
        </tbody></table></div>
      @include('partials.pager', ['p' => $overview])
    </section>
  </div>

  {{-- Concerns (with a status filter) --}}
  <div data-panel="co" hidden>
    <form class="filters card" method="GET" action="{{ route('staff.barangays.show', $name) }}">
      <input type="hidden" name="tab" value="co">
      <div><label for="cs">Status</label>
        <select id="cs" name="status"><option value="">All statuses</option>
          @foreach(['Needs review', 'Reviewed', 'Rejected', 'Unmatched', 'No ordinance found'] as $s)<option @selected($status === $s)>{{ $s }}</option>@endforeach
        </select></div>
      <div class="btns"><button class="btn primary" type="submit">Filter</button><a class="btn" href="{{ route('staff.barangays.show', [$name, 'tab' => 'co']) }}">Reset</a></div>
    </form>
    <section class="card flush">
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Reference</th><th>Concern</th><th>Category</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        @forelse($concerns as $c)
          @php [$l, $k, $cat] = $row($c); @endphp
          <tr><td><b>{{ $c->reference }}</b></td><td>{{ \Illuminate\Support\Str::limit($c->description, 90) }}</td>
            <td><div class="chips"><span class="chip {{ $cat['cls'] }}">{{ $cat['label'] }}</span></div></td>
            <td><span class="badge {{ $k }}">{{ $l }}</span></td><td>{{ $c->submitted_at->format('j M Y') }}</td>
            <td><a href="{{ route('staff.concerns.show', $c) }}">View</a></td></tr>
        @empty
          <tr><td colspan="6" class="empty">No concerns to show.</td></tr>
        @endforelse
        </tbody></table></div>
      @include('partials.pager', ['p' => $concerns])
    </section>
  </div>

  {{-- Ordinances matched here --}}
  <div data-panel="or" hidden>
    <section class="card flush">
      <div style="padding:16px 18px 6px"><h2>Ordinances matched here</h2><p class="muted small">Each concern is counted once, under its best ordinance.</p></div>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Ordinance</th><th>Category</th><th class="num">Concerns</th><th></th></tr></thead>
        <tbody>
        @forelse($counts as $id => $n)
          @php $o = $ordinances[$id] ?? null; @endphp
          @if($o)
          <tr><td><b>{{ $o->title }}</b><div class="muted small">{{ $o->number }}</div></td>
            <td><div class="chips"><span class="chip {{ $cats[$o->category]['cls'] ?? 'other' }}">{{ $cats[$o->category]['label'] ?? $o->category }}</span></div></td>
            <td class="num">{{ $n }}</td><td><a href="{{ route('staff.ordinances.show', $o) }}">View</a></td></tr>
          @endif
        @empty
          <tr><td colspan="4" class="empty">No ordinance matches yet.</td></tr>
        @endforelse
        </tbody></table></div>
    </section>
  </div>
</div>
@endsection
