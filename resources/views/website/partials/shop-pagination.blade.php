@if ($paginator->hasPages())
    <nav class="gs-pages" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="gs-page gs-page-nav is-disabled">‹ Prev</span>
        @else
            <a class="gs-page gs-page-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev" data-no-loader>‹ Prev</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="gs-page is-disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="gs-page is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="gs-page" href="{{ $url }}" data-no-loader>{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="gs-page gs-page-nav" href="{{ $paginator->nextPageUrl() }}" rel="next" data-no-loader>Next ›</a>
        @else
            <span class="gs-page gs-page-nav is-disabled">Next ›</span>
        @endif
    </nav>
@endif
