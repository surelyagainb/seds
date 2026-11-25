@extends('layouts.auth-split')
@section('title', 'Your account is under review')

@section('content')
<a class="split-back-link" href="{{ route('login') }}">Back to sign in</a>
<p class="split-eyebrow">STAFF ACCOUNT</p>
<h1 class="split-heading">Your account is under review</h1>
<p class="split-lead">Your email is verified. The municipal administrator will review your Staff access before you can sign in.</p>

<div class="split-callout">
    <strong>Email verified</strong>
    <div class="split-callout-sub">Staff access · Awaiting approval</div>
    We'll email you when your access is approved. You don't need to register again.
</div>

<a href="{{ route('login') }}" class="split-btn split-btn-navy">Back to sign in</a>

<p class="split-footnote" style="margin-top:16px">Need help? Contact your municipal administrator.</p>
@endsection
