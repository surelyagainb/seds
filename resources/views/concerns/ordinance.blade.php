@extends('layouts.citizen')
@section('title','Ordinance Detail')
@section('content')
<a class="ordinance-back" href="{{ route('concerns.ordinances') }}"><span aria-hidden="true">←</span> Back to ordinances</a>

<article class="ordinance-document">
  <header class="ordinance-heading">
    <h1>{{ $ordinance->number }}</h1>
    <p>{{ $ordinance->title }}</p>
  </header>

  <div class="ordinance-demo-notice">
    Demo data only. This ordinance is for system demonstration and is not legal advice.
  </div>

  <div class="ordinance-sections">
    @forelse($ordinance->sections ?: [] as $section)
      <section>
        <h2>{{ $section['title'] ?? 'Section '.$section['n'] }}</h2>
        <p>{{ $section['text'] ?? '' }}</p>
      </section>
    @empty
      <section>
        <h2>Ordinance text</h2>
        <p>No text has been added for this ordinance.</p>
      </section>
    @endforelse
  </div>

  <a class="ordinance-download" href="{{ route('concerns.ordinance.download', $ordinance) }}">
    <span aria-hidden="true">↓</span> Download File
  </a>
</article>
@endsection
