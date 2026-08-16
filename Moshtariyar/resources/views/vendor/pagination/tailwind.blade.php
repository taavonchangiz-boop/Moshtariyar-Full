@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <div class="muted" style="font-size:.85rem">
            نمایش
            <span>@fa($paginator->firstItem())</span>
            تا
            <span>@fa($paginator->lastItem())</span>
            از
            <span>@fa($paginator->total())</span>
            نتیجه
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
            @if ($paginator->onFirstPage())
                <span class="btn btn-ghost" style="opacity:.45;cursor:not-allowed;padding:6px 12px">قبلی</span>
            @else
                <a class="btn btn-ghost" style="padding:6px 12px" href="{{ $paginator->previousPageUrl() }}" rel="prev">قبلی</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="muted" style="padding:6px 8px">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn" style="padding:6px 12px">@fa($page)</span>
                        @else
                            <a class="btn btn-ghost" style="padding:6px 12px" href="{{ $url }}">@fa($page)</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="btn btn-ghost" style="padding:6px 12px" href="{{ $paginator->nextPageUrl() }}" rel="next">بعدی</a>
            @else
                <span class="btn btn-ghost" style="opacity:.45;cursor:not-allowed;padding:6px 12px">بعدی</span>
            @endif
        </div>
    </nav>
@endif
