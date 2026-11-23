<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create an account · Victoria</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
</head>
<body class="auth-body">
<div class="auth-card card">
    <div class="auth-header-box">
        @include('partials.seal')
        <div class="auth-brand-copy">
            <span class="auth-brand-title">Municipality of Victoria</span>
            <span class="auth-brand-sub">ORIENTAL MINDORO, PHILIPPINES</span>
        </div>
    </div>
    <div class="auth-form-wrap">
    <h2 class="auth-form-heading">Create an account</h2>
    <p class="muted">Sign up to submit and track community concerns. We will send a six-digit code to your email to verify your account.</p>

    <form method="POST" action="{{ route('register.attempt') }}" class="stack">
        @csrf
        <div class="field">
            <label for="username">Username *</label>
            <input id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="Required">
            @error('username')<div class="error show">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="email">Email address *</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="example@email.com">
            @error('email')<div class="error show">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="mobile">Mobile number (optional)</label>
            <input id="mobile" type="tel" name="mobile" value="{{ old('mobile') }}" autocomplete="tel" placeholder="09XX XXX XXXX">
            @error('mobile')<div class="error show">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="password">Password</label>
            <div class="password-wrap">
                <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Required">
                <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password" aria-pressed="false">Show</button>
            </div>
            <ul class="password-checklist" id="passwordChecklist" aria-live="polite">
                <li data-rule="length">At least 8 characters</li>
                <li data-rule="upper">Uppercase letter</li>
                <li data-rule="lower">Lowercase letter</li>
                <li data-rule="number">Number</li>
                <li data-rule="symbol">Special character</li>
            </ul>
            <div class="password-meter"><span id="passwordMeterBar"></span></div>
            <div class="hint" id="passwordStrength">Password strength: not entered</div>
            @error('password')<div class="error show">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <div class="password-wrap">
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Required">
                <button type="button" class="password-toggle" data-password-toggle="password_confirmation" aria-label="Show password" aria-pressed="false">Show</button>
            </div>
            <div class="hint" id="passwordMatch" aria-live="polite">Passwords must match.</div>
        </div>
        <button class="btn primary" type="submit">Create account</button>
    </form>
    <p class="muted small" style="margin-top:14px">Already have an account? <a class="link" href="{{ route('login') }}">Sign in</a></p>
    </div>
</div>
<script src="{{ asset('js/ui.js') }}"></script>
<script>
const password=document.getElementById("password");
const confirmation=document.getElementById("password_confirmation");
function checkPassword(){
 const value=password.value;
 const rules={
  length:value.length>=8,
  upper:/[A-Z]/.test(value),
  lower:/[a-z]/.test(value),
  number:/\d/.test(value),
  symbol:/[^A-Za-z0-9]/.test(value)
 };
 let score=0;
 Object.entries(rules).forEach(([rule,valid])=>{
  document.querySelector('[data-rule="'+rule+'"]').classList.toggle("valid",valid);
  if(valid)score++;
 });
 const labels=["not entered","weak","weak","fair","strong","strong"];
 const bar=document.getElementById("passwordMeterBar");
 bar.style.width=(score*20)+"%";
 bar.style.background=score<=2?"#b42318":score<=3?"#8a5a00":"#1f7a3d";
 document.getElementById("passwordStrength").textContent="Password strength: "+labels[score];
 checkMatch();
}
function checkMatch(){
 const value=confirmation.value;
 const match=value&&value===password.value;
 document.getElementById("passwordMatch").textContent=!value?"Passwords must match.":(match?"Passwords match.":"Passwords do not match.");
 document.getElementById("passwordMatch").style.color=match?"#1f7a3d":"#b42318";
}
password.addEventListener("input",checkPassword);
confirmation.addEventListener("input",checkMatch);
</script>
</body>
</html>
