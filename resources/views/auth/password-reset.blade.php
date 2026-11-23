<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password · Victoria</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}"><link rel="stylesheet" href="{{ asset('css/base.css') }}">
</head>
<body class="auth-body">
<main class="auth-card card auth-card-aesthetic">
    <div class="auth-header-box">@include('partials.seal')<div class="auth-brand-copy"><span class="auth-brand-title">Municipality of Victoria</span><span class="auth-brand-sub">ORIENTAL MINDORO, PHILIPPINES</span></div></div>
    <div class="auth-form-wrap">
        @if($step === 'request')
            <h1 class="auth-form-heading">Forgot password?</h1>
            <p class="muted">Enter the email address linked to your account.</p>
            @foreach($errors->all() as $error)<div class="error show" role="alert">{{ $error }}</div>@endforeach
            <form method="POST" action="{{ route('password.email') }}" class="stack">@csrf
                <div class="field"><label for="email">Email address *</label><input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="Required"></div>
                <button type="submit" class="btn primary auth-submit">Send reset link</button>
            </form>
        @elseif($step === 'sent')
            <h1 class="auth-form-heading">Reset link sent</h1>
            <div class="notice-banner" role="status">Prototype email: <a class="link" href="{{ $demoLink }}">Open demo reset link</a></div>
            <a class="btn auth-submit" href="{{ route('login') }}">Back to sign in</a>
        @elseif($step === 'reset')
            <h1 class="auth-form-heading">Set a new password</h1>
            <form method="POST" action="{{ route('password.update', $token) }}" class="stack">@csrf
                @foreach($errors->all() as $error)<div class="error show" role="alert">{{ $error }}</div>@endforeach
                <div class="field"><label for="password">New password *</label><div class="password-wrap"><input id="password" type="password" name="password" required placeholder="Required"><button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password">Show</button></div></div>
                <ul class="password-checklist" id="passwordChecklist" aria-live="polite"><li data-rule="length">At least 8 characters</li><li data-rule="upper">Uppercase letter</li><li data-rule="lower">Lowercase letter</li><li data-rule="number">Number</li><li data-rule="symbol">Special character</li></ul>
                <div class="password-meter"><span id="passwordMeterBar"></span></div><div class="hint" id="passwordStrength">Password strength: not entered</div>
                <div class="field"><label for="password_confirmation">Confirm password *</label><div class="password-wrap"><input id="password_confirmation" type="password" name="password_confirmation" required placeholder="Required"><button type="button" class="password-toggle" data-password-toggle="password_confirmation" aria-label="Show password">Show</button></div><div class="hint" id="passwordMatch" aria-live="polite">Passwords must match.</div></div>
                <button type="submit" class="btn primary auth-submit">Change password</button>
            </form>
        @else
            <h1 class="auth-form-heading">Password changed</h1>
            <div class="notice-banner" role="status">Your password was changed successfully.</div>
            <a class="btn primary auth-submit" href="{{ route('login') }}">Sign in</a>
        @endif
    </div>
</main>
<script src="{{ asset('js/ui.js') }}"></script>
@if($step === 'reset')
<script>
const password=document.getElementById("password"),confirmation=document.getElementById("password_confirmation");
function updatePassword(){
 const value=password.value;
 const rules={length:value.length>=8,upper:/[A-Z]/.test(value),lower:/[a-z]/.test(value),number:/\d/.test(value),symbol:/[^A-Za-z0-9]/.test(value)};
 let score=0;
 Object.entries(rules).forEach(([rule,valid])=>{const el=document.querySelector('[data-rule="'+rule+'"]');if(el)el.classList.toggle("valid",valid);if(valid)score++;});
 const bar=document.getElementById("passwordMeterBar");
 bar.style.width=(score*20)+"%";
 bar.style.background=score<=2?"#b42318":score<=3?"#8a5a00":"#1f7a3d";
 document.getElementById("passwordStrength").textContent="Password strength: "+(["not entered","weak","weak","fair","strong","strong"][score]);
 updateMatch();
}
function updateMatch(){
 const match=confirmation.value&&confirmation.value===password.value;
 document.getElementById("passwordMatch").textContent=!confirmation.value?"Passwords must match.":(match?"Passwords match.":"Passwords do not match.");
 document.getElementById("passwordMatch").style.color=match?"#1f7a3d":"#b42318";
}
password?.addEventListener("input",updatePassword);
confirmation?.addEventListener("input",updateMatch);
</script>
@endif
</body>
</html>
