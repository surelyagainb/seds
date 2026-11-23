@extends('layouts.citizen')
@section('title','Ordinance Detail')
@section('content')
<a class="btn" href="{{ route('concerns.ordinances') }}">← Back to ordinances</a>
<article class="card" style="margin-top:12px">
  <h1>{{ $ordinance->number }}</h1>
  <h2>{{ $ordinance->title }}</h2>
  <p class="notice">Demo data only. This ordinance is for system demonstration and is not legal advice.</p>
  @foreach($ordinance->sections ?: [] as $section)
    <section class="top-rule"><h3>{{ $section['title'] ?? 'Section '.$section['n'] }}</h3><p>{{ $section['text'] ?? '' }}</p></section>
  @endforeach
</article>
@endsection
