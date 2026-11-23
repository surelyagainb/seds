@extends(auth()->user()->isAdmin() ? 'layouts.admin' : (auth()->user()->isSbMember() ? 'layouts.staff' : 'layouts.citizen'))
@section('title','Notifications')
@section('heading','Notifications')
@section('lead','Account and concern updates.')
@section('crumb','Notifications')
@section('content')
<section class="card">
    <div class="card-head"><h1>Notifications</h1><form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button type="submit" class="btn">Mark all as read</button></form></div>
    <form class="row-split mt" method="GET">
      <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search notifications" aria-label="Search notifications">
      <select name="status" aria-label="Filter notification status"><option value="">All notifications</option><option value="unread" @selected($filters['status']==='unread')>Unread</option><option value="read" @selected($filters['status']==='read')>Read</option></select>
      <button type="submit" class="btn">Filter</button>
    </form>
    @forelse($notifications as $notification)
        <article class="top-rule act" style="padding:12px 0">
            <div class="card-head"><div><b>{{ $notification->title }}</b><div class="muted small">{{ $notification->message }}</div></div>
                @if(! $notification->read_at)<span class="pill">Unread</span>@else<span class="muted small">Read</span>@endif
            </div>
            @if(! $notification->read_at)<form method="POST" action="{{ route('notifications.read',$notification) }}" style="margin-top:8px">@csrf<button class="btn small-btn" type="submit">Mark as read</button></form>@endif
        </article>
    @empty
        <p class="muted">No notifications yet.</p>
    @endforelse
    {{ $notifications->links() }}
</section>
@endsection
