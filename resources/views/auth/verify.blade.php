<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify email · Victoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
</head>
<body class="auth-body">
<main class="auth-card card auth-card-aesthetic">
    <div class="auth-header-box">
        @include('partials.seal')
        <div class="auth-brand-copy">
            <span class="auth-brand-title">Municipality of Victoria</span>
            <span class="auth-brand-sub">ORIENTAL MINDORO, PHILIPPINES</span>
        </div>
    </div>

    <div class="auth-form-wrap">
        <h1 class="auth-form-heading">Verify your email</h1>
        <p class="muted">
            We sent a six-digit verification code to <strong>{{ $email }}</strong>.
            Enter it below to {{ $purpose === 'registration' ? 'finish creating your account' : 'complete your sign in' }}.
        </p>

        @if(session('status'))<div class="flash ok" role="status">{{ session('status') }}</div>@endif
        @error('otp')<div class="error show" role="alert">{{ $message }}</div>@enderror

        <form method="POST" action="{{ $purpose === 'registration' ? route('register.verify.attempt') : route('login.verify.attempt') }}" class="stack">
            @csrf
            <div class="field">
                <label for="otp">Six-digit code *</label>
                <input id="otp" type="text" name="otp" value="{{ old('otp') }}" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" minlength="6" required autofocus placeholder="Enter your code">
                <div class="hint">The code expires after 5 minutes and can only be used once.</div>
            </div>
            <button class="btn primary auth-submit" type="submit">Verify code</button>
        </form>

        <form method="POST" action="{{ $purpose === 'registration' ? route('register.verify.resend') : route('login.verify.resend') }}" class="stack" style="margin-top:14px">
            @csrf
            <button class="btn auth-submit" type="submit">Resend verification code</button>
        </form>

        <p class="muted small auth-footer-link">
            <a class="link" href="{{ $purpose === 'registration' ? route('register') : route('login') }}">Use a different {{ $purpose === 'registration' ? 'email' : 'account' }}</a>
        </p>
    </div>
</main>
<script src="{{ asset('js/ui.js') }}"></script>
</body>
</html>
