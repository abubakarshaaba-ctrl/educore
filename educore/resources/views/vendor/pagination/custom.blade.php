@if ($paginator->hasPages())
<nav style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-top:14px;font-family:inherit" aria-label="Pagination">
    <div style="font-size:11px;color:var(--slate-light,#94A3B8)">
        Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
        @if(method_exists($paginator,'total')) — {{ $paginator->total() }} result{{ $paginator->total() === 1 ? '' : 's' }} @endif
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
        @if ($paginator->onFirstPage())
            <span style="display:inline-flex;align-items:center;padding:7px 12px;font-size:12px;font-weight:600;border-radius:7px;border:1px solid var(--border,#E2E8F0);background:#F1F5F9;color:var(--slate-light,#94A3B8);cursor:default">← Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" style="display:inline-flex;align-items:center;padding:7px 12px;font-size:12px;font-weight:600;border-radius:7px;border:1px solid var(--border,#E2E8F0);background:white;color:var(--midnight,#1E293B);text-decoration:none">← Previous</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span style="display:inline-flex;align-items:center;padding:7px 11px;font-size:12px;color:var(--slate-light,#94A3B8)">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span style="display:inline-flex;align-items:center;padding:7px 12px;font-size:12px;font-weight:700;border-radius:7px;border:1px solid var(--indigo,#2563EB);background:var(--indigo,#2563EB);color:white;cursor:default">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="display:inline-flex;align-items:center;padding:7px 12px;font-size:12px;font-weight:600;border-radius:7px;border:1px solid var(--border,#E2E8F0);background:white;color:var(--midnight,#1E293B);text-decoration:none">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" style="display:inline-flex;align-items:center;padding:7px 12px;font-size:12px;font-weight:600;border-radius:7px;border:1px solid var(--border,#E2E8F0);background:white;color:var(--midnight,#1E293B);text-decoration:none">Next →</a>
        @else
            <span style="display:inline-flex;align-items:center;padding:7px 12px;font-size:12px;font-weight:600;border-radius:7px;border:1px solid var(--border,#E2E8F0);background:#F1F5F9;color:var(--slate-light,#94A3B8);cursor:default">Next →</span>
        @endif
    </div>
</nav>
@endif
