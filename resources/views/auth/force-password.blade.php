@extends('layouts.auth-split')
@section('title', 'Set your new password')

@section('content')
<p class="split-eyebrow">CITIZEN ACCOUNT · STEP 2 OF 2</p>
<h1 class="split-heading">Set your new password</h1>
<p class="split-lead">Replace your temporary password with a password only you know.</p>

<div class="split-callout">
    <strong>Password change required</strong>
    Complete this step to access your account. Your temporary password will no longer work.
</div>

<form method="POST" action="{{ route('password.force.update') }}" novalidate data-server-validation>
    @csrf
    <div class="split-field">
        <label for="password">New password</label>
        <div class="password-wrap">
            <input id="password" type="password" name="password" minlength="8" required autofocus placeholder="Enter a new password">
            <button type="button" class="password-toggle" data-password-toggle="password">Show</button>
        </div>
        @error('password')<div class="split-error">{{ $message }}</div>@enderror
    </div>

    <div class="split-field">
        <label for="password_confirmation">Confirm new password</label>
        <div class="password-wrap">
            <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required placeholder="Re-enter your new password">
            <button type="button" class="password-toggle" data-password-toggle="password_confirmation">Show</button>
        </div>
    </div>

    <div class="split-hint" style="margin-bottom:18px">
        <strong style="display:block;color:#374151;margin-bottom:2px">For a stronger password</strong>
        Use at least 8 characters.<br>
        Choose a password different from your temporary one.
    </div>

    <button type="submit" class="split-btn split-btn-gold">Save password &amp; continue</button>
</form>

<p class="split-center-note">Your new password will be used for future sign-ins.</p>
<form method="POST" action="{{ route('logout') }}" class="split-center-note" style="margin-top:8px" novalidate data-server-validation>
    @csrf
    <button type="submit" class="split-inline-action">Sign out</button>
</form>
@endsection
