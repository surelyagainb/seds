@extends('layouts.staff')
@section('title','Concern Categories')
@section('crumb','Categories')
@section('content')
<div class="page-head"><div><h1>Concern categories</h1><p>Keywords and committees used to classify citizen reports.</p></div><button type="button" class="btn primary" data-open="newCategory">+ Add category</button></div>
<form class="filters card" method="GET">
  <div><label for="cq">Search</label><input type="search" id="cq" name="q" value="{{ $q }}" placeholder="Category name or key"></div>
  <div><label for="cs">Status</label><select id="cs" name="status"><option value="">All statuses</option><option value="active" @selected($status==='active')>Active</option><option value="inactive" @selected($status==='inactive')>Inactive</option></select></div>
  <div class="btns"><button class="btn primary" type="submit">Filter</button><a class="btn" href="{{ route('staff.categories.index') }}">Reset</a></div>
</form>
<section class="card flush">
  <table class="table" data-sortable>
    <caption>Concern categories</caption>
    <thead><tr><th scope="col">Name</th><th scope="col">Key</th><th scope="col">English keywords</th><th scope="col">Filipino keywords</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
    <tbody>
    @forelse($categories as $category)
      <tr>
        <td data-label="Name"><b>{{ $category->name }}</b></td><td data-label="Key">{{ $category->key }}</td>
        <td data-label="English keywords">{{ implode(', ', $category->keywords_en ?: []) ?: '—' }}</td>
        <td data-label="Filipino keywords">{{ implode(', ', $category->keywords_fil ?: []) ?: '—' }}</td>
        <td data-label="Status"><span class="badge {{ $category->is_active?'green':'gray' }}">{{ $category->is_active?'Active':'Inactive' }}</span></td>
        <td data-label="Actions"><button type="button" class="btn" data-open="edit-{{ $category->id }}">Edit</button>
          <button type="button" class="btn" data-open="assign-{{ $category->id }}">Assign</button>
          @if($category->key !== 'other')<form class="inline-form" method="POST" action="{{ route('staff.categories.toggle',$category) }}" style="margin-left:6px">@csrf<button type="submit" class="btn">{{ $category->is_active?'Deactivate':'Reactivate' }}</button></form>@else<span class="muted small"> Cannot be deleted</span>@endif
        </td>
      </tr>
    @empty<tr><td colspan="6" class="empty">No categories match your filters.</td></tr>@endforelse
    </tbody>
  </table>
</section>
@foreach($categories as $category)
<div class="modal-back" id="assign-{{ $category->id }}" role="dialog" aria-modal="true" aria-labelledby="assign-title-{{ $category->id }}">
  <form class="modal" method="POST" action="{{ route('staff.categories.assign',$category) }}">@csrf
    <div class="modal-head"><h2 id="assign-title-{{ $category->id }}">Assigned SB members</h2><button type="button" data-close aria-label="Close">×</button></div>
    @forelse($members as $member)
      <label class="check"><input type="checkbox" name="member_ids[]" value="{{ $member->id }}" {{ in_array($category->key,$member->assigned_categories ?: [],true)?'checked':'' }}> {{ $member->name }}</label>
    @empty
      <p class="muted">No SB member accounts exist yet. Create one from Admin.</p>
    @endforelse
    <div class="modal-actions"><button type="button" class="btn" data-close>Cancel</button><button type="submit" class="btn primary">Save assignments</button></div>
  </form>
</div>
<div class="modal-back" id="edit-{{ $category->id }}" role="dialog" aria-modal="true" aria-labelledby="edit-title-{{ $category->id }}">
  <form class="modal" method="POST" action="{{ route('staff.categories.update',$category) }}">@csrf @method('PUT')
    <div class="modal-head"><h2 id="edit-title-{{ $category->id }}">Edit category</h2><button type="button" data-close aria-label="Close">×</button></div>
    <label for="ename-{{ $category->id }}">Name *</label><input id="ename-{{ $category->id }}" name="name" required value="{{ $category->name }}">
    <label for="ecolor-{{ $category->id }}">Color</label><input id="ecolor-{{ $category->id }}" name="color" value="{{ $category->color }}">
    <label for="een-{{ $category->id }}">English keywords</label><input id="een-{{ $category->id }}" name="keywords_en" value="{{ implode(', ', $category->keywords_en ?: []) }}">
    <label for="efil-{{ $category->id }}">Filipino keywords</label><input id="efil-{{ $category->id }}" name="keywords_fil" value="{{ implode(', ', $category->keywords_fil ?: []) }}" {{ $category->key==='other'?'disabled':'' }}>
    <div class="modal-actions"><button type="button" class="btn" data-close>Cancel</button><button type="submit" class="btn primary">Save category</button></div>
  </form>
</div>
@endforeach
@endsection
@push('modals')
<div class="modal-back" id="newCategory" role="dialog" aria-modal="true" aria-labelledby="newCategoryTitle">
 <form class="modal" method="POST" action="{{ route('staff.categories.store') }}">@csrf
  <div class="modal-head"><h2 id="newCategoryTitle">New category</h2><button type="button" data-close aria-label="Close">×</button></div>
  <label for="cname">Name *</label><input id="cname" name="name" required placeholder="Required">
  <label for="ckey">Key *</label><input id="ckey" name="key" required placeholder="Required, use letters and underscores">
  <label for="ccolor">Color</label><input id="ccolor" name="color" value="#64748b">
  <label for="cen">English keywords</label><input id="cen" name="keywords_en" placeholder="garbage, waste">
  <label for="cfil">Filipino keywords</label><input id="cfil" name="keywords_fil" placeholder="basura, kalat">
  <div class="modal-actions"><button type="button" class="btn" data-close>Cancel</button><button type="submit" class="btn primary">Add category</button></div>
 </form>
</div>
@endpush
