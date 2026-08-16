@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" style="display:flex;gap:8px;align-items:center;justify-content:flex-end">
        @if ($paginator->onFirstPage())
            <span class="btn btn-ghost" style="opacity:.45;cursor:not-allowed;padding:6px 12px">قبلی</span>
        @else
            <a class="btn btn-ghost" style="padding:6px 12px" href="{{ $paginator->previousPageUrl() }}" rel="prev">قبلی</a>
        @endif
        @if ($paginator->hasMorePages())
            <a class="btn btn-ghost" style="padding:6px 12px" href="{{ $paginator->nextPageUrl() }}" rel="next">بعدی</a>
        @else
            <span class="btn btn-ghost" style="opacity:.45;cursor:not-allowed;padding:6px 12px">بعدی</span>
        @endif
    </nav>
@endif
