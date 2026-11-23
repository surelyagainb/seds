<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') – Municipality of Victoria</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/citizen.css') }}">
<link rel="stylesheet" href="{{ asset('css/base.css') }}">
<script src="{{ asset('js/ui.js') }}" defer></script>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<div class="shell">
  <button class="mobile-menu-btn" id="citizenMenuBtn" type="button" aria-expanded="false" aria-controls="citizenSidebar" aria-label="Open navigation">☰</button>
  <aside class="sidebar" id="citizenSidebar">
    <a class="side-brand" href="{{ route('citizen.dashboard') }}">
      @include('partials.seal')
      <span class="brand-copy">
        <span class="brand-title">Municipality of Victoria</span>
        <span class="brand-sub">ORIENTAL MINDORO, PHILIPPINES</span>
      </span>
    </a>

    <nav class="nav" aria-label="Citizen navigation">
      <a class="nav-link {{ request()->routeIs('concerns.create', 'concerns.review') ? 'active' : '' }}" href="{{ route('concerns.create') }}">Submit a concern</a>
      <a class="nav-link {{ request()->routeIs('citizen.dashboard') ? 'active' : '' }}" href="{{ route('citizen.dashboard') }}">My concerns</a>
      <a class="nav-link {{ request()->routeIs('concerns.ordinance*') ? 'active' : '' }}" href="{{ route('concerns.ordinances') }}">Ordinances</a>
      <a class="nav-link {{ request()->routeIs('concerns.status*') ? 'active' : '' }}" href="{{ route('concerns.status') }}">Track a concern</a>
    </nav>

    @if(auth()->check())
      <div class="whoami">
        <div class="whoami-name">{{ auth()->user()->name }}</div>
        <div class="muted small">Citizen</div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="link-btn" type="submit">Sign out</button>
        </form>
      </div>
    @endif
  </aside>

  <div class="main-wrap">
    <header class="topbar">
      <div class="crumbs">Victoria / Citizen / <b>@yield('crumb', 'Dashboard')</b></div>
      @auth
      <a class="notification-bell" href="{{ route('notifications.index') }}" aria-label="Notifications">
        Bell
        @php($unread = auth()->user()->notifications()->whereNull('read_at')->count())
        @if($unread)<span class="notification-count">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
      </a>
      @endauth
    </header>

    <main class="main" id="main-content">
      @yield('content')
      <footer>Municipality of Victoria, Oriental Mindoro • Community Concern and Ordinance Matching System</footer>
    </main>
  </div>
</div>
@stack('scripts')
<script>
(function(){
 const button=document.getElementById("citizenMenuBtn");
 const drawer=document.getElementById("citizenSidebar");
 if(!button||!drawer)return;
 let previous=null;
 function openDrawer(){
  previous=document.activeElement;
  drawer.classList.add("open");
  button.setAttribute("aria-expanded","true");
  drawer.querySelector("a,button")?.focus();
 }
 function closeDrawer(){
  drawer.classList.remove("open");
  button.setAttribute("aria-expanded","false");
  previous?.focus();
 }
 button.addEventListener("click",()=>drawer.classList.contains("open")?closeDrawer():openDrawer());
 document.addEventListener("keydown",event=>{
  if(event.key==="Escape"&&drawer.classList.contains("open"))closeDrawer();
  if(event.key==="Tab"&&drawer.classList.contains("open")){
   const items=[...drawer.querySelectorAll("a,button,input")].filter(el=>!el.disabled);
   if(!items.length)return;
   const first=items[0],last=items[items.length-1];
   if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
   else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
  }
 });
})();
</script>
</body>
</html>