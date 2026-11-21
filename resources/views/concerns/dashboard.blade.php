@extends('layouts.citizen')
@section('title', 'My Dashboard')

@section('content')
@php $cats = config('concerns.categories'); @endphp
<h1>My Dashboard</h1>
<p class="lead">A quick look at the concerns you have submitted.</p>

<div class="stats">
  <div class="card stat"><div class="num">{{ $stats['total'] }}</div><div class="lbl">My Concerns</div></div>
  <div class="card stat"><div class="num">{{ $stats['under_review'] }}</div><div class="lbl">Under Review</div></div>
  <div class="card stat"><div class="num">{{ $stats['reviewed'] }}</div><div class="lbl">Reviewed</div></div>
  <div class="card stat"><div class="num">{{ $stats['resolved'] }}</div><div class="lbl">Resolved</div></div>
</div>

<section class="card">
  <div class="card-head">
    <h2>My Recent Submissions</h2>
    <a class="btn primary" href="{{ route('concerns.create') }}" style="padding:8px 14px;font-size:12px">+ Submit a concern</a>
  </div>

  @if($recent->isEmpty())
    <p class="sub" style="margin:14px 0 0">You haven't submitted a concern yet. Use "Submit a concern" to report one.</p>
  @else
    <div class="table-wrap">
      <table class="dtable">
        <thead><tr><th>Reference</th><th>Category</th><th>Status</th></tr></thead>
        <tbody>
        @foreach($recent as $c)
          <tr>
            <td><a class="ref-link" href="{{ route('concerns.status', ['ref' => $c->reference]) }}">{{ $c->reference }}</a></td>
            <td>
              <div class="chips">
                @foreach($c->categoryKeys() as $k)<span class="chip {{ $cats[$k]['cls'] }}">{{ $cats[$k]['label'] }}</span>@endforeach
              </div>
            </td>
            <td><span class="badge {{ $c->statusClass() }}">{{ $c->statusLabel() }}</span></td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
    <div class="hint" style="margin-top:12px">Select a reference number to see its full progress.</div>
  @endif
</section>
@endsection
