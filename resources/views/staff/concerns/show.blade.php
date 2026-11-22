{{-- One concern with candidate ordinances: compare the text, then confirm, reject or reassign. --}}
@extends('layouts.staff')
@section('title', 'Concern Detail')
@section('crumb', 'Concern detail')

@section('content')
@php
    $cats = config('concerns.categories');
    [$label, $cls] = $concern->staffStatus();
    $sel = $selected['match'];            // the selected suggestion (score, ordinance)
    $hits = $selected['hits'];            // keywords found in the concern
    $decided = in_array($concern->decision, ['confirmed', 'rejected'], true);
@endphp
<a class="back" href="{{ route('staff.concerns.index') }}">← All concerns</a>

<section class="card mb">
  <div class="row between">
    <div><div class="eyebrow">Concern reference</div><h1 class="refnum">{{ $concern->reference }}</h1></div>
    <div class="row gap"><span class="badge {{ $cls }}">{{ $label }}</span><span class="badge blue">Auto-matched</span></div>
  </div>
  <p class="quote">“{{ $concern->description }}”</p>
  <div class="meta-row"><span>Submitted {{ $concern->submitted_at->format('j M Y, h:i A') }}</span><span>•</span>
    <span>{{ $concern->barangay }}{{ $concern->location ? ' • '.$concern->location : '' }}</span>
    @if($concern->assigned_to)<span>•</span><span>Assigned to {{ $concern->assigned_to }}</span>@endif</div>
  <div class="chips">@foreach($concern->categoryKeys() as $k)<span class="chip {{ $cats[$k]['cls'] }}">{{ $cats[$k]['label'] }}</span>@endforeach</div>
</section>

<div class="grid-split">
  {{-- Candidate ordinances: select one to compare --}}
  <section class="card">
    <h2>Candidate ordinances</h2>
    <p class="muted small">Select one to compare it with the concern.</p>
    <div class="mt">
      @foreach($cands as $c)
        @php $m = $c['match']; @endphp
        <a class="cand {{ $m->ordinance_id === $sel->ordinance_id ? 'selected' : '' }}" href="{{ route('staff.concerns.show', [$concern, 'o' => $m->ordinance_id]) }}">
          <h3>{{ $m->ordinance->title }}</h3>
          <div class="muted small">{{ $m->ordinance->number }}</div>
          <div class="pct"><div class="bar"><i style="width:{{ $m->percent }}%"></i></div>{{ $m->percent }}% • {{ ucfirst($m->confidence) }}</div>
          <div class="chips">@foreach($c['hits'] as $h)<span class="chip">{{ $h }}</span>@endforeach</div>
        </a>
      @endforeach
    </div>
  </section>

  <div class="stack">
    <section class="card">
      <h2>Compare the relevant section</h2>
      <div class="eyebrow mt">Citizen's concern</div>
      <div class="section-text">{!! \App\Support\Highlight::words($concern->description, $hits) !!}</div>
      @if($section)
        <div class="eyebrow">Section {{ $section['n'] }}: {{ $section['title'] }} • {{ $sel->ordinance->title }}</div>
        <div class="section-text">{!! \App\Support\Highlight::words($section['text'], $hits) !!}</div>
      @endif
      <div class="callout"><b>Why this match was suggested</b>
        @foreach($sel->reasons as $r){{ $r }}. @endforeach
        This is a rule-based match (category + keywords, score {{ $sel->score }}), so staff should read the section and decide whether it really fits.</div>
    </section>

    <section class="card">
      <h2>Staff decision</h2>
      @if($decided)
        <div class="callout {{ $concern->decision === 'confirmed' ? 'green' : 'amber' }} mt"><b>{{ $concern->decision === 'confirmed' ? 'Match confirmed' : 'Match rejected' }}</b>
          {{ $concern->matchedOrdinance ? $concern->matchedOrdinance->title.' • ' : '' }}{{ $concern->reviewed_at->format('j M Y, h:i A') }}. You can still change the decision below.</div>
      @endif
      <form method="POST" action="{{ route('staff.concerns.decide', $concern) }}">
        @csrf
        <input type="hidden" name="ordinance_id" value="{{ $sel->ordinance_id }}">
        <label for="note">Notes (optional)</label>
        <textarea id="note" name="note" maxlength="500" placeholder="Add a note for the activity log">{{ old('note') }}</textarea>
        <div class="row mt">
          <button class="btn primary" type="submit" name="action" value="confirm">Confirm match</button>
          <button class="btn danger" type="submit" name="action" value="reject">Reject</button>
          <button class="btn outline" type="button" data-open="reassign">Reassign</button>
        </div>
      </form>
    </section>
  </div>
</div>

<section class="card mt"><h2>Activity</h2><div class="mt">@include('partials.timeline', ['concern' => $concern])</div></section>
@endsection

@push('modals')
{{-- Reassign pop-up --}}
<div class="modal-back {{ $errors->hasAny(['assigned_to', 'reason']) ? 'open' : '' }}" id="reassign" role="dialog" aria-modal="true" aria-labelledby="rTitle">
  <form class="modal" method="POST" action="{{ route('staff.concerns.reassign', $concern) }}">
    @csrf
    <div class="modal-head"><h2 id="rTitle">Reassign concern</h2><button type="button" data-close aria-label="Close">×</button></div>
    <div class="callout"><b>Current assignment</b>{{ $concern->assigned_to ?: 'Not yet assigned (Municipal Planning and Development Office handles it by default).' }}</div>

    <label for="rTo">Assign to</label>
    <select id="rTo" name="assigned_to">@foreach($offices as $o)<option @selected(old('assigned_to') === $o)>{{ $o }}</option>@endforeach</select>

    <label for="rWhy">Reason for reassignment *</label>
    <textarea id="rWhy" name="reason" maxlength="500" placeholder="Why should this concern go to another office?">{{ old('reason') }}</textarea>

    <div class="callout mt" style="font-size:11px">The new office will see this reason in the activity log. The citizen's status page does not change.</div>
    <div class="modal-actions">
      <button class="btn" type="button" data-close>Cancel</button>
      <button class="btn primary" type="submit">Confirm reassignment</button>
    </div>
  </form>
</div>
@endpush
