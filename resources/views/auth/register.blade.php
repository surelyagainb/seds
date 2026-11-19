<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create an account · Victoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="auth-body">
<div class="auth-card card">
    <div class="brand">Victoria, Oriental Mindoro</div>
    <h2>Create an account</h2>
    <p class="muted">Sign up to submit and track community concerns.</p>

    <form method="POST" action="{{ route('register.attempt') }}" class="stack">
        @csrf
        <div class="field">
            <label for="name">Full name</label>
            <input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            @error('name')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            @error('email')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            <div class="hint">At least 8 characters, with letters and numbers.</div>
            @error('password')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button class="btn primary" type="submit">Create account</button>
    </form>
    <p class="muted small" style="margin-top:14px">Already have an account? <a class="link" href="{{ route('login') }}">Sign in</a></p>
</div>
</body>
</html>
