@extends('layouts.admin')
@section('title', 'Accounts')
@section('heading', 'Accounts')
@section('lead', 'Check accounts and remove the ones that should no longer have access.')

@section('content')
<section class="card">
    <h2>All accounts</h2>
    <table class="table">
        <thead><tr><th>Account</th><th>Role</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($members as $m)
            <tr>
                <td><div class="value-sm">{{ $m->name }}</div><div class="muted small">{{ $m->email }}</div></td>
                <td><span class="tag">{{ $m->roleLabel() }}</span></td>
                <td><span class="pill {{ $m->is_active ? 'green' : 'grey' }}">{{ $m->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td class="right">
                    @unless($m->isAdmin())
                        <button type="button" class="btn small-btn danger"
                                onclick="document.getElementById('remove-{{ $m->id }}').showModal()">Remove</button>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</section>

@foreach($members as $m)
    @unless($m->isAdmin())
    <dialog id="remove-{{ $m->id }}" class="dialog">
        <form method="POST" action="{{ route('admin.members.destroy', $m) }}" class="stack">
            @csrf @method('DELETE')
            <h2>Remove this account?</h2>
            <p class="muted small">{{ $m->name }} ({{ $m->email }}) will lose access for good. Their history stays on record.</p>
            <div class="field">
                <label for="rm-pass-{{ $m->id }}">Enter your password to confirm</label>
                <input id="rm-pass-{{ $m->id }}" type="password" name="password" autocomplete="current-password" required>
                @if($errors->remove->has('password') && old('_removing') == $m->id)<div class="error">{{ $errors->remove->first('password') }}</div>@endif
            </div>
            <input type="hidden" name="_removing" value="{{ $m->id }}">
            <div class="actions">
                <button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button>
                <button type="submit" class="btn danger solid">Remove account</button>
            </div>
        </form>
    </dialog>
    @endunless
@endforeach

@if($errors->remove->any() && old('_removing'))
    @push('scripts')<script>document.getElementById('remove-{{ old('_removing') }}')?.showModal();</script>@endpush
@endif

<p class="foot-note">Roles can't be changed here. They are granted only through an approved office request.</p>
@endsection
