@php
    $notificationUser = auth()->user();
    $unreadNotifications = $notificationUser->notifications()->whereNull('read_at')->count();
    $recentNotifications = $notificationUser->notifications()->latest()->limit(7)->get();
@endphp
<details class="notification-menu-wrap">
  <summary class="notification-bell" aria-label="Open notifications">
    <img src="{{ asset('images/bellLogo.jpg') }}" alt="" class="bell-logo">
    @if($unreadNotifications)<span class="notification-count">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>@endif
  </summary>
  <section class="notification-panel" aria-label="Recent notifications">
    <h2>Notifications</h2>
    <div class="notification-list">
      @forelse($recentNotifications as $notification)
        <article class="notification-item {{ $notification->read_at ? '' : 'unread' }}">
          <div class="notification-item-copy">
            <strong>{{ $notification->title }}</strong>
            @if($notification->message)<p>{{ $notification->message }}</p>@endif
            <time datetime="{{ $notification->created_at->toISOString() }}">{{ $notification->created_at->diffForHumans() }}</time>
          </div>
          <a href="{{ route('notifications.index') }}">View full notification</a>
        </article>
      @empty
        <p class="notification-empty">No notifications yet.</p>
      @endforelse
    </div>
    <a class="notification-see-all" href="{{ route('notifications.index') }}">See all</a>
  </section>
</details>
