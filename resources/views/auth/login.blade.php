<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ config('admin.brand') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="auth-body">
<div class="auth-card card">
    <div class="brand">{{ config('admin.brand') }}</div>
    <h2>Sign in</h2>
    <p class="muted">Use your workspace account.</p>

    @if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif

    <form method="POST" action="{{ route('login.attempt') }}" class="stack">
        @csrf
        <div class="field">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
        </div>
        <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
        <button class="btn primary" type="submit">Sign in</button>
    </form>
</div>
</body>
</html>
