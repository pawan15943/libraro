{{-- Admin portal pagination (design system). Use: $items->links('pagination::ui') --}}
@if ($paginator->hasPages())
    <nav class="ui-pager" role="navigation" aria-label="Pagination">
        <span class="ui-pager__info">{{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }} of {{ number_format($paginator->total()) }}</span>
        <ul class="ui-pager__list">
            @if ($paginator->onFirstPage())
                <li><span class="ui-pager__btn is-disabled" aria-disabled="true" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></span></li>
            @else
                <li><a class="ui-pager__btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></a></li>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="ui-pager__gap"><span>…</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="ui-pager__btn is-active" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li class="{{ abs($page - $paginator->currentPage()) > 1 && $page != 1 && $page != $paginator->lastPage() ? 'ui-pager__far' : '' }}"><a class="ui-pager__btn" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <li><a class="ui-pager__btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></a></li>
            @else
                <li><span class="ui-pager__btn is-disabled" aria-disabled="true" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></span></li>
            @endif
        </ul>
    </nav>
@endif
