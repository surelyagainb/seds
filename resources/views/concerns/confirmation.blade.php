@extends('layouts.citizen')
@section('title', 'Concern Received')

@section('content')
@php $cats = config('concerns.categories'); @endphp
<h1>Your concern has been received.</h1>
<p class="lead">Thank you for helping improve your community. Save your reference number to check the review status.</p>

<section class="card ref-card">
  <div class="ref-top">
    <div class="tick">✓</div>
    <div><div class="ref-label">Your reference number</div><div class="ref-num">{{ $concern->reference }}</div></div>
  </div>
  <span class="stamp">Received • {{ $concern->submitted_at->format('j M Y, h:i A') }}</span>
  <div class="steps">
    <div class="step active"><b>● Received →</b><small>Active • Received {{ $concern->submitted_at->isToday() ? 'today' : $concern->submitted_at->format('j M Y') }}</small></div>
    <div class="step"><b>○ Analyzed →</b><small>Pending</small></div>
    <div class="step"><b>○ Matched →</b><small>Pending</small></div>
    <div class="step"><b>○ Reviewed</b><small>Pending</small></div>
  </div>
  <p class="hint" style="margin-top:12px">Received means your report is safely recorded. Analysis, matching and staff review have not started yet.</p>
</section>

<div class="two">
  <section class="card">
    <h2>What you submitted</h2>
    <div class="quote" style="border:0;padding:0;font-size:17px;margin:12px 0 8px">“{{ $concern->description }}”</div>
    <div class="meta">{{ $concern->barangay }}{{ $concern->location ? ' • '.$concern->location : '' }}</div>
    <div class="chips">
      @foreach($concern->categoryKeys() as $k)<span class="chip {{ $cats[$k]['cls'] }}">{{ $cats[$k]['label'] }}</span>@endforeach
    </div>
    <div class="meta" style="margin-top:12px">Photo: {{ $concern->photo_name ?: 'None attached' }}</div>
  </section>
  <section class="card dark-card">
    <h2>Keep your reference handy</h2>
    <p>Use <b>{{ $concern->reference }}</b> when asking about your concern. A match suggests a relevant ordinance; municipal staff make the final review.</p>
    <div class="actions no-print">
      <button class="btn primary" id="copy" type="button">Copy reference</button>
      <button class="btn ghost" type="button" onclick="window.print()">Print receipt</button>
    </div>
  </section>
</div>

<div class="actions no-print">
  <a class="btn" href="{{ route('concerns.create') }}">Submit another concern</a>
  <a class="btn" href="{{ route('concerns.status', ['ref' => $concern->reference]) }}">Check concern status</a>
</div>
@endsection

@push('scripts')
<script>
document.getElementById("copy").onclick = async e => {
  try { await navigator.clipboard.writeText(@json($concern->reference)); } catch (_) {}
  e.target.textContent = "Copied ✓";
  setTimeout(() => e.target.textContent = "Copy reference", 1800);
};
</script>
@endpush
