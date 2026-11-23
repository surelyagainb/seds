@extends('layouts.admin')
@section('title','Barangays')
@section('heading','Barangays')
@section('lead','Maintain citizen and staff barangay records.')
@section('content')
<form class="card row-split" method="GET">
  <input type="search" name="q" value="{{ $q }}" placeholder="Search barangay name" aria-label="Search barangays">
  <select name="status"><option value="">All statuses</option><option value="active" @selected($status==='active')>Active</option><option value="inactive" @selected($status==='inactive')>Inactive</option></select>
  <button type="submit" class="btn">Filter</button><a class="btn" href="{{ route('admin.barangays.index') }}">Reset</a>
  <button type="button" class="btn primary" data-open="createBarangay">Add barangay</button>
</form>
<section class="card">
<table class="table responsive-table" data-sortable>
<caption>Municipal barangays</caption>
<thead><tr><th scope="col" data-sort-type="text">Name</th><th scope="col" data-sort-type="number">Latitude</th><th scope="col" data-sort-type="number">Longitude</th><th scope="col" data-sort-type="text">Status</th><th scope="col">Actions</th></tr></thead>
<tbody>
@forelse($barangays as $barangay)
<tr><td data-label="Name"><b>{{ $barangay->name }}</b></td><td data-label="Latitude">{{ number_format($barangay->latitude,7) }}</td><td data-label="Longitude">{{ number_format($barangay->longitude,7) }}</td><td data-label="Status"><span class="pill {{ $barangay->is_active?'green':'grey' }}">{{ $barangay->is_active?'Active':'Inactive' }}</span></td>
<td data-label="Actions"><button type="button" class="btn small-btn" data-open="edit-b-{{ $barangay->id }}">Edit</button><form class="inline" method="POST" action="{{ route('admin.barangays.toggle',$barangay) }}">@csrf<button type="submit" class="btn small-btn">{{ $barangay->is_active?'Deactivate':'Reactivate' }}</button></form>
@if(! $barangay->is_active)<form class="inline" method="POST" action="{{ route('admin.barangays.destroy',$barangay) }}">@csrf @method('DELETE')<button type="submit" class="btn small-btn danger">Delete</button></form>@endif</td></tr>
@empty<tr><td colspan="5" class="empty">No barangays match the filters.</td></tr>@endforelse
</tbody></table>
</section>
@foreach($barangays as $barangay)
<dialog id="edit-b-{{ $barangay->id }}" class="dialog"><form method="POST" action="{{ route('admin.barangays.update',$barangay) }}" class="stack">@csrf @method('PUT')<h2>Edit barangay</h2>
<label for="bn-{{ $barangay->id }}">Name *</label><input id="bn-{{ $barangay->id }}" name="name" value="{{ $barangay->name }}" required>
<label for="bl-{{ $barangay->id }}">Latitude *</label><input id="bl-{{ $barangay->id }}" name="latitude" value="{{ $barangay->latitude }}" required>
<label for="bo-{{ $barangay->id }}">Longitude *</label><input id="bo-{{ $barangay->id }}" name="longitude" value="{{ $barangay->longitude }}" required>
<div class="actions"><button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button><button type="submit" class="btn primary">Save</button></div></form></dialog>
@endforeach
<dialog id="createBarangay" class="dialog"><form method="POST" action="{{ route('admin.barangays.store') }}" class="stack">@csrf<h2>Add barangay</h2>
<label for="bname">Name *</label><input id="bname" name="name" required>
<label for="blat">Latitude *</label><input id="blat" name="latitude" required placeholder="13.1770">
<label for="blng">Longitude *</label><input id="blng" name="longitude" required placeholder="121.2780">
<div class="actions"><button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button><button type="submit" class="btn primary">Add barangay</button></div></form></dialog>
@endsection
