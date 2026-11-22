{{-- A concern with no suggested ordinance: link one by hand, flag a new ordinance, or mark it unmatched. --}}
@extends('layouts.staff')
@section('title', 'No Ordinance Found')
@section('crumb', 'No ordinance found')

@section('content')
@php
    $cats = config('concerns.categories');
    [$label, $cls] = $concern->staffStatus();
@endphp
<a class="back" href="{{ route('staff.concerns.index') }}">← All concerns</a>

<section class="card mb">
  <div class="row between">
    <div><div class="eyebrow">Concern reference</div><h1 class="refnum">{{ $concern->reference }}</h1></div>
    <div class="row gap"><span class="badge {{ $cls }}">{{ $label }}</span></div>
  </div>
  <p class="quote">“{{ $concern->description }}”</p>
  <div class="meta-row"><span>Submitted {{ $concern->submitted_at->format('j M Y, h:i A') }}</span><span>•</span><span>{{ $concern->barangay }}{{ $concern->location ? ' • '.$concern->location : '' }}</span></div>
  <div class="chips">@foreach($concern->categoryKeys() as $k)<span class="chip {{ $cats[$k]['cls'] }}">{{ $cats[$k]['label'] }}</span>@endforeach</div>
</section>

<section class="card mb">
  <h2>No ordinance found</h2>
  <p class="muted">None of the ordinances on file matched the category or words in this concern. That can mean the topic is not covered yet, or the citizen used different words.</p>

  @if($concern->reviewed_at)
    <div class="callout green mt"><b>{{ $concern->decision === 'unmatched' ? 'Marked as unmatched' : 'Ordinance linked manually' }}</b>
      {{ $concern->matchedOrdinance ? $concern->matchedOrdinance->title.' • ' : '' }}{{ $concern->reviewed_at->format('j M Y, h:i A') }}</div>
  @endif
  @if($concern->suggest_new)
    <div class="callout mt"><b>Suggested for a new ordinance</b>This concern is flagged for the Sangguniang Bayan.</div>
  @endif

  <div class="callout amber mt"><b>Recommended next steps</b>
    Read the concern again and check the ordinance list for a related topic. If nothing applies, flag it as a possible new ordinance or mark it as unmatched.</div>

  <div class="row mt">
    <form class="inline-form" method="POST" action="{{ route('staff.concerns.suggest', $concern) }}">@csrf<button class="btn primary" type="submit">Suggest new ordinance</button></form>
    <button class="btn outline" type="button" data-open="link">Manually link ordinance</button>
    <form class="inline-form" method="POST" action="{{ route('staff.concerns.unmatched', $concern) }}">@csrf<button class="btn" type="submit">Mark as unmatched</button></form>
  </div>
</section>

<section class="card"><h2>Activity</h2><div class="mt">@include('partials.timeline', ['concern' => $concern])</div></section>
@endsection

@push('modals')
{{-- Manual link pop-up --}}
<div class="modal-back {{ $errors->hasAny(['ordinance_id', 'reason']) ? 'open' : '' }}" id="link" role="dialog" aria-modal="true" aria-labelledby="lTitle">
  <form class="modal" method="POST" action="{{ route('staff.concerns.link', $concern) }}">
    @csrf
    <div class="modal-head"><h2 id="lTitle">Manually link ordinance</h2><button type="button" data-close aria-label="Close">×</button></div>
    <p class="muted">Pick the ordinance that staff believe applies to this concern.</p>
    <label for="lOrd">Ordinance</label>
    <select id="lOrd" name="ordinance_id">@foreach($ordinances as $o)<option value="{{ $o->id }}">{{ $o->title }}</option>@endforeach</select>
    <label for="lWhy">Reason (optional)</label>
    <textarea id="lWhy" name="reason" maxlength="500" placeholder="Why does this ordinance apply?">{{ old('reason') }}</textarea>
    <div class="modal-actions">
      <button class="btn" type="button" data-close>Cancel</button>
      <button class="btn primary" type="submit">Link ordinance</button>
    </div>
  </form>
</div>
@endpush
