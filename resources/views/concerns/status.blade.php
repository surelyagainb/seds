@extends('layouts.citizen')
@section('title', 'Check Concern Status')

@section('content')
@php
    $cats = config('concerns.categories');
    $fmt  = fn ($d) => $d->format('j M Y, h:i A');
@endphp
<h1>Check concern status</h1>
<p class="lead">Use your reference number to see where your concern is in the review process.</p>

<form class="card lookup" method="GET" action="{{ route('concerns.status') }}">
  <div>
    <label class="field" for="refInput" style="margin-top:0">Reference number</label>
    <div class="lookup-row">
      <input type="text" id="refInput" name="ref" value="{{ $ref }}" placeholder="VIC-2026-000000" autocomplete="off">
      <button type="submit" class="btn primary" style="white-space:nowrap">Check status</button>
    </div>
    <div class="hint">Enter the full reference number shown on your receipt.</div>
  </div>
  <div><h4>Looking for your reference number?</h4><p>Check the confirmation page or receipt you saved after submitting your concern, or pick one from your list below.</p></div>
</form>

@if($concern)
@php
    $stages = [
        ['at' => $concern->submitted_at, 'name' => 'Received', 'done' => 'Your report was safely recorded.', 'wait' => ''],
        ['at' => $concern->analyzed_at,  'name' => 'Analyzed', 'done' => 'Concern details and categories analyzed.', 'wait' => 'Waiting for analysis.'],
        ['at' => $concern->matched_at,   'name' => 'Matched',  'done' => 'Potentially relevant demo ordinances found.', 'wait' => 'Waiting for ordinance matching.'],
        ['at' => $concern->reviewed_at,  'name' => 'Reviewed', 'done' => 'Municipal staff reviewed the suggested match.', 'wait' => 'Municipal staff review the suggested match.'],
    ];
    $last = $concern->lastStage();
    $allDone = $last === 3;
    $summary = [
        0 => ['Received • Awaiting analysis', 'Your concern has been safely recorded. Analysis and ordinance matching have not started yet. No action is needed from you at this stage.'],
        1 => ['Analyzed • Awaiting ordinance matching', 'Your concern has been analyzed. We are now looking for potentially relevant demo ordinances. No action is needed from you.'],
        2 => ['Matched • Awaiting municipal staff review', 'Your concern has been analyzed and matched to potentially relevant demo ordinances. Staff have not reviewed the match yet. No action is needed from you at this stage.'],
        3 => ['Reviewed • Complete', 'Municipal staff have reviewed the suggested match. Contact the municipal office and quote your reference number for any next step.'],
    ][$last];
    $badge = in_array($last, [0, 3]) ? [$last === 0 ? 'Received' : 'Reviewed', 'received'] : ['Needs review', 'review'];
@endphp
<div class="grid" style="grid-template-columns:minmax(0,1fr) 300px">
  <section class="card">
    <div class="card-head">
      <div class="ref-label">Concern status</div>
      <span class="badge {{ $badge[1] }}">{{ $badge[0] }}</span>
    </div>
    <div class="status-ref">{{ $concern->reference }}</div>
    <h2 style="margin:0">Review progress</h2>
    <div class="progress">
      @foreach($stages as $i => $s)
        @php
            $done = $i <= $last;
            $cls = $allDone ? ($done ? 'done' : '') : ($i < $last ? 'done' : ($i === $last ? 'current' : ''));
        @endphp
        <div class="p-step {{ $cls }}">
          <div class="p-dot">{{ $cls === 'done' ? '✓' : '' }}</div>
          <h5>{{ $s['name'] }}</h5>
          @if($done)
            <p>{{ $s['done'] }}</p><time>{{ $fmt($s['at']) }}</time>
          @else
            <p>{{ $s['wait'] }}</p><span class="pend">Pending{{ $i === 3 ? ' staff review' : '' }}</span>
          @endif
        </div>
      @endforeach
    </div>
    <div class="status-box"><b>{{ $summary[0] }}</b><p>{{ $summary[1] }}</p></div>
    <div class="legal"><b>An ordinance match is not a final legal decision.</b> It is a suggestion, not legal advice or a promise of resolution. Authorized municipal staff review the match before any next step.</div>
    <div class="updated">Last updated: {{ $fmt($stages[$last]['at']) }}</div>
  </section>
  <aside class="side">
    <div class="card sub-card">
      <h2>What you submitted</h2>
      <div class="q">“{{ $concern->description }}”</div>
      <div class="meta">{{ $concern->barangay }}{{ $concern->location ? ' • '.$concern->location : '' }}</div>
      <div class="chips">
        @foreach($concern->categoryKeys() as $k)<span class="chip {{ $cats[$k]['cls'] }}">{{ $cats[$k]['label'] }}</span>@endforeach
      </div>
      <div class="meta">Photo: {{ $concern->photo_name ?: 'None attached' }}</div>
      <div class="foot">Submitted {{ $fmt($concern->submitted_at) }}</div>
    </div>
    <div class="card handy">
      <h3>Keep your reference handy</h3>
      <p>Check back for updates. When asking municipal staff about this concern, quote {{ $concern->reference }}.</p>
    </div>
  </aside>
</div>
@elseif($ref !== '')
<div class="card not-found"><h2>We couldn't find that reference number</h2>
  <p>No concern of yours matches “{{ $ref }}”. Check for typos and make sure you entered the full number from your receipt.</p></div>
@endif

@if($mine->isNotEmpty())
<section class="card mine">
  <h2>Your recent concerns</h2>
  @foreach($mine as $m)
    <a class="row" href="{{ route('concerns.status', ['ref' => $m->reference]) }}">
      <span><b>{{ $m->reference }}</b> <span class="muted">• {{ $m->barangay }}</span></span>
      <span class="muted">{{ $m->submitted_at->format('j M Y') }}</span>
    </a>
  @endforeach
</section>
@endif

<div class="actions no-print" style="margin-top:24px">
  <a class="btn" href="{{ route('concerns.create') }}">Submit another concern</a>
  <button class="btn" type="button" onclick="window.print()">Print status</button>
</div>
@endsection
