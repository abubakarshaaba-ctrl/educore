@extends('layouts.super')

@section('title', 'Academic Content Repository')

@section('content')
<div class="repo-shell">
    <nav class="repo-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('super.curriculum-sources.index') }}">Academic Repository</a>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        <span>Resources</span>
    </nav>

    <header class="repo-page-head">
        <div>
            <h1>Content library</h1>
            <p>Sources used by EduCore Lesson Planner.</p>
        </div>
        <a href="{{ route('super.curriculum-sources.create') }}" class="repo-button repo-button-primary">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5"/></svg>
            <span>Import archive</span>
        </a>
    </header>

    @include('curriculum-sources._navigation')

    @if(session('success'))
        <div class="repo-notice repo-notice-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="repo-notice repo-notice-error">{{ $errors->first() }}</div>
    @endif

    <section class="repo-metrics" aria-label="Repository summary">
        <article class="repo-metric">
            <span class="repo-metric-icon blue"><svg viewBox="0 0 24 24"><path d="M6 5h12v16H6zM9 5V3h6v2"/></svg></span>
            <div><strong>{{ number_format($analytics['resources']) }}</strong><b>Resources</b><small>Total items</small></div>
        </article>
        <article class="repo-metric">
            <span class="repo-metric-icon green"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg></span>
            <div><strong>{{ number_format($analytics['indexed']) }}</strong><b>Indexed</b><small>Ready to use</small></div>
        </article>
        <article class="repo-metric">
            <span class="repo-metric-icon amber"><svg viewBox="0 0 24 24"><path d="M6 3h12v18H6zM9 8h6M9 12h6M9 16h4"/></svg></span>
            <div><strong>{{ number_format($analytics['review']) }}</strong><b>In review</b><small>Pending review</small></div>
        </article>
        <article class="repo-metric">
            <span class="repo-metric-icon red"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 17h.01"/></svg></span>
            <div><strong>{{ number_format($analytics['failed']) }}</strong><b>Failed</b><small>Needs attention</small></div>
        </article>
        <article class="repo-metric">
            <span class="repo-metric-icon violet"><svg viewBox="0 0 24 24"><path d="M5 7h14v13H5zM4 4h16v3H4zM9 11h6"/></svg></span>
            <div><strong>{{ number_format($analytics['imports']) }}</strong><b>Imports</b><small>Total imports</small></div>
        </article>
    </section>

    @php($hasFilters = request()->filled('class') || request()->filled('subject') || request()->filled('term'))
    <form method="GET" action="{{ route('super.curriculum-sources.index') }}" class="repo-card" id="repositoryFilterForm">
        <div class="repo-toolbar">
            <label class="repo-search">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                <input name="search" value="{{ request('search') }}" placeholder="Search resources..." aria-label="Search resources">
            </label>
            <button type="button" class="repo-button repo-button-outline" id="toggleRepositoryFilters" aria-expanded="{{ $hasFilters ? 'true' : 'false' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                Filters
            </button>
            @if(request()->hasAny(['search', 'class', 'subject', 'term']))
                <a href="{{ route('super.curriculum-sources.index') }}" class="repo-button repo-button-outline">Clear</a>
            @endif
        </div>
        <div class="repo-filter-panel" id="repositoryFilters" @unless($hasFilters) hidden @endunless>
            <div class="repo-filter-grid">
                <div class="repo-field">
                    <label for="resourceClass">Class folder</label>
                    <select id="resourceClass" name="class">
                        <option value="">All classes</option>
                        @foreach($folders['classes'] as $folder)<option value="{{ $folder }}" @selected(request('class') === $folder)>{{ $folder }}</option>@endforeach
                    </select>
                </div>
                <div class="repo-field">
                    <label for="resourceSubject">Subject folder</label>
                    <select id="resourceSubject" name="subject">
                        <option value="">All subjects</option>
                        @foreach($folders['subjects'] as $folder)<option value="{{ $folder }}" @selected(request('subject') === $folder)>{{ $folder }}</option>@endforeach
                    </select>
                </div>
                <div class="repo-field">
                    <label for="resourceTerm">Term folder</label>
                    <select id="resourceTerm" name="term">
                        <option value="">All terms</option>
                        @foreach($folders['terms'] as $folder)<option value="{{ $folder }}" @selected(request('term') === $folder)>{{ $folder }}</option>@endforeach
                    </select>
                </div>
            </div>
            <button class="repo-button repo-button-primary" style="margin-top:10px">Apply filters</button>
        </div>
    </form>

    <section class="repo-card repo-library">
        <div class="repo-list-head">
            <h2>Resources</h2>
            <span>{{ number_format($sources->total()) }} {{ \Illuminate\Support\Str::plural('item', $sources->total()) }}</span>
        </div>

        @forelse($sources as $source)
            @php
                $meta = $source->metadata ?? [];
                $extension = strtoupper(pathinfo($source->original_filename ?? '', PATHINFO_EXTENSION) ?: 'FILE');
                $classLabel = $meta['class_label'] ?? 'Unmapped class';
                $subjectLabel = $meta['subject_label'] ?? 'Unmapped subject';
                $termLabel = $meta['term_label'] ?? 'Unmapped term';
                $statusLabel = $source->is_active ? 'Active' : ($source->needs_review ? 'In review' : 'Inactive');
                $statusClass = $source->is_active ? 'active' : ($source->needs_review ? 'review' : 'inactive');
            @endphp
            <article class="repo-resource">
                <span class="repo-file-mark">{{ substr($extension, 0, 4) }}</span>
                <div class="repo-resource-copy">
                    <div class="repo-resource-path">
                        <span>{{ $classLabel }}</span><i></i><span>{{ $subjectLabel }}</span><i></i><span>{{ $termLabel }}</span>
                    </div>
                    <h3>{{ $source->title }}</h3>
                    <p>{{ $source->original_filename }} · {{ number_format($source->fragments_count) }} {{ \Illuminate\Support\Str::plural('section', $source->fragments_count) }}</p>
                </div>
                <div class="repo-resource-actions">
                    <span class="repo-status {{ $statusClass }}">{{ $statusLabel }}</span>
                    @if(!$source->is_active && $source->extraction_status === 'extracted')
                        <form method="POST" action="{{ route('super.curriculum-sources.activate', $source) }}">@csrf<button class="repo-inline-action">Activate</button></form>
                    @elseif($source->is_active)
                        <form method="POST" action="{{ route('super.curriculum-sources.deactivate', $source) }}">@csrf<button class="repo-inline-action">Deactivate</button></form>
                    @endif
                    <form method="POST" action="{{ route('super.curriculum-sources.destroy', $source) }}" onsubmit="return confirm('Remove this resource?')">
                        @csrf @method('DELETE')
                        <button class="repo-icon-button danger" aria-label="Remove {{ $source->title }}" title="Remove">
                            <svg viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5"/></svg>
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <div class="repo-empty">
                <svg class="repo-empty-art" viewBox="0 0 240 150" aria-hidden="true">
                    <defs><linearGradient id="folderBg" x1="0" x2="1"><stop stop-color="#eef5ff"/><stop offset="1" stop-color="#dcecff"/></linearGradient></defs>
                    <ellipse cx="120" cy="132" rx="78" ry="7" fill="#edf3fa"/>
                    <circle cx="60" cy="55" r="2" fill="#d79a21"/><path d="M60 47v16M52 55h16" stroke="#d79a21" stroke-width="1" opacity=".7"/>
                    <circle cx="187" cy="42" r="2" fill="#5e9fe5"/><path d="M187 35v14M180 42h14" stroke="#5e9fe5" stroke-width="1" opacity=".65"/>
                    <rect x="68" y="39" width="104" height="72" rx="12" fill="url(#folderBg)"/>
                    <rect x="78" y="49" width="84" height="52" rx="8" fill="#fff" stroke="#cfe0f3"/>
                    <rect x="87" y="58" width="21" height="18" rx="3" fill="#9bc3f0"/>
                    <rect x="114" y="59" width="35" height="5" rx="2.5" fill="#d7e5f4"/><rect x="114" y="69" width="28" height="4" rx="2" fill="#e4edf6"/>
                    <path d="M101 87h42l7-8h21v31H96V92a5 5 0 0 1 5-5z" fill="#4e91dc"/>
                    <circle cx="166" cy="102" r="22" fill="#fff" stroke="#4e91dc" stroke-width="6"/><path d="m182 118 16 16" stroke="#4e91dc" stroke-width="7" stroke-linecap="round"/>
                </svg>
                <h2>{{ request()->hasAny(['search', 'class', 'subject', 'term']) ? 'No resources found' : 'No resources found' }}</h2>
                <p>{{ request()->hasAny(['search', 'class', 'subject', 'term']) ? 'Adjust the search or filters.' : 'Import an archive to get started.' }}</p>
                @unless(request()->hasAny(['search', 'class', 'subject', 'term']))
                    <a href="{{ route('super.curriculum-sources.create') }}" class="repo-button repo-button-primary">
                        <svg viewBox="0 0 24 24"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5"/></svg>
                        Import archive
                    </a>
                @endunless
            </div>
        @endforelse

        @if($sources->hasPages())<div class="repo-pagination">{{ $sources->links() }}</div>@endif
    </section>
</div>

@include('curriculum-sources._styles')

@push('scripts')
<script>
(() => {
    const button = document.getElementById('toggleRepositoryFilters');
    const panel = document.getElementById('repositoryFilters');
    if (!button || !panel) return;
    button.addEventListener('click', () => {
        panel.hidden = !panel.hidden;
        button.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
    });
})();
</script>
@endpush
@endsection
