{{-- Shared frame for every SB member (staff) page: sidebar on the left, top bar, page content. --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title') – Staff – Victoria, Oriental Mindoro</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/staff.css') }}">
@stack('head')
</head>
<body>
@php
    // Sidebar links: [route name, text, pattern used to highlight the current page]
    $nav = [
        ['staff.dashboard', 'Dashboard', 'staff.dashboard'],
        ['staff.concerns.index', 'Concerns', 'staff.concerns.*'],
        ['staff.recurring', 'Recurring issues', 'staff.recurring*'],
        ['staff.ordinances.index', 'Ordinances', 'staff.ordinances.*'],
        ['staff.barangays.index', 'Barangay directory', 'staff.barangays.*'],
    ];
@endphp
<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="side-brand">@include('partials.seal', ['size' => 34])<span>Victoria,<br>Oriental Mindoro</span></div>
    <nav class="side-nav" aria-label="Staff navigation">
      @foreach($nav as [$route, $label, $pattern])
        <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}">{{ $label }}</a>
      @endforeach
    </nav>
  </aside>

  <div class="content">
    <header class="topbar">
      <div><button class="menu-btn" id="menuBtn" type="button" aria-label="Open menu">☰</button>Victoria / Staff / <b>@yield('crumb')</b></div>
      <div>
        {{ auth()->user()->name }} •
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="link-btn" type="submit">Sign out</button></form>
      </div>
    </header>

    <main class="page">
      {{-- Green message after an action worked; red list when something was not accepted --}}
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
