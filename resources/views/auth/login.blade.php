@extends('layouts.auth-split')
@section('title', 'Sign in')

@section('content')
<p class="split-eyebrow">SHARED SIGN IN</p>
<h1 class="split-heading">Sign in</h1>
<p class="split-lead">Enter your email address, username, or mobile number and password.</p>

@if(session('status'))
    <div class="split-callout">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('login.attempt') }}" novalidate data-server-validation>
    @csrf

    <div class="split-field">
        <label for="identity">Email address, username, or mobile</label>
        <input id="identity" type="text" name="identity" value="{{ old('identity') }}" required autofocus autocomplete="username" placeholder="you@example.com">
        @error('identity')<div class="split-error">{{ $message }}</div>@enderror
    </div>

    <div class="split-field">
        <label for="password">Password</label>
        <div class="password-wrap">
            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
            <button type="button" class="password-toggle" data-password-toggle="password">Show</button>
        </div>
        @error('password')<div class="split-error">{{ $message }}</div>@enderror
    </div>

    <div class="split-meta-row">
        <label class="split-check" style="margin:0">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Keep me signed in</span>
        </label>
        <a class="split-link" href="{{ route('password.request') }}">Forgot password?</a>
    </div>

    <button type="submit" class="split-btn split-btn-navy">Sign in</button>
</form>

<div class="split-center-note" style="margin-top:20px">
    New citizen? <a class="split-link" href="{{ route('register') }}">Create citizen account</a><br>
    <span style="display:inline-block;margin-top:6px">
        Municipal Staff? <a class="split-link" href="{{ route('staff.register') }}">Register as Municipal Staff</a>
    </span>
</div>
@endsection
