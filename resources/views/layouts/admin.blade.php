{{-- Shared frame for every admin page: sidebar menu on the left, page content on the right. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administration') · Municipality of Victoria</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <script src="{{ asset('js/ui.js') }}" defer></script>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
@php($u = auth()->user())
<div class="shell">
    <button class="mobile-menu-btn" id="adminMenuBtn" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar">☰</button>
    <aside class="sidebar" id="adminSidebar">
        <a class="side-brand" href="{{ route('admin.overview') }}">
            @include('partials.seal')
            <span class="brand-copy">
                <span class="brand-title">Municipality of Victoria</span>
                <span class="brand-sub">ORIENTAL MINDORO, PHILIPPINES</span>
            </span>
        </a>

        <nav class="nav">
            <a href="{{ route('admin.password.edit') }}" class="nav-link {{ request()->routeIs('admin.password.*') ? 'active' : '' }}">Password settings</a>
            @if($u->isAdmin())
                <a href="{{ route('admin.audit.index') }}" class="nav-link {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">Audit trail / History</a>
            @endif
            @if($u->isAdmin())
                <a href="{{ route('admin.members.index') }}" class="nav-link {{ request()->routeIs('admin.members.*') ? 'active' : '' }}">Accounts</a>
                <a href="{{ route('admin.health') }}" class="nav-link {{ request()->routeIs('admin.health') ? 'active' : '' }}">System health</a>
                <a href="{{ route('admin.barangays.index') }}" class="nav-link {{ request()->routeIs('admin.barangays.*') ? 'active' : '' }}">Barangays</a>
            @endif
        </nav>

        <div class="whoami">
            <div class="whoami-name">{{ $u->name }}</div>
            <div class="muted small">{{ $u->roleLabel() }}</div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="link-btn" type="submit">Sign out</button>
            </form>
        </div>
    </aside>

    <main class="main" id="main-content">
        <div class="card-head"><div class="crumbs">Workspace &nbsp;/&nbsp; Settings</div>
            <a class="notification-bell" href="{{ route('notifications.index') }}" aria-label="Notifications">Bell
                @php($unread = $u->notifications()->whereNull('read_at')->count())
                @if($unread)<span class="notification-count">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
            </a>
        </div>
        <h1>@yield('heading')</h1>
        <p class="lead">@yield('lead')</p>

        @if(session('status'))
            <div class="flash ok" role="status">{{ session('status') }}</div>
        @endif
        @if($errors->has('members'))
            <div class="flash err" role="alert">{{ $errors->first('members') }}</div>
        @endif

        @yield('content')
    </main>
</div>
@stack('scripts')
<script>
(function(){
 const button=document.getElementById("adminMenuBtn"),drawer=document.getElementById("adminSidebar");
 if(!button||!drawer)return;
 let previous=null;
 function close(){drawer.classList.remove("open");button.setAttribute("aria-expanded","false");previous?.focus();}
 button.addEventListener("click",()=>{
  if(drawer.classList.contains("open")){close();return;}
  previous=document.activeElement;drawer.classList.add("open");button.setAttribute("aria-expanded","true");drawer.querySelector("a,button")?.focus();
 });
 document.addEventListener("keydown",event=>{
  if(event.key==="Escape"&&drawer.classList.contains("open"))close();
 });
})();
</script>
</body>
</html>