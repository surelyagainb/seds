@extends('layouts.user')
@section('title', 'Review Your Concern')

@section('content')
@php $cats = config('concerns.categories'); $keys = $draft['categories'] ?: ['other']; @endphp
<h1>Review your concern</h1>
<p class="lead">Please check the details below. You can still make changes before sending your report.</p>

<div class="grid" style="grid-template-columns:minmax(0,1fr) 340px">
  <section class="card">
    <div class="card-head">
      <h2>Your report</h2>
      <a class="btn" href="{{ route('concerns.create') }}" style="padding:8px 14px;font-size:12px">Edit details</a>
    </div>
    <div class="sub" style="text-transform:uppercase;letter-spacing:.05em;margin-bottom:0;font-size:10px">Original concern</div>
    <div class="quote">{{ $draft['concern'] }}</div>

    <div class="kv"><div class="k">Barangay</div><div class="v">{{ $draft['barangay'] }}</div></div>
    <div class="kv"><div class="k">Location</div><div class="v">{{ $draft['location'] ?: 'Not provided' }}</div></div>
    <div class="kv"><div class="k">Categories • One concern, multiple categories</div>
      <div class="chips" style="margin-top:6px">
        @foreach($keys as $k)<span class="chip {{ $cats[$k]['cls'] }}">{{ $cats[$k]['label'] }}</span>@endforeach
      </div>
    </div>
    <div class="kv"><div class="k">Photo</div><div class="v">{{ $draft['photo_name'] ?: 'No photo attached' }}</div></div>
  </section>

  <aside>
    <form class="card" method="POST" action="{{ route('concerns.store') }}">
      @csrf
      <h2>Before you send</h2>
      <p class="sub" style="font-size:12px">This report will be recorded for municipal analysis and ordinance matching. Authorized staff will review the match before taking any next step.</p>
      <div class="notice" style="margin-top:0">Keep your reference number after submission. Your report is not yet submitted.</div>
      <label class="check"><input type="checkbox" id="agree" name="agree" value="1"> <span>I have checked these details and understand they will be shared with authorized municipal staff.</span></label>
      @error('agree')<div class="error show">{{ $message }}</div>@enderror
      <button class="btn primary block" id="submit" type="submit" disabled>Submit concern</button>
      <a class="btn block" href="{{ route('concerns.create') }}" style="margin-top:10px">Back to edit</a>
    </form>
    <p class="fine">Only include the information needed to understand your concern. Do not submit passwords, identity numbers or private medical records.</p>
  </aside>
</div>
@endsection

@push('scripts')
<script>
const agree = document.getElementById("agree"), submit = document.getElementById("submit");
agree.onchange = () => submit.disabled = !agree.checked;
submit.disabled = !agree.checked;
</script>
@endpush
