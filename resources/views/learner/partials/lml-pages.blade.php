{{-- Pagination in the members-list style (lb-members.css / lb-members.js load these links over AJAX). Pass the paginator as $p. --}}
@if ($p->lastPage() > 1)
<ul class="paginations lml-pages">
    <li><a href="{{ $p->onFirstPage() ? '#' : $p->previousPageUrl() }}" class="{{ $p->onFirstPage() ? 'is-off' : '' }}" aria-label="Previous page"><x-lb-icon name="chevron-left" />Prev</a></li>
    @if ($p->currentPage() > 3)
    <li><a href="{{ $p->url(1) }}">1</a></li>
    <li><span>…</span></li>
    @endif
    @for ($i = max(1, $p->currentPage() - 2); $i <= min($p->lastPage(), $p->currentPage() + 2); $i++)
    <li><a href="{{ $p->url($i) }}" class="{{ $p->currentPage() == $i ? 'active' : '' }}" @if($p->currentPage() == $i) aria-current="page" @endif>{{ $i }}</a></li>
    @endfor
    @if ($p->currentPage() < $p->lastPage() - 2)
    <li><span>…</span></li>
    <li><a href="{{ $p->url($p->lastPage()) }}">{{ $p->lastPage() }}</a></li>
    @endif
    <li><a href="{{ $p->hasMorePages() ? $p->nextPageUrl() : '#' }}" class="{{ $p->hasMorePages() ? '' : 'is-off' }}" aria-label="Next page">Next<x-lb-icon name="chevron-right" /></a></li>
</ul>
@endif
