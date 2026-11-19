@extends('layouts.user')
@section('title', 'Submit a Concern')

@section('content')
@php
    $v = fn ($key) => old($key, $draft[$key] ?? '');
    $cats = config('concerns.categories');
@endphp
<h1>A better Victoria starts with you.</h1>
<p class="lead">Report a local concern. Help the municipality connect community needs with the right ordinances.</p>

<div class="grid">
  <form class="card" id="form" method="POST" action="{{ route('concerns.preview') }}" enctype="multipart/form-data" novalidate>
    @csrf
    <div class="card-head"><h2>Submit a concern</h2><span class="pill">Step 1 of 2</span></div>
    <p class="sub">Tell us what happened. You can review everything before submitting.</p>

    <label class="field" for="barangay">Barangay *</label>
    <select id="barangay" name="barangay" class="{{ $errors->has('barangay') ? 'invalid' : '' }}" required>
      <option value="">Select barangay</option>
      @foreach(config('concerns.barangays') as $b)
        <option value="{{ $b }}" @selected($v('barangay') === $b)>{{ $b }}</option>
      @endforeach
    </select>
    <div class="hint">Search and select where the concern happened.</div>
    @error('barangay')<div class="error show">{{ $message }}</div>@enderror

    <label class="field" for="concern">Describe your concern *</label>
    <textarea id="concern" name="concern" maxlength="2000" class="{{ $errors->has('concern') ? 'invalid' : '' }}" placeholder="What happened, where, and when?">{{ $v('concern') }}</textarea>
    <div class="hint">Write in English • Describe what happened, where and when.</div>
    <div class="counter"><span id="count">0</span> / 2,000 characters</div>
    @error('concern')<div class="error show">{{ $message }}</div>@enderror

    <label class="field" style="margin-top:0">Detected categories</label>
    <div class="chips" id="chips"></div>
    <div id="catInputs"></div>
    <div class="add-wrap" style="margin-top:8px">
      <button type="button" class="btn-add" id="addBtn">+ Add category</button>
      <div class="menu" id="menu"></div>
    </div>
    <div class="hint">One concern can have multiple categories. Remove or add a category if needed.</div>
    @error('categories.*')<div class="error show">{{ $message }}</div>@enderror

    <label class="field" for="location">Location or landmark (optional)</label>
    <input type="text" id="location" name="location" maxlength="150" value="{{ $v('location') }}" placeholder="e.g. Near the covered court">
    <div class="hint">Do not include private addresses or sensitive personal information.</div>
    @error('location')<div class="error show">{{ $message }}</div>@enderror

    <label class="field">Photo *</label>
    <label class="photo {{ $errors->has('photo') ? 'invalid' : '' }}" for="photo">
      <span class="photo-icon">▣</span>
      <span><b>Choose a photo</b><small id="photoText">{{ !empty($draft['photo_name']) ? $draft['photo_name'].' • Selected (choose another to replace it)' : 'JPG or PNG • Up to 10 MB • No photo selected' }}</small></span>
      <input type="file" id="photo" name="photo" accept="image/jpeg,image/png">
    </label>
    @error('photo')<div class="error show">{{ $message }}</div>@enderror

    <div class="notice">Your report supports municipal review. Ordinance suggestions are not legal advice or a promise of resolution.</div>
    <div class="consent">By continuing, you agree to send this concern to authorized municipal staff. Only include information needed to review it.</div>

    <div class="actions">
      <button type="submit" class="btn primary">Review concern →</button>
      <button type="submit" class="btn" form="clear-form">Clear form</button>
    </div>
  </form>
  <form id="clear-form" method="POST" action="{{ route('concerns.clear') }}">@csrf @method('DELETE')</form>

  <aside class="side">
    <div class="card">
      <div class="eyebrow">What you can report</div>
      <h3>Everyday concerns. Shared responsibility.</h3>
      <div class="topic"><span class="ic"></span><div><b>Public safety</b><span>Flooding, clogged drainage and unsafe local roads.</span></div></div>
      <div class="topic"><span class="ic"></span><div><b>Health</b><span>Dengue risks and community sanitation concerns.</span></div></div>
      <div class="topic"><span class="ic"></span><div><b>Waste management</b><span>Missed collection, garbage buildup and open dumping.</span></div></div>
    </div>
    <div class="card next">
      <h3>What happens next?</h3>
      <ol><li>Receive your reference number</li><li>Analyze your concern</li><li>Find relevant demo ordinances</li><li>Staff review the match</li></ol>
      <p>You stay informed with a simple four-stage tracker.</p>
    </div>
    <div class="warn"><b>Not for emergencies</b>For an immediate threat to life or safety, contact emergency services. Do not wait for online review.</div>
  </aside>
</div>
@endsection

@push('scripts')
<script>
const CATS = @json($cats);
const $ = id => document.getElementById(id);
let selected = @json(array_values((array) old('categories', $draft['categories'] ?? [])));
let dismissed = new Set();

function detect(){
  const t = $("concern").value.toLowerCase();
  Object.entries(CATS).forEach(([k,c])=>{
    if(c.kw.some(w=>t.includes(w)) && !dismissed.has(k) && !selected.includes(k)) selected.push(k);
  });
}
function render(){
  $("chips").innerHTML = selected.length ? "" : '<span class="hint" style="margin:0">None detected yet</span>';
  $("catInputs").innerHTML = "";
  selected.forEach(k=>{
    const s = document.createElement("span"); s.className = "chip " + CATS[k].cls;
    s.textContent = CATS[k].label + " ";
    const x = document.createElement("button"); x.type = "button"; x.setAttribute("aria-label","Remove"); x.textContent = "×";
    x.onclick = ()=>{ selected = selected.filter(i=>i!==k); dismissed.add(k); render(); };
    s.appendChild(x); $("chips").appendChild(s);

    const h = document.createElement("input"); h.type = "hidden"; h.name = "categories[]"; h.value = k;
    $("catInputs").appendChild(h);
  });
  $("menu").innerHTML = "";
  Object.entries(CATS).filter(([k])=>!selected.includes(k)).forEach(([k,c])=>{
    const b = document.createElement("button"); b.type = "button"; b.textContent = c.label;
    b.onclick = ()=>{ selected.push(k); dismissed.delete(k); $("menu").classList.remove("open"); render(); };
    $("menu").appendChild(b);
  });
}
$("addBtn").onclick = e=>{ e.stopPropagation(); $("menu").classList.toggle("open"); };
document.addEventListener("click", ()=>$("menu").classList.remove("open"));

$("concern").addEventListener("input", ()=>{
  $("count").textContent = $("concern").value.length.toLocaleString();
  detect(); render();
});
$("photo").addEventListener("change", e=>{
  const f = e.target.files[0];
  if(f && f.size > 10*1024*1024){ alert("Photo must be 10 MB or smaller."); e.target.value = ""; return; }
  $("photoText").textContent = f ? f.name + " • " + (f.size/1048576).toFixed(1) + " MB" : "JPG or PNG • Up to 10 MB • No photo selected";
});
$("count").textContent = $("concern").value.length.toLocaleString();
render();
</script>
@endpush
