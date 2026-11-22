{{-- Activity log of a concern, newest first. Usage: @include('partials.timeline', ['concern' => $concern]) --}}
@php
    $events = collect([['t' => $concern->submitted_at, 'text' => 'Concern submitted by citizen']]);
    if ($concern->matched_at) $events->push(['t' => $concern->matched_at, 'text' => 'Concern analyzed and matched to candidate ordinances automatically']);
    foreach ($concern->logs as $l) $events->push(['t' => $l->created_at, 'text' => $l->text.($l->user ? ' (by '.$l->user->name.')' : '')]);
    $events = $events->sortByDesc('t');
@endphp
<ul class="timeline">
    @foreach($events as $e)
        <li><b>{{ $e['text'] }}</b><span>{{ $e['t']->format('j M Y, h:i A') }}</span></li>
    @endforeach
</ul>
