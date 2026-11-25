{{-- Shared frame for every SB member (staff) page: sidebar on the left, top bar, page content. --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title') – Staff – Municipality of Victoria</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/staff.css') }}">
<link rel="stylesheet" href="{{ asset('css/base.css') }}">
<script src="{{ asset('js/ui.js') }}" defer></script>
@stack('head')
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
@php
    $nav = [
        ['staff.dashboard', 'Dashboard', 'staff.dashboard'],
        ['staff.concerns.index', 'Concerns', 'staff.concerns.*'],
        ['staff.recurring', 'Recurring issues', 'staff.recurring*'],
        ['staff.ordinances.index', 'Ordinances', 'staff.ordinances.*'],
        ['staff.categories.index', 'Categories', 'staff.categories.*'],
        ['staff.reports', 'Reports', 'staff.reports*'],
    ];
    $u = auth()->user();
@endphp
<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="side-brand">
      @include('partials.seal')
      <span class="brand-copy">
        <span class="brand-title">Municipality of Victoria</span>
        <span class="brand-sub">ORIENTAL MINDORO, PHILIPPINES</span>
      </span>
    </div>
    <nav class="side-nav" aria-label="Staff navigation">
      @foreach($nav as [$route, $label, $pattern])
        <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}">{{ $label }}</a>
      @endforeach
    </nav>
    @if($u->isSbMember())
      <div class="muted small" style="padding:0 16px">
        <b>Assigned categories</b><br>
        {{ collect($u->assigned_categories ?: [])->map(fn($key)=>config('concerns.categories.'.$key.'.label',$key))->join(', ') ?: 'No categories assigned yet' }}
      </div>
    @endif

  </aside>

  <div class="content">
    <header class="topbar">
      <div><button class="menu-btn" id="menuBtn" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="sidebar">☰</button>Victoria / Staff / <b>@yield('crumb')</b></div>
      <div class="topbar-actions">
        @include('partials.notification-menu')
        <details class="account-menu-wrap">
          <summary class="account-menu-btn" aria-label="Open account menu"><span></span><span></span><span></span></summary>
          <div class="account-menu">
            <div class="account-menu-name">{{ $u->name }}</div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form>
          </div>
        </details>
      </div>
    </header>

    <main class="page" id="main-content">
      @if(session('status'))<div class="callout green flash" role="status">{{ session('status') }}</div>@endif
      @if($errors->any())
        <div class="callout amber flash" role="alert"><b>Please check the following</b>
          <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
      @endif

      @yield('content')
    </main>
  </div>
</div>

@stack('modals')
<script src="{{ asset('js/staff.js') }}"></script>
@stack('scripts')
</body>
</html>