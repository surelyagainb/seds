@extends('layouts.citizen')
@section('title', 'Submit a Concern')

@section('content')
@php
    $v = fn ($key) => old($key, $draft[$key] ?? '');
    $cats = config('concerns.categories');
@endphp
<h1>A better Victoria starts with you.</h1>
<p class="lead">Report a local concern. Help the municipality connect community needs with the right ordinances.</p>

<div class="grid concern-layout">
  <form class="card" id="form" method="POST" action="{{ route('concerns.preview') }}" enctype="multipart/form-data" novalidate>
    @csrf
    <div class="card-head"><h2>Submit a concern</h2><span class="pill">Step 1 of 2</span></div>
    <p class="sub">Tell us what happened. You can review everything before submitting.</p>

    <label class="field" for="barangay">Barangay *</label>
    <select id="barangay" name="barangay" class="{{ $errors->has('barangay') ? 'invalid' : '' }}" required>
      <option value="">Select barangay</option>
      @foreach($barangays as $b)
        <option value="{{ $b }}" @selected($v('barangay') === $b)>{{ $b }}</option>
      @endforeach
    </select>
    <div class="hint">Search and select where the concern happened.</div>
    @error('barangay')<div class="error show">{{ $message }}</div>@enderror

    <label class="field" for="concern">Describe your concern *</label>
    <textarea id="concern" name="concern" maxlength="2000" class="{{ $errors->has('concern') ? 'invalid' : '' }}" placeholder="What happened, where, and when?">{{ $v('concern') }}</textarea>
    <div class="hint">You can write in English or Filipino. Describe what happened, where and when.</div>
    <div class="counter"><span id="count">0</span> / 2,000 characters</div>
    @error('concern')<div class="error show">{{ $message }}</div>@enderror

    <label class="field" style="margin-top:0">Detected categories</label>
    <div class="chips" id="chips"></div>
    <div class="hint" id="detectedFrom" style="display:none">Detected from: <b></b></div>
    <details class="hint"><summary>How categories are detected</summary><p>We look for common English and Filipino words such as “baha,” “basura,” “lubak,” “lamok,” and “sunog ng basura.” You can always add or remove categories.</p></details>
    <div id="catInputs"></div>
    <div class="add-wrap" style="margin-top:8px">
      <button type="button" class="btn-add" id="addBtn">+ Add category</button>
      <div class="menu" id="menu"></div>
    </div>
    <div class="hint">One concern can have multiple categories. Remove or add a category if needed.</div>
    @error('categories.*')<div class="error show">{{ $message }}</div>@enderror

    <label class="field" for="location">Location or landmark (optional)</label>
    <button type="button" class="btn" id="openLocation">Use map to set location</button>
    <input type="text" id="location" name="location" maxlength="150" value="{{ $v('location') }}" placeholder="e.g. Near the covered court">
    <input type="hidden" id="latitude" name="latitude" value="{{ $v('latitude') }}">
    <input type="hidden" id="longitude" name="longitude" value="{{ $v('longitude') }}">
    <div id="locationPanel" hidden>
      <p class="hint" id="locationMessage" aria-live="polite">Opening location services...</p>
      <div id="locationMap" style="height:280px;border-radius:8px;margin:8px 0"></div>
      <div class="hint">Your pin is used to help staff find the place. Do not enter a private home address.</div>
    </div>
    <div class="hint">Use the nearest barangay center or search for your barangay if GPS is denied or unavailable.</div>
    <input type="search" id="barangaySearch" placeholder="Search barangay" aria-label="Search barangay">
    @error('location')<div class="error show">{{ $message }}</div>@enderror

    <label class="field">Photo *</label>
    <label class="photo {{ $errors->has('photo') ? 'invalid' : '' }}" for="photo">
      <span class="photo-icon">▣</span>
      <span><b>Choose a photo</b><small id="photoText">{{ !empty($draft['photo_name']) ? $draft['photo_name'].' • Selected (choose another to replace it)' : 'JPG or PNG • Up to 5 MB • No photo selected' }}</small></span>
      <input type="file" id="photo" name="photo" accept="image/jpeg,image/png">
    </label>
    <div class="photo-preview" id="photoPreview" {{ empty($draft['photo_path']) ? 'hidden' : '' }}>
      <img id="photoImage" src="{{ !empty($draft['photo_path']) ? route('concerns.draft-photo') : '' }}" alt="Selected concern photo">
    </div>
    @error('photo')<div class="error show">{{ $message }}</div>@enderror
    <div class="hint" id="photoScan" aria-live="polite"></div>

    @php($emergency=config('concerns.emergency'))
    <div class="warn"><b>Emergency contacts</b>
      Emergency: call {{ $emergency['911'] }}.<br>
      MDRRMO Victoria: {{ $emergency['mdrrmo'] }}.<br>
      Victoria Police Station: {{ $emergency['police'] }}.<br>
      Rural Health Unit: {{ $emergency['rhu'] }}.
    </div>

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
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const barangayCoordinates=@json(config('concerns.barangay_coordinates'));
const barangaySelect=document.getElementById("barangay");
let locationMap, locationMarker;
function nearestBarangay(lat,lng){
 let nearest=null,best=Infinity;
 Object.entries(barangayCoordinates).forEach(([name,p])=>{
  const d=Math.pow(lat-p[0],2)+Math.pow(lng-p[1],2);
  if(d<best){best=d;nearest=name;}
 });
 return nearest;
}
function setPin(lat,lng){
 document.getElementById("latitude").value=lat.toFixed(4);
 document.getElementById("longitude").value=lng.toFixed(4);
 const nearest=nearestBarangay(lat,lng);
 barangaySelect.value=nearest;
 document.getElementById("locationMessage").textContent="Nearest barangay: "+nearest+". Drag or tap to adjust.";
}
document.getElementById("openLocation").addEventListener("click",()=>{
 document.getElementById("locationPanel").hidden=false;
 if(!window.L){document.getElementById("locationMessage").textContent="Map could not load. Check your internet connection.";return;}
 if(locationMap)return locationMap.invalidateSize();
 locationMap=L.map("locationMap").setView([13.177,121.278],12);
 L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{maxZoom:19,attribution:"&copy; OpenStreetMap contributors"}).addTo(locationMap);
 const start=()=>{
  navigator.geolocation.getCurrentPosition(pos=>{
   const lat=pos.coords.latitude,lng=pos.coords.longitude;
   locationMap.setView([lat,lng],16);
   locationMarker=L.marker([lat,lng],{draggable:true}).addTo(locationMap);
   locationMarker.on("dragend",()=>{const p=locationMarker.getLatLng();setPin(p.lat,p.lng);});
   locationMap.on("click",e=>{locationMarker.setLatLng(e.latlng);setPin(e.latlng.lat,e.latlng.lng);});
   setPin(lat,lng);
  },err=>{
   const messages={1:"Location permission was denied. Search for your barangay below.",2:"Your location could not be determined. Search for your barangay below.",3:"Location lookup timed out. Search for your barangay below."};
   document.getElementById("locationMessage").textContent=messages[err.code]||"Location is unavailable.";
  },{enableHighAccuracy:true,timeout:10000,maximumAge:60000});
 };
 if(!navigator.geolocation){document.getElementById("locationMessage").textContent="Geolocation is unavailable. Search for your barangay below.";return;}
 start();
 setTimeout(()=>locationMap.invalidateSize(),100);
});
document.getElementById("barangaySearch").addEventListener("input",e=>{
 const term=e.target.value.toLowerCase();
 const match=Object.keys(barangayCoordinates).find(name=>name.toLowerCase().includes(term));
 if(match){barangaySelect.value=match;}
});
const CATS = @json($cats);
const $ = id => document.getElementById(id);
let selected = @json(array_values((array) old('categories', $draft['categories'] ?? [])));
let dismissed = new Set();

function detect(){
  const t = $("concern").value.toLowerCase();
  const found = [];
  Object.entries(CATS).forEach(([k,c])=>{
    const hits = (c.kw || []).filter(w=>t.includes(w));
    if(hits.length){
      found.push(...hits);
      if(!dismissed.has(k) && !selected.includes(k)) selected.push(k);
    }
  });
  const detector = $("detectedFrom");
  if(found.length){
    detector.style.display="block";
    detector.querySelector("b").textContent=[...new Set(found)].join(", ");
  } else {
    detector.style.display="none";
  }
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
  const length=$("concern").value.length;
  $("count").textContent = length.toLocaleString();
  if(length>=1800&&length<2000) window.UI?.announce("Concern text is at 90 percent of the character limit.");
  if(length>=2000) window.UI?.announce("Concern text has reached 2,000 characters.");
  detect(); render();
});
$("photo").addEventListener("change", e=>{
  const f = e.target.files[0];
  if(f){
    const scan=$("photoScan");
    scan.textContent="Scanning for viruses...";
    const reader=new FileReader();
    reader.onload=()=>{
      const bytes=new Uint8Array(reader.result);
      const isJpeg=bytes[0]===255&&bytes[1]===216&&bytes[2]===255;
      const isPng=bytes[0]===137&&bytes[1]===80&&bytes[2]===78&&bytes[3]===71;
      if(/\.[^./]+\.[^./]+$/i.test(f.name)){scan.textContent="Blocked: double file extensions are not allowed.";e.target.value="";return;}
      if(!isJpeg&&!isPng){scan.textContent="Blocked: file signature does not match JPG or PNG.";e.target.value="";return;}
      let asText="";
      for(let i=0;i<Math.min(bytes.length,4096);i++)asText+=String.fromCharCode(bytes[i]);
      if(asText.toLowerCase().includes("eicar")){scan.textContent="Blocked: infected test file detected (EICAR).";e.target.value="";return;}
      setTimeout(()=>scan.textContent="Clean. This is a simulated virus scan.",650);
    };
    reader.readAsArrayBuffer(f);
  }
  const preview = $("photoPreview");
  const image = $("photoImage");
  if(f && f.size > 5*1024*1024){ alert("Photo must be 5 MB or smaller."); e.target.value = ""; return; }
  if(f && !["image/jpeg","image/png"].includes(f.type)){ alert("Please upload a JPG or PNG photo."); e.target.value = ""; return; }
  $("photoText").textContent = f ? f.name + " • " + (f.size/1048576).toFixed(1) + " MB" : "JPG or PNG • Up to 5 MB • No photo selected";
  if(f){
    if(image.dataset.previewUrl) URL.revokeObjectURL(image.dataset.previewUrl);
    const url = URL.createObjectURL(f);
    image.dataset.previewUrl = url;
    image.src = url;
    preview.hidden = false;
  }
});
$("count").textContent = $("concern").value.length.toLocaleString();
render();
</script>
@endpush
