{{-- Change-password page for the signed-in admin. @method('PUT') makes the form send a PUT request. --}}
@extends('layouts.admin')
@section('title', 'Password settings')
@section('heading', 'Password settings')
@section('lead', 'Keep your account secure with a strong password.')

@section('content')
<section class="card">
    <h2>Change password</h2>
    <p class="muted small">For {{ $user->email }}</p>

    {{-- The form checks the current password first, then the two new password boxes must match --}}
    <form method="POST" action="{{ route('admin.password.update') }}" class="stack narrow">
        @csrf
        @method('PUT')

        <div class="field">
            <label for="current_password">Current password</label>
            <input id="current_password" type="password" name="current_password" autocomplete="current-password" required>
            @error('current_password')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="password">New password</label>
            <input id="password" type="password" name="password" placeholder="Enter a new password" autocomplete="new-password" required>
            <div class="hint">Use at least 12 characters, including a number and symbol.</div>
            @error('password')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Re-enter your new password" autocomplete="new-password" required>
        </div>

        <div class="row-split">
            <button class="btn primary" type="submit">Update password</button>
            <span class="muted small">{{ $user->password_changed_at ? 'Last updated '.$user->password_changed_at->diffForHumans() : 'Never updated' }}</span>
        </div>
    </form>
</section>
<p class="foot-note">Changing your password will sign you out of other sessions.</p>
@endsection
