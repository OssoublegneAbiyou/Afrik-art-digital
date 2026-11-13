{{-- Pagination de l'administration, en français : $paginator->links('admin.partials.pager') --}}
@if ($paginator->hasPages())
    <nav class="adm-row" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="adm-btn adm-btn--ghost adm-btn--sm" aria-disabled="true">‹ Précédent</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="adm-btn adm-btn--ghost adm-btn--sm" rel="prev">‹ Précédent</a>
        @endif

        <span class="adm-muted">Page {{ $paginator->currentPage() }} sur {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="adm-btn adm-btn--ghost adm-btn--sm" rel="next">Suivant ›</a>
        @else
            <span class="adm-btn adm-btn--ghost adm-btn--sm" aria-disabled="true">Suivant ›</span>
        @endif
    </nav>
@endif
