@if($paginator->hasPages())
    <nav class="pagination-controls" aria-label="Paginação">
        @if($paginator->onFirstPage())
            <span class="btn btn-outline-violet disabled">← Anterior</span>
        @else
            <a class="btn btn-outline-violet" href="{{ $paginator->previousPageUrl() }}">← Anterior</a>
        @endif

        <span class="pagination-summary">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>

        @if($paginator->hasMorePages())
            <a class="btn btn-outline-violet" href="{{ $paginator->nextPageUrl() }}">Próxima →</a>
        @else
            <span class="btn btn-outline-violet disabled">Próxima →</span>
        @endif
    </nav>
@endif
