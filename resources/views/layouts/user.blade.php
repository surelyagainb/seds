<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') – Victoria, Oriental Mindoro</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/user.css') }}">
</head>
<body>
<header class="site-header"><div class="inner">
  <a class="brand" href="{{ route('concerns.create') }}">Victoria.<br>Oriental Mindoro</a>
  <nav class="nav">
    <a href="#">How it works</a>
    <a href="{{ route('concerns.status') }}">Track a concern</a>
    <span class="who">{{ auth()->user()->name }}</span>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="btn-signin" type="submit">Sign out</button>
    </form>
  </nav>
</div></header>

<main>
  @yield('content')
</main>
<footer>Municipality of Victoria, Oriental Mindoro • Community Concern and Ordinance Matching System</footer>
@stack('scripts')
</body>
</html>
