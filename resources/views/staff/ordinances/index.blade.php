{{-- Ordinance register: search, filters, the list, and a pop-up form to add a new ordinance. --}}
@extends('layouts.staff')
@section('title', 'Ordinance Management')
@section('crumb', 'Ordinance management')

@section('content')
@php $cats = config('concerns.categories'); @endphp
<div class="page-head">
  <div>
    <h1>Ordinance management</h1>
    <p>Keep the ordinance register and the keywords used for matching up to date.</p>
  </div>
  <div class="row gap"><button class="btn primary" type="button" data-open="newOrd">+ New ordinance</button></div>
</div>

@if(\App\Models\Ordinance::where('number', 'like', 'Demo Ordinance%')->exists())
  <div class="callout amber mb"><b>Demo data</b>Ordinances named "Demo Ordinance" are sample text for testing. They are not official records of the municipality.</div>
@endif

<form class="filters card" method="GET" action="{{ route('staff.ordinances.index') }}">
  <div><label for="fq">Search</label><input type="search" id="fq" name="q" value="{{ $filters['q'] }}" placeholder="Number or title"></div>
  <div><label for="fc">Category</label>
    <select id="fc" name="category"><option value="">All categories</option>
      @foreach($cats as $k => $c)<option value="{{ $k }}" @selected($filters['category'] === $k)>{{ $c['label'] }}</option>@endforeach
    </select></div>
  <div><label for="fs">Status</label>
    <select id="fs" name="status"><option value="">All statuses</option>
      @foreach(\App\Models\Ordinance::STATUSES as $s)<option @selected($filters['status'] === $s)>{{ $s }}</option>@endforeach
    </select></div>
  <div class="btns"><button class="btn primary" type="submit">Filter</button><a class="btn" href="{{ route('staff.ordinances.index') }}">Reset</a></div>
</form>

<section class="card flush mb">
  <div style="padding:16px 18px 6px"><h2>Ordinance register</h2><p class="muted small">Showing {{ $ordinances->count() }} of {{ $all }} ordinances</p></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Ordinance no.</th><th>Title</th><th>Category</th><th>Status</th><th>Version</th><th></th></tr></thead>
      <tbody>
      @forelse($ordinances as $o)
        <tr><td><b>{{ $o->number }}</b></td><td>{{ $o->title }}</td>
          <td><div class="chips"><span class="chip {{ $cats[$o->category]['cls'] ?? 'other' }}">{{ $cats[$o->category]['label'] ?? $o->category }}</span></div></td>
          <td><span class="badge {{ $o->statusClass() }}">{{ $o->status }}</span></td><td>{{ $o->version }}</td>
          <td><a href="{{ route('staff.ordinances.show', $o) }}">View</a></td></tr>
      @empty
        <tr><td colspan="6" class="empty">No ordinances match your filters.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="card">
  <h2>Keep text and keywords connected</h2>
  <p class="muted">Concerns are matched to ordinances using their category and these keywords. Add them when you create an ordinance. "Draft" ordinances are not used for matching.</p>
  <div class="chips">@foreach($keywords as $k)<span class="chip">{{ $k }}</span>@endforeach</div>
</section>
@endsection

@push('modals')
{{-- New ordinance pop-up (reopens by itself if something was not accepted) --}}
<div class="modal-back {{ $errors->hasAny(['number', 'title', 'category', 'status', 'keywords', 'text']) ? 'open' : '' }}" id="newOrd" role="dialog" aria-modal="true" aria-labelledby="nTitle">
  <form class="modal" method="POST" action="{{ route('staff.ordinances.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="modal-head"><h2 id="nTitle">New ordinance</h2><button type="button" data-close aria-label="Close">×</button></div>
    <label for="nNo">Ordinance number *</label><input type="text" id="nNo" name="number" value="{{ old('number') }}" maxlength="40" placeholder="e.g. Ordinance No. 2026-003">
    <label for="nTitleIn">Title *</label><input type="text" id="nTitleIn" name="title" value="{{ old('title') }}" maxlength="200" placeholder="e.g. Noise Control Ordinance">
    <label for="nCat">Category</label>
    <select id="nCat" name="category">@foreach($cats as $k => $c)<option value="{{ $k }}" @selected(old('category') === $k)>{{ $c['label'] }}</option>@endforeach</select>
    <label for="nStatus">Status</label>
    <select id="nStatus" name="status">@foreach(\App\Models\Ordinance::STATUSES as $s)<option @selected(old('status') === $s)>{{ $s }}</option>@endforeach</select>
    <label for="nKw">Keywords *</label><input type="text" id="nKw" name="keywords" value="{{ old('keywords') }}" maxlength="500" placeholder="Separate with commas, e.g. noise, karaoke, loud">
    <label for="nText">Main text</label><textarea id="nText" name="text" maxlength="10000" placeholder="Paste the main section of the ordinance">{{ old('text') }}</textarea>
    <label for="nFile">Ordinance PDF</label><input type="file" id="nFile" name="file" accept=".pdf,application/pdf">
    <div class="modal-actions">
      <button class="btn" type="button" data-close>Cancel</button>
      <button class="btn primary" type="submit">Save ordinance</button>
    </div>
  </form>
</div>
@endpush
