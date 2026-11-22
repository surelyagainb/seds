{{-- Barangay directory: concern counts per barangay, and a tab for barangay officials. --}}
@extends('layouts.staff')
@section('title', 'Barangay Directory')
@section('crumb', 'Barangay directory')

@section('content')
<div class="page-head">
  <div>
    <h1>Barangay directory</h1>
    <p>Victoria's barangays, their concern counts and their officials.</p>
  </div>
</div>

<div data-tabs data-initial="dir">
  <div class="tabs" role="tablist">
    <button type="button" data-tab="dir" role="tab">Barangay &amp; directory</button>
    <button type="button" data-tab="off" role="tab">Barangay officials</button>
  </div>

  <div data-panel="dir">
    <form class="filters card" method="GET" action="{{ route('staff.barangays.index') }}">
      <div><label for="fq">Search barangay</label><input type="search" id="fq" name="q" value="{{ $q }}" placeholder="Type a barangay name"></div>
      <div><label for="fs">Show</label>
        <select id="fs" name="show">
          <option value="">All barangays</option>
          <option value="un" @selected($show === 'un')>With unmatched concerns (5 or more)</option>
          <option value="top" @selected($show === 'top')>Top 10 by concerns</option>
        </select></div>
      <div class="btns"><button class="btn primary" type="submit">Filter</button><a class="btn" href="{{ route('staff.barangays.index') }}">Reset</a></div>
    </form>

    <section class="card flush">
      <div style="padding:16px 18px 6px"><h2>Victoria barangays</h2><p class="muted small">Showing {{ $rows->count() }} of {{ $all->count() }} barangays</p></div>
      <div class="scroll" style="max-height:520px">
        <table class="table">
          <thead><tr><th>Barangay</th><th class="num">Total concerns</th><th class="num">Matched</th><th class="num">Unmatched</th><th></th></tr></thead>
          <tbody>
          @forelse($rows as $b)
            <tr><td><b>{{ $b['name'] }}</b></td><td class="num">{{ $b['total'] }}</td><td class="num">{{ $b['matched'] }}</td><td class="num">{{ $b['unmatched'] }}</td>
              <td class="num"><a href="{{ route('staff.barangays.show', $b['name']) }}">View details</a></td></tr>
          @empty
            <tr><td colspan="5" class="empty">No barangay matches your search.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </section>
    <p class="muted small mt">Counts come from the concerns in the database (all time).</p>
  </div>

  <div data-panel="off" hidden>
    <section class="card flush">
      <div style="padding:16px 18px 6px"><h2>Barangay officials</h2><p class="muted small">No official records are loaded yet. Add names and contact details here once the data is ready.</p></div>
      <div class="scroll" style="max-height:520px">
        <table class="table">
          <thead><tr><th>Barangay</th><th>Punong Barangay</th><th>Contact</th><th>Last updated</th></tr></thead>
          <tbody>
          @foreach(collect(config('concerns.barangays'))->sort() as $name)
            <tr><td><b>{{ $name }}</b></td><td class="muted">Not yet added</td><td class="muted">Not yet added</td><td class="muted">—</td></tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </section>
  </div>
</div>
@endsection
