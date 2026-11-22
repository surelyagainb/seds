{{-- All concerns with search, status, barangay and category filters. --}}
@extends('layouts.staff')
@section('title', 'Concerns')
@section('crumb', 'Concerns')

@section('content')
@php $cats = config('concerns.categories'); @endphp
<div class="page-head">
  <div>
    <h1>Concerns</h1>
    <p>Every concern sent by citizens, including new ones from the public form.</p>
  </div>
  <div class="muted small">{{ $concerns->total() }} of {{ $total }} concerns</div>
</div>

<form class="filters card" method="GET" action="{{ route('staff.concerns.index') }}">
  <div><label for="fq">Search</label><input type="search" id="fq" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Reference, keyword or barangay"></div>
  <div><label for="fc">Category</label>
    <select id="fc" name="category"><option value="">All categories</option>
      @foreach($cats as $k => $c)<option value="{{ $k }}" @selected(($filters['category'] ?? '') === $k)>{{ $c['label'] }}</option>@endforeach
    </select></div>
  <div><label for="fs">Status</label>
    <select id="fs" name="status"><option value="">All statuses</option>
      @foreach(['Needs review', 'Reviewed', 'Rejected', 'Unmatched', 'No ordinance found'] as $s)<option @selected(($filters['status'] ?? '') === $s)>{{ $s }}</option>@endforeach
    </select></div>
  <div><label for="fb">Barangay</label>
    <select id="fb" name="barangay"><option value="">All barangays</option>
      @foreach(config('concerns.barangays') as $b)<option value="{{ $b }}" @selected(($filters['barangay'] ?? '') === $b)>{{ $b }}</option>@endforeach
    </select></div>
  <div class="btns"><button class="btn primary" type="submit">Filter</button><a class="btn" href="{{ route('staff.concerns.index') }}">Reset</a></div>
</form>

<section class="card flush">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Reference</th><th>Concern</th><th>Barangay</th><th>Categories</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
      <tbody>
      @forelse($concerns as $c)
        @php [$label, $cls] = $c->staffStatus(); @endphp
        <tr>
          <td><b>{{ $c->reference }}</b></td>
          <td>{{ \Illuminate\Support\Str::limit($c->description, 110) }}</td>
          <td>{{ $c->barangay }}</td>
          <td><div class="chips">@foreach($c->categoryKeys() as $k)<span class="chip {{ $cats[$k]['cls'] }}">{{ $cats[$k]['label'] }}</span>@endforeach</div></td>
          <td><span class="badge {{ $cls }}">{{ $label }}</span></td>
          <td>{{ $c->submitted_at->format('j M Y') }}</td>
          <td><a href="{{ route('staff.concerns.show', $c) }}">View</a></td>
        </tr>
      @empty
        <tr><td colspan="7" class="empty">No concerns match your filters.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.pager', ['p' => $concerns])
</section>
@endsection
