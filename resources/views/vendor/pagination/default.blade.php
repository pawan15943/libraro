<!-- resources/views/vendor/pagination/custom-pagination.blade.php -->
@if ($paginator->hasPages())
    <nav class="flex justify-between items-center mt-2 mb-2">
        {{-- Previous Page Link --}}
        <div>
            @if ($paginator->onFirstPage())
                <span class="px-4 py-2 text-gray-500 bg-gray-200 border rounded-md cursor-not-allowed">« Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-4 py-2 text-blue-500 bg-white border rounded-md hover:bg-blue-50">« Previous</a>
            @endif
        </div>

        {{-- Pagination Elements --}}
        <div class="flex items-center space-x-1">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-3 py-2 text-gray-500">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="px-3 py-2 text-white bg-blue-500 border border-blue-500 rounded-md">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-2 text-blue-500 bg-white border rounded-md hover:bg-blue-50">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        {{-- Next Page Link --}}
        <div>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-4 py-2 text-blue-500 bg-white border rounded-md hover:bg-blue-50">Next »</a>
            @else
                <span class="px-4 py-2 text-gray-500 bg-gray-200 border rounded-md cursor-not-allowed">Next »</span>
            @endif
        </div>
    </nav>
@endif
