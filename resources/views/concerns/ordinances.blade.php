@extends('layouts.citizen')
@section('title','Ordinances')
@section('content')
@php $cats=config('concerns.categories'); @endphp
<h1>Ordinances</h1>
<p class="lead">View-only demo ordinances. These records are not legal advice.</p>
<form class="card filters" method="GET">
  <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search ordinance number or title">
  <select name="category"><option value="">All categories</option>@foreach($cats as $key=>$cat)<option value="{{ $key }}" @selected($filters['category']===$key)>{{ $cat['label'] }}</option>@endforeach</select>
  <button type="submit" class="btn primary">Search</button>
</form>
<section class="card">
  <table class="dtable responsive-table" data-sortable>
    <caption>Demo ordinances</caption>
    <thead><tr><th scope="col">Number</th><th scope="col">Title</th><th scope="col">Category</th></tr></thead>
    <tbody>
    @forelse($ordinances as $ordinance)
      <tr><td data-label="Number"><a class="ref-link" href="{{ route('concerns.ordinance',$ordinance) }}">{{ $ordinance->number }}</a></td><td data-label="Title">{{ $ordinance->title }}</td><td data-label="Category">{{ $cats[$ordinance->category]['label'] ?? $ordinance->category }}</td></tr>
    @empty
      <tr><td colspan="3" class="empty">No demo ordinances match your search.</td></tr>
    @endforelse
    </tbody>
  </table>
  {{ $ordinances->links() }}
</section>
@endsection
