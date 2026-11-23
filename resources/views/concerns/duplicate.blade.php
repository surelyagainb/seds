@extends('layouts.citizen')
@section('title','Similar Concern')
@section('content')
<section class="card" role="dialog" aria-modal="true" aria-labelledby="duplicateTitle">
  <h1 id="duplicateTitle">A similar concern was already reported</h1>
  <p class="lead">An open concern in the same area may describe the same problem.</p>
  <div class="status-box">
    <b>{{ $concern->reference }}</b>
    <p>{{ $concern->barangay }} • {{ $concern->description }}</p>
  </div>
  <form method="POST" action="{{ route('concerns.store') }}" class="actions">
    @csrf
    <input type="hidden" name="agree" value="1">
    <input type="hidden" name="duplicate_confirmed" value="1">
    <input type="hidden" name="duplicate_concern_id" value="{{ $concern->id }}">
    <button type="submit" class="btn primary">This is the same problem (add my report)</button>
  </form>
  <form method="POST" action="{{ route('concerns.store') }}" class="actions" style="margin-top:10px">
    @csrf
    <input type="hidden" name="agree" value="1">
    <input type="hidden" name="duplicate_different" value="1">
    <button type="submit" class="btn">No, this is different</button>
  </form>
</section>
@endsection
