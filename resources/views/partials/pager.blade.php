{{-- Previous / Next buttons for a paginator. Usage: @include('partials.pager', ['p' => $concerns]) --}}
@if($p->hasPages())
<div class="pager" style="padding:12px 18px">
    <span>Page {{ $p->currentPage() }} of {{ $p->lastPage() }} • {{ $p->total() }} in total</span>
    <span class="row gap">
        <a class="btn {{ $p->onFirstPage() ? 'off' : '' }}" href="{{ $p->previousPageUrl() ?? '#' }}">Previous</a>
        <a class="btn {{ $p->hasMorePages() ? '' : 'off' }}" href="{{ $p->nextPageUrl() ?? '#' }}">Next</a>
    </span>
</div>
@endif
