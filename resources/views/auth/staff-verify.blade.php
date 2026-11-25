@extends('layouts.auth-split')
@section('title', 'Verify your email')

@section('content')
<a class="split-back-link" href="{{ route('login') }}">Back to sign in</a>
<p class="split-eyebrow">STAFF EMAIL VERIFICATION</p>
<h1 class="split-heading">Verify your email</h1>
<p class="split-lead">
    Enter the 6-digit code sent to <strong>{{ $maskedEmail }}</strong> to continue setting up your account.
</p>

<p style="margin:-10px 0 18px;font-size:13px;color:var(--vic-muted)">
    Wrong email? <a class="split-link" href="{{ route('staff.register') }}">Change email address</a>
</p>

@if(session('status'))
    <div class="split-callout">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('staff.register.verify.attempt') }}" novalidate data-server-validation>
    @csrf
    <div class="split-field">
        <label for="otp">6-digit verification code</label>
        <input id="otp" class="split-otp-box" type="text" name="otp" inputmode="numeric" maxlength="6" required autofocus placeholder="0 0 0 0 0 0">
        <div class="split-hint">You can paste the full code. It expires in 10 minutes.</div>
        @error('otp')<div class="split-error">{{ $message }}</div>@enderror
    </div>

    <button type="submit" class="split-btn split-btn-navy">Verify email</button>
</form>

<form method="POST" action="{{ route('staff.register.verify.resend') }}" style="margin-top:16px" novalidate data-server-validation>
    @csrf
    <span style="font-size:13px;color:var(--vic-muted)">Didn't receive a code? </span>
    <button type="submit" class="split-inline-action">Resend code</button>
</form>

<div class="split-callout">
    Check your spam folder. Never share your code. Staff accounts require approval after verification.
</div>
@endsection
@push('scripts')
<script>
document.getElementById('otp')?.addEventListener('input', function () {
    this.value = this.value.replace(/\D+/g, '').slice(0, 6);
});
</script>
@endpush
