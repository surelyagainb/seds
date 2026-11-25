@extends('layouts.auth-split')
@section('title', 'Create your account')

@section('content')
<p class="split-eyebrow">CITIZEN REGISTRATION</p>
<h1 class="split-heading">Create your account</h1>
<p class="split-lead">Get started with your name, email, and password.</p>

<form method="POST" action="{{ route('register.attempt') }}" novalidate data-server-validation>
    @csrf
    <div class="split-grid-2">
        <div class="split-field">
            <label for="first_name">First name</label>
            <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus placeholder="First name">
            @error('first_name')<div class="split-error">{{ $message }}</div>@enderror
        </div>
        <div class="split-field">
            <label for="last_name">Last name</label>
            <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required placeholder="Last name">
            @error('last_name')<div class="split-error">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="split-field">
        <label for="email">Email address</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com">
        @error('email')<div class="split-error">{{ $message }}</div>@enderror
    </div>

    <div class="split-field">
        <label for="password">Password</label>
        <div class="password-wrap">
            <input id="password" type="password" name="password" minlength="8" required autocomplete="new-password" placeholder="Create a password">
            <button type="button" class="password-toggle" data-password-toggle="password">Show</button>
        </div>
        <div class="split-hint">Use at least 8 characters. A unique passphrase works best.</div>
        @error('password')<div class="split-error">{{ $message }}</div>@enderror
    </div>

    <label class="split-check">
        <input type="checkbox" name="terms" value="1" @checked(old('terms')) required>
        <span>I agree to the <a href="#terms">Terms of use</a> and <a href="#privacy">Privacy policy</a>.</span>
    </label>
    @error('terms')<div class="split-error" style="margin:-12px 0 12px">{{ $message }}</div>@enderror

    <button type="submit" class="split-btn split-btn-navy">Continue to email verification</button>
</form>

<p class="split-footnote" style="margin-top:16px">
    Already have an account? <a class="split-link" href="{{ route('login') }}">Sign in</a>
</p>
@endsection
