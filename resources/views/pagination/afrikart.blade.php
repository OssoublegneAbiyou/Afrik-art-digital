{{-- Pagination du site (s'adapte à l'univers via CSS : .pager). --}}
@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="pager__link pager__link--edge" aria-disabled="true">← Précédent</span>
        @else
            <a class="pager__link pager__link--edge" href="{{ $paginator->previousPageUrl() }}" rel="prev">← Précédent</a>
        @endif

        <ul class="pager__pages" role="list">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="pager__gap" aria-hidden="true">…</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="pager__link" aria-current="page"><span class="sr-only">Page </span>{{ $page }}</span>
                            @else
                                <a class="pager__link" href="{{ $url }}"><span class="sr-only">Page </span>{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ul>

        @if ($paginator->hasMorePages())
            <a class="pager__link pager__link--edge" href="{{ $paginator->nextPageUrl() }}" rel="next">Suivant →</a>
        @else
            <span class="pager__link pager__link--edge" aria-disabled="true">Suivant →</span>
        @endif
    </nav>
@endif
