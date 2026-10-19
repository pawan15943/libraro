@if ($paginator->hasPages())
    <nav class="lb-pager" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="lb-pager-btn is-disabled" aria-disabled="true"><x-lb-icon name="chevron-left" /><span>Previous</span></span>
        @else
            <a class="lb-pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev"><x-lb-icon name="chevron-left" /><span>Previous</span></a>
        @endif
        <span class="lb-pager-pages">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="lb-pager-dots">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="lb-pager-num is-on" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="lb-pager-num" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </span>
        @if ($paginator->hasMorePages())
            <a class="lb-pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next"><span>Next</span><x-lb-icon name="chevron-right" /></a>
        @else
            <span class="lb-pager-btn is-disabled" aria-disabled="true"><span>Next</span><x-lb-icon name="chevron-right" /></span>
        @endif
    </nav>
@endif
