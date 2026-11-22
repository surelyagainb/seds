{{-- One ordinance: its text, record details, keywords and the concerns that match it. --}}
@extends('layouts.staff')
@section('title', 'Ordinance Detail')
@section('crumb', 'Ordinance detail')

@section('content')
@php $cats = config('concerns.categories'); @endphp
<a class="back" href="{{ route('staff.ordinances.index') }}">← All ordinances</a>
<div class="page-head">
  <div><div class="eyebrow">{{ $ordinance->number }}</div><h1>{{ $ordinance->title }}</h1></div>
  <div class="row gap"><span class="badge {{ $ordinance->statusClass() }}">{{ $ordinance->status }}</span><span class="badge blue">{{ $ordinance->version }}</span></div>
</div>
@if(str_starts_with($ordinance->number, 'Demo Ordinance'))
  <div class="callout amber mb"><b>Demo data</b>This text is sample content for testing, not an official municipal record.</div>
@endif

<div class="grid-main">
  <section class="card">
    <h2>Ordinance text</h2>
    @foreach($ordinance->sections ?? [] as $s)
      <div class="ord-section"><h3>Section {{ $s['n'] }}. {{ $s['title'] }}</h3><p>{{ $s['text'] }}</p></div>
    @endforeach
  </section>

  <div class="stack">
    <section class="card">
      <h2>Record details</h2>
      <div class="mt">
        <div class="kv"><span>Number</span><b>{{ $ordinance->number }}</b></div>
        <div class="kv"><span>Date enacted</span><b>{{ $ordinance->enacted_on ? $ordinance->enacted_on->format('j M Y') : 'Not yet enacted' }}</b></div>
        <div class="kv"><span>Status</span><b>{{ $ordinance->status }}</b></div>
        <div class="kv"><span>Version</span><b>{{ $ordinance->version }}</b></div>
        <div class="kv"><span>Category</span><b>{{ $cats[$ordinance->category]['label'] ?? $ordinance->category }}</b></div>
      </div>
    </section>

    <section class="card">
      <h2>Linked keywords</h2>
      <div class="chips mt">@foreach($ordinance->keywords as $k)<span class="chip">{{ $k }}</span>@endforeach</div>
    </section>

    <section class="card">
      <h2>Concerns that match</h2>
      <p class="muted small">{{ $linkedCount }} {{ \Illuminate\Support\Str::plural('concern', $linkedCount) }} matched to this ordinance.</p>
      @foreach($linked as $c)
        <div class="kv"><a href="{{ route('staff.concerns.show', $c->reference) }}">{{ $c->reference }}</a><span>{{ $c->barangay }}</span></div>
      @endforeach
    </section>

    <section class="card">
      <h2>Review log</h2>
      <ul class="timeline mt">
        @if($ordinance->enacted_on)<li><b>Enacted as version 1</b><span>{{ $ordinance->enacted_on->format('j M Y') }}</span></li>@endif
        <li><b>Added to the ordinance register</b><span>{{ $ordinance->created_at->format('j M Y') }}</span></li>
      </ul>
    </section>
  </div>
</div>
@endsection
