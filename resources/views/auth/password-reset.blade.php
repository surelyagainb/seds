@extends('layouts.auth-split')
@section('title', 'Password recovery')

@section('content')
@if($step === 'request')
    <a class="split-back-link" href="{{ route('login') }}">Back to sign in</a>
    <p class="split-eyebrow">PASSWORD RECOVERY</p>
    <h1 class="split-heading">Forgot your password?</h1>
    <p class="split-lead">Enter the email address linked to your account. We'll send you a code to reset your password.</p>

    <form method="POST" action="{{ route('password.email') }}" novalidate data-server-validation>
        @csrf
        <div class="split-field">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
            @error('email')<div class="split-error">{{ $message }}</div>@enderror
        </div>

        <div class="split-callout">
            Staff and Admin: use your registered official email address.
        </div>

        <button type="submit" class="split-btn split-btn-navy">Send verification code</button>
    </form>

    <p class="split-footnote" style="margin-top:16px">For your privacy, we won't confirm whether an account exists.</p>

@elseif($step === 'verify')
    <a class="split-back-link" href="{{ route('login') }}">Back to sign in</a>
    <p class="split-eyebrow">PASSWORD RECOVERY · VERIFY CODE</p>
    <h1 class="split-heading">Verify your email</h1>
    <p class="split-lead">Enter the 6-digit code sent to <strong>{{ $email }}</strong> to reset your password.</p>

    @if(session('status'))
        <div class="split-callout">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.verify.attempt') }}" novalidate data-server-validation>
        @csrf
        <div class="split-field">
            <label for="otp">6-digit verification code</label>
            <input id="otp" class="split-otp-box" type="text" name="otp" inputmode="numeric" maxlength="6" required autofocus placeholder="0 0 0 0 0 0">
            <div class="split-hint">You can paste the full code. It expires in 10 minutes.</div>
            @error('otp')<div class="split-error">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="split-btn split-btn-navy">Verify code</button>
    </form>

    <form method="POST" action="{{ route('password.resend') }}" style="margin-top:16px" novalidate data-server-validation>
        @csrf
        <span style="font-size:13px;color:var(--vic-muted)">Didn't receive a code? </span>
        <button type="submit" class="split-inline-action">Resend code</button>
    </form>

@elseif($step === 'reset')
    <a class="split-back-link" href="{{ route('login') }}">Back to sign in</a>
    <p class="split-eyebrow">PASSWORD RECOVERY · NEW PASSWORD</p>
    <h1 class="split-heading">Set your new password</h1>
    <p class="split-lead">Create a new password for <strong>{{ $email }}</strong>.</p>

    <form method="POST" action="{{ route('password.update') }}" novalidate data-server-validation>
        @csrf
        <div class="split-field">
            <label for="password">New password</label>
            <div class="password-wrap">
                <input id="password" type="password" name="password" minlength="8" required autofocus placeholder="Create a new password">
                <button type="button" class="password-toggle" data-password-toggle="password">Show</button>
            </div>
            <div class="split-hint">Use at least 8 characters. A unique passphrase works best.</div>
            @error('password')<div class="split-error">{{ $message }}</div>@enderror
        </div>

        <div class="split-field">
            <label for="password_confirmation">Confirm new password</label>
            <div class="password-wrap">
                <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required placeholder="Re-enter your new password">
                <button type="button" class="password-toggle" data-password-toggle="password_confirmation">Show</button>
            </div>
        </div>

        <button type="submit" class="split-btn split-btn-navy">Save new password</button>
    </form>

@else
    <p class="split-eyebrow">PASSWORD RECOVERY</p>
    <h1 class="split-heading">Password updated</h1>
    <p class="split-lead">Your password has been changed. You can now sign in with your new credentials.</p>
    <a href="{{ route('login') }}" class="split-btn split-btn-navy">Back to sign in</a>
@endif
@endsection
@push('scripts')
<script>
document.getElementById('otp')?.addEventListener('input', function () {
    this.value = this.value.replace(/\D+/g, '').slice(0, 6);
});
</script>
@endpush
