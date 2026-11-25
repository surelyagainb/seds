<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Municipality of Victoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth-split.css') }}">
</head>
<body class="split-auth-body">
<div class="split-auth-shell">
    <aside class="split-auth-aside" style="--hero-bg: url('{{ asset('images/auth-side-panel.jpg') }}')">
        <div class="split-aside-brand">
            <img src="{{ asset('images/logo.jpg') }}" alt="Municipality of Victoria Seal" class="split-aside-seal">
            <div>
                <div class="split-aside-title">Municipality of <span>VICTORIA</span></div>
                <div class="split-aside-sub">Oriental Mindoro, Philippines</div>
            </div>
        </div>
    </aside>

    <main class="split-auth-main">
        <div class="split-auth-container">
            @yield('content')
        </div>

        <footer class="split-auth-footer">
            <a href="#privacy">Privacy policy</a>
            <span aria-hidden="true">•</span>
            <a href="#terms">Terms of use</a>
        </footer>
    </main>
</div>
<script src="{{ asset('js/ui.js') }}"></script>
@stack('scripts')
</body>
</html>