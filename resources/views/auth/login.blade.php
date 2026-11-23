<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · Victoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
</head>
<body class="auth-body">
<div class="auth-card card auth-card-aesthetic">
    <div class="auth-header-box">
        @include('partials.seal')
        <div class="auth-brand-copy">
            <span class="auth-brand-title">Municipality of Victoria</span>
            <span class="auth-brand-sub">ORIENTAL MINDORO, PHILIPPINES</span>
        </div>
    </div>

    <div class="auth-form-wrap">
        <h2 class="auth-form-heading">Sign in</h2>
        <p class="muted">After your password is checked, we will send a six-digit verification code to your email.</p>

        @if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif

        <form method="POST" action="{{ route('login.attempt') }}" class="stack">
            @csrf
            @if($hallLogin ?? false)<input type="hidden" name="hall" value="1">@endif
            <div class="field">
                <label for="identity">{{ ($hallLogin ?? false) ? 'Username, email or mobile *' : 'Username, email or mobile *' }}</label>
                <input id="identity" type="text" name="identity" value="{{ old('identity') }}" required autofocus autocomplete="username" placeholder="Enter your username, email or mobile">
                @error('identity')<div class="error show" role="alert">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password">Password *</label>
                <div class="password-wrap">
                    <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Required">
                    <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password" aria-pressed="false">Show</button>
                </div>
            </div>
            <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
            <div class="auth-reset-link"><a class="link" href="{{ route('password.request') }}">Forgot password?</a></div>
            <button class="btn primary auth-submit" type="submit">Sign in</button>
        </form>
        <p class="muted small auth-footer-link">New here? <a class="link" href="{{ route('register') }}">Create an account</a></p>
    </div>
</div>
<script src="{{ asset('js/ui.js') }}"></script>
</body>
</html>