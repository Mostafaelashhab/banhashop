@if ($paginator->hasPages())
    <nav class="pagination" aria-label="التنقل بين الصفحات">
        @if ($paginator->onFirstPage())
            <span class="pagination__item pagination__item--disabled" aria-hidden="true">
                <x-ui.icon name="chevron-end" :size="16" />
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination__item" aria-label="الصفحة السابقة">
                <x-ui.icon name="chevron-end" :size="16" />
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination__item pagination__item--disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination__item" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pagination__item" aria-label="صفحة {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination__item" aria-label="الصفحة التالية">
                <x-ui.icon name="chevron-start" :size="16" />
            </a>
        @else
            <span class="pagination__item pagination__item--disabled" aria-hidden="true">
                <x-ui.icon name="chevron-start" :size="16" />
            </span>
        @endif
    </nav>
@endif
