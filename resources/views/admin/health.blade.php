@extends('layouts.admin')
@section('title','System Health')
@section('heading','System health')
@section('lead','Prototype infrastructure status and recent system activity.')
@section('content')
<div class="card-head"><span class="muted small">Last refreshed: {{ $lastChecked->timezone('Asia/Manila')->format('j M Y, h:i:s A') }}</span><label class="check"><input type="checkbox" id="autoRefresh"> Auto-refresh</label></div>
<div class="grid-2">
  <section class="card">
    <h2>Available storage</h2><p class="muted small">Simulated data for the prototype.</p>
    <div class="act"><b>{{ number_format($usedStorage/1048576/1024,2) }} GB used of {{ number_format($totalStorage/1048576/1024,2) }} GB</b>
      <div class="password-meter"><span style="width:{{ min(100,$storagePercent) }}%;background:{{ $storagePercent>=90?'#b42318':($storagePercent>=80?'#8a5a00':'#1f7a3d') }}"></span></div>
      <div class="muted small">{{ number_format($freeStorage/1048576/1024,2) }} GB free</div>
    </div>
    <div class="top-rule"><b>Total photo storage used:</b> {{ number_format($photoUsed/1048576,1) }} MB</div>
  </section>
  <section class="card">
    <h2>Uptime</h2><p class="muted small">Simulated data for the prototype.</p>
    <div class="act"><div class="num">{{ number_format($uptime,3) }}%</div><div class="muted">Last 30 days</div></div>
    <div class="top-rule"><b>Current uptime:</b> {{ $uptimeStarted->diffForHumans(null,true) }}</div>
  </section>
  <section class="card">
    <h2>Database connectivity</h2><p class="muted small">Simulated data for the prototype.</p>
    <div class="act"><span class="pill {{ $dbStatus==='Connected'?'green':'grey' }}">{{ $dbStatus }}</span><div class="muted small mt">Response time: {{ $dbMs }} ms</div><div class="muted small">Last checked: {{ $lastChecked->format('h:i:s A') }}</div></div>
  </section>
  <section class="card">
    <h2>Errors</h2><p class="muted small">Simulated data for the prototype.</p>
    <div class="grid-2 act"><div><div class="num">{{ $errors24h }}</div><div class="muted small">Last 24 hours</div></div><div><div class="num">{{ $errors7d }}</div><div class="muted small">Last 7 days</div></div></div>
  </section>
</div>
<section class="card">
  <h2>Downtime incidents</h2><table class="table responsive-table" data-sortable><caption>Downtime incidents</caption><thead><tr><th scope="col">Start</th><th scope="col">End</th><th scope="col">Duration</th><th scope="col">Cause</th></tr></thead><tbody>
  @foreach($downtimeIncidents as $incident)<tr><td data-label="Start" data-sort="{{ $incident['start']->toIso8601String() }}">{{ $incident['start']->format('j M Y, h:i A') }}</td><td data-label="End" data-sort="{{ $incident['end']->toIso8601String() }}">{{ $incident['end']->format('j M Y, h:i A') }}</td><td data-label="Duration">{{ $incident['duration'] }}</td><td data-label="Cause">{{ $incident['cause'] }}</td></tr>@endforeach
  </tbody></table>
</section>
<section class="card">
  <div class="card-head"><h2>Recent errors</h2><button type="button" class="btn" id="runDbCheck">Run check</button></div>
  <form class="row-split" method="GET">
    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search error or page" aria-label="Search errors">
    <select name="level" aria-label="Filter by level"><option value="">All levels</option>@foreach(['Info','Warning','Error'] as $level)<option @selected($filters['level']===$level)>{{ $level }}</option>@endforeach</select>
    <button type="submit" class="btn">Filter</button>
  </form>
  <table class="table responsive-table" data-sortable><caption>Recent system errors</caption><thead><tr><th scope="col" data-sort-type="date">Time</th><th scope="col">Level</th><th scope="col">Message</th><th scope="col">Page</th></tr></thead><tbody>
  @forelse($recentErrors as $error)<tr><td data-label="Time" data-sort="{{ $error['time']->toIso8601String() }}">{{ $error['time']->format('j M Y, h:i A') }}</td><td data-label="Level"><span class="pill {{ $error['level']==='Error'?'grey':'grey' }}">{{ $error['level'] }}</span></td><td data-label="Message">{{ $error['message'] }}</td><td data-label="Page">{{ $error['page'] }}</td></tr>@empty<tr><td colspan="4" class="muted empty">No errors match the filters.</td></tr>@endforelse
  </tbody></table>
</section>
@endsection
@push('scripts')
<script>
const run=document.getElementById("runDbCheck");
run.addEventListener("click",()=>{const restore=window.UI.loadingButton(run,"Checking...");setTimeout(()=>{restore();window.UI.toast("Database check completed: {{ $dbStatus }}, {{ $dbMs }} ms.");},600);});
const refresh=document.getElementById("autoRefresh");let timer=null;
refresh.addEventListener("change",()=>{if(refresh.checked){timer=setInterval(()=>location.reload(),30000);window.UI.announce("Auto-refresh enabled.");}else{clearInterval(timer);window.UI.announce("Auto-refresh disabled.");}});
</script>
@endpush
