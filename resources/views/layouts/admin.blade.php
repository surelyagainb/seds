{{-- Shared frame for every admin page: sidebar menu on the left, page content on the right. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administration') · {{ config('admin.brand') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
{{-- $u = the signed-in admin --}}
@php($u = auth()->user())
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('admin.overview') }}">{{ config('admin.brand') }}</a>

        {{-- Sidebar links. The current page's link is highlighted with the "active" class. --}}
        <nav class="nav">
            <a href="{{ route('admin.password.edit') }}" class="nav-link {{ request()->routeIs('admin.password.*') ? 'active' : '' }}">Password settings</a>
            @if($u->isAdmin())
                <a href="{{ route('admin.audit.index') }}" class="nav-link {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">Audit trail / History</a>
            @endif
            @if($u->isAdmin())
                <a href="{{ route('admin.members.index') }}" class="nav-link {{ request()->routeIs('admin.members.*') ? 'active' : '' }}">Accounts</a>
            @endif
        </nav>

        {{-- Name, role and sign-out button at the bottom of the sidebar --}}
        <div class="whoami">
            <div class="whoami-name">{{ $u->name }}</div>
            <div class="muted small">{{ $u->roleLabel() }}</div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="link-btn" type="submit">Sign out</button>
            </form>
        </div>
    </aside>

    <main class="main">
        <div class="crumbs">Workspace &nbsp;/&nbsp; Settings</div>
        <h1>@yield('heading')</h1>
        <p class="lead">@yield('lead')</p>

        {{-- Green message after an action worked (for example "account removed") --}}
        @if(session('status'))
            <div class="flash ok" role="status">{{ session('status') }}</div>
        @endif
        {{-- Red message when an account could not be removed --}}
        @if($errors->has('members'))
            <div class="flash err" role="alert">{{ $errors->first('members') }}</div>
        @endif

        {{-- Each page puts its own content here --}}
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
