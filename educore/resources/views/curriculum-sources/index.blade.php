@extends('layouts.super')

@section('title', 'Academic Content Repository')

@section('content')
<div class="repo-shell">
    <nav class="repo-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('super.curriculum-sources.index') }}">Academic Repository</a>
        <svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg><span>Resources</span>
    </nav>

    <header class="repo-page-head">
        <div><h1>Content library</h1><p>Class, term and subject resources used by Lesson Planner.</p></div>
        <a href="{{ route('super.curriculum-sources.create') }}" class="repo-button repo-button-primary">
            <svg viewBox="0 0 24 24"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5"/></svg><span>Import archive</span>
        </a>
    </header>

    @include('curriculum-sources._navigation')

    @if(session('success'))<div class="repo-notice repo-notice-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="repo-notice repo-notice-error">{{ $errors->first() }}</div>@endif

    <section class="repo-metrics" aria-label="Repository summary">
        @foreach([
            ['resources','blue','M6 5h12v16H6zM9 5V3h6v2','Resources','Total items'],
            ['indexed','green','M4 12l5 5L20 6','Indexed','Ready to use'],
            ['review','amber','M6 3h12v18H6zM9 8h6M9 12h6M9 16h4','In review','Pending review'],
            ['failed','red','M12 7v6M12 17h.01','Failed','Needs attention'],
            ['imports','violet','M5 7h14v13H5zM4 4h16v3H4zM9 11h6','Imports','Total imports']
        ] as [$key,$colour,$path,$label,$note])
            <article class="repo-metric">
                <span class="repo-metric-icon {{ $colour }}"><svg viewBox="0 0 24 24"><path d="{{ $path }}"/></svg></span>
                <div><strong>{{ number_format($analytics[$key]) }}</strong><b>{{ $label }}</b><small>{{ $note }}</small></div>
            </article>
        @endforeach
    </section>

    <form method="GET" action="{{ route('super.curriculum-sources.index') }}" class="repo-card repo-library-toolbar">
        <label class="repo-search">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
            <input name="search" value="{{ request('search') }}" placeholder="Search resource titles and content" aria-label="Search resources">
        </label>
        <div class="repo-field repo-subject-filter">
            <label class="sr-only" for="resourceSubject">Filter subject</label>
            <select id="resourceSubject" name="subject">
                <option value="">All subjects</option>
                @foreach($folders['subjects'] as $folder)<option value="{{ $folder }}" @selected(request('subject') === $folder)>{{ $folder }}</option>@endforeach
            </select>
        </div>
        <button class="repo-button repo-button-outline">Filter</button>
        @if(request()->hasAny(['search','subject']))<a href="{{ route('super.curriculum-sources.index') }}" class="repo-button repo-button-outline">Clear</a>@endif
    </form>

    @if($groups->isNotEmpty())
        <section class="repo-class-section" aria-labelledby="classFoldersHeading">
            <div class="repo-section-heading"><div><h2 id="classFoldersHeading">Classes</h2><p>Select a class to open its terms and subjects.</p></div><span>{{ $groups->count() }} groups</span></div>
            <div class="repo-class-grid" role="tablist" aria-label="Class folders">
                @foreach($groups as $classLabel => $terms)
                    @php
                        $classResources = $terms->flatten(2);
                        $classId = 'class-'.$loop->index;
                        $activeCount = $classResources->where('is_active', true)->count();
                        $failedCount = $classResources->where('extraction_status', 'failed')->count();
                    @endphp
                    <button type="button" class="repo-class-stat @if($loop->first) active @endif" data-class-target="{{ $classId }}" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        <span class="repo-class-icon"><svg viewBox="0 0 24 24"><path d="M3 7h7l2 2h9v10H3z"/></svg></span>
                        <span class="repo-class-copy"><strong>{{ $classLabel }}</strong><small>{{ number_format($classResources->count()) }} resources · {{ $terms->count() }} {{ str('term')->plural($terms->count()) }}</small></span>
                        <span class="repo-class-health"><b>{{ $activeCount }}</b> active @if($failedCount)<i>{{ $failedCount }} failed</i>@endif</span>
                    </button>
                @endforeach
            </div>
        </section>

        <form method="POST" action="{{ route('super.curriculum-sources.bulk') }}" id="bulkResourceForm">
            @csrf
            <div id="bulkResourceIds"></div>
            <div class="repo-bulk-bar" id="bulkBar" hidden>
                <div><strong id="selectedCount">0</strong><span>selected</span></div>
                <button name="action" value="activate" class="repo-button repo-bulk-activate">Activate</button>
                <button name="action" value="deactivate" class="repo-button repo-button-outline">Deactivate</button>
                <button name="action" value="delete" class="repo-button repo-button-danger">Remove</button>
                <button type="button" class="repo-bulk-clear" id="clearSelection">Clear</button>
            </div>
        </form>

        <section class="repo-hierarchy">
            @foreach($groups as $classLabel => $terms)
                @php
                    $classId = 'class-'.$loop->index;
                @endphp
                <div class="repo-class-panel" id="{{ $classId }}" data-class-panel @if(!$loop->first) hidden @endif>
                    <div class="repo-hierarchy-head">
                        <div><span>Class</span><h2>{{ $classLabel }}</h2></div>
                        <label class="repo-local-search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg><input type="search" placeholder="Filter subjects" data-subject-search></label>
                    </div>

                    <div class="repo-term-tabs" role="tablist" aria-label="Terms in {{ $classLabel }}">
                        @foreach($terms as $termLabel => $subjects)
                            @php
                                $termId = $classId.'-term-'.$loop->index;
                            @endphp
                            <button type="button" class="repo-term-tab @if($loop->first) active @endif" data-term-target="{{ $termId }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                <span>{{ $termLabel }}</span><b>{{ $subjects->flatten(1)->count() }}</b>
                            </button>
                        @endforeach
                    </div>

                    @foreach($terms as $termLabel => $subjects)
                        @php
                            $termId = $classId.'-term-'.$loop->index;
                        @endphp
                        <div class="repo-term-panel" id="{{ $termId }}" data-term-panel @if(!$loop->first) hidden @endif>
                            <div class="repo-term-summary"><div><span>Term</span><h3>{{ $termLabel }}</h3></div><p>{{ $subjects->count() }} {{ str('subject')->plural($subjects->count()) }} · {{ $subjects->flatten(1)->count() }} resources</p></div>

                            <div class="repo-subject-list">
                                @foreach($subjects as $subjectLabel => $subjectSources)
                                    @php
                                        $usable = $subjectSources->where('extraction_status','extracted')->count();
                                        $active = $subjectSources->where('is_active',true)->count();
                                        $failed = $subjectSources->where('extraction_status','failed')->count();
                                    @endphp
                                    <details class="repo-subject" data-subject-name="{{ mb_strtolower($subjectLabel) }}" @if($subjects->count() === 1) open @endif>
                                        <summary>
                                            <label class="repo-check" onclick="event.stopPropagation()"><input type="checkbox" data-subject-select aria-label="Select all {{ $subjectLabel }} resources"><span></span></label>
                                            <span class="repo-subject-mark">{{ mb_strtoupper(mb_substr($subjectLabel,0,2)) }}</span>
                                            <span class="repo-subject-copy"><strong>{{ $subjectLabel }}</strong><small>{{ $subjectSources->count() }} resources · {{ $usable }} indexed · {{ $active }} active</small></span>
                                            @if($failed)<span class="repo-subject-alert">{{ $failed }} failed</span>@endif
                                            <svg class="repo-chevron" viewBox="0 0 24 24"><path d="m8 10 4 4 4-4"/></svg>
                                        </summary>
                                        <div class="repo-subject-resources">
                                            @foreach($subjectSources as $source)
                                                @php
                                                    $extension = strtoupper(pathinfo($source->original_filename ?? '', PATHINFO_EXTENSION) ?: 'FILE');
                                                    $failedExtraction = $source->extraction_status === 'failed' || $source->fragments_count < 1;
                                                    $statusLabel = $source->is_active ? 'Active' : ($failedExtraction ? 'Extraction failed' : ($source->needs_review ? 'In review' : 'Inactive'));
                                                    $statusClass = $source->is_active ? 'active' : ($failedExtraction ? 'failed' : ($source->needs_review ? 'review' : 'inactive'));
                                                @endphp
                                                <article class="repo-resource">
                                                    <label class="repo-check"><input type="checkbox" value="{{ $source->id }}" data-resource-select aria-label="Select {{ $source->title }}"><span></span></label>
                                                    <span class="repo-file-mark">{{ substr($extension,0,4) }}</span>
                                                    <div class="repo-resource-copy"><h3>{{ $source->title }}</h3><p>{{ $source->original_filename }} · {{ number_format($source->fragments_count) }} {{ str('section')->plural($source->fragments_count) }}</p></div>
                                                    <div class="repo-resource-actions">
                                                        <span class="repo-status {{ $statusClass }}">{{ $statusLabel }}</span>
                                                        @if(!$source->is_active && !$failedExtraction)
                                                            <form method="POST" action="{{ route('super.curriculum-sources.activate',$source) }}">@csrf<button class="repo-inline-action">Activate</button></form>
                                                        @elseif($source->is_active)
                                                            <form method="POST" action="{{ route('super.curriculum-sources.deactivate',$source) }}">@csrf<button class="repo-inline-action">Deactivate</button></form>
                                                        @else
                                                            <form method="POST" action="{{ route('super.curriculum-sources.reindex',$source) }}">@csrf<button class="repo-inline-action">Re-index</button></form>
                                                        @endif
                                                        <form method="POST" action="{{ route('super.curriculum-sources.destroy',$source) }}" onsubmit="return confirm('Remove this resource?')">
                                                            @csrf @method('DELETE')
                                                            <button class="repo-icon-button danger" aria-label="Remove {{ $source->title }}"><svg viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5"/></svg></button>
                                                        </form>
                                                    </div>
                                                </article>
                                            @endforeach
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                            <div class="repo-no-subjects" hidden>No subjects match this filter.</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </section>
    @else
        <section class="repo-card repo-empty"><h2>No resources found</h2><p>Adjust the filter or import an archive.</p><a href="{{ route('super.curriculum-sources.create') }}" class="repo-button repo-button-primary">Import archive</a></section>
    @endif
</div>

@include('curriculum-sources._styles')

@push('scripts')
<script>
(() => {
    const classButtons = [...document.querySelectorAll('[data-class-target]')];
    const classPanels = [...document.querySelectorAll('[data-class-panel]')];
    classButtons.forEach(button => button.addEventListener('click', () => {
        classButtons.forEach(item => { item.classList.toggle('active', item === button); item.setAttribute('aria-selected', item === button ? 'true' : 'false'); });
        classPanels.forEach(panel => panel.hidden = panel.id !== button.dataset.classTarget);
        button.scrollIntoView({behavior:'smooth',block:'nearest',inline:'nearest'});
    }));

    classPanels.forEach(classPanel => {
        const tabs = [...classPanel.querySelectorAll('[data-term-target]')];
        const panels = [...classPanel.querySelectorAll('[data-term-panel]')];
        tabs.forEach(tab => tab.addEventListener('click', () => {
            tabs.forEach(item => { item.classList.toggle('active', item === tab); item.setAttribute('aria-selected', item === tab ? 'true' : 'false'); });
            panels.forEach(panel => panel.hidden = panel.id !== tab.dataset.termTarget;
        }));
        const search = classPanel.querySelector('[data-subject-search]');
        search?.addEventListener('input', () => {
            const termPanel = panels.find(panel => !panel.hidden);
            const query = search.value.trim().toLowerCase();
            let visible = 0;
            termPanel?.querySelectorAll('[data-subject-name]').forEach(subject => {
                subject.hidden = query !== '' && !subject.dataset.subjectName.includes(query);
                if (!subject.hidden) visible++;
            });
            const empty = termPanel?.querySelector('.repo-no-subjects');
            if (empty) empty.hidden = visible > 0;
        });
    });

    const resources = [...document.querySelectorAll('[data-resource-select]')];
    const subjectSelectors = [...document.querySelectorAll('[data-subject-select]')];
    const bulkBar = document.getElementById('bulkBar');
    const selectedCount = document.getElementById('selectedCount');
    const syncSelection = () => {
        const selected = resources.filter(input => input.checked);
        selectedCount.textContent = selected.length;
        bulkBar.hidden = selected.length === 0;
        subjectSelectors.forEach(selector => {
            const inputs = [...selector.closest('.repo-subject').querySelectorAll('[data-resource-select]')];
            selector.checked = inputs.length > 0 && inputs.every(input => input.checked);
            selector.indeterminate = inputs.some(input => input.checked) && !selector.checked;
        });
    };
    resources.forEach(input => input.addEventListener('change', syncSelection));
    subjectSelectors.forEach(selector => selector.addEventListener('change', () => {
        selector.closest('.repo-subject').querySelectorAll('[data-resource-select]').forEach(input => input.checked = selector.checked);
        syncSelection();
    }));
    document.getElementById('clearSelection')?.addEventListener('click', () => { resources.forEach(input => input.checked = false); syncSelection(); });
    document.getElementById('bulkResourceForm')?.addEventListener('submit', event => {
        const selected = resources.filter(input => input.checked);
        if (!selected.length) { event.preventDefault(); return; }
        if (event.submitter?.value === 'delete' && !confirm(`Remove ${selected.length} selected resources?`)) { event.preventDefault(); return; }
        const holder = document.getElementById('bulkResourceIds'); holder.replaceChildren();
        selected.forEach(input => { const hidden = document.createElement('input'); hidden.type='hidden'; hidden.name='source_ids[]'; hidden.value=input.value; holder.appendChild(hidden); });
    });
})();
</script>
@endpush
@endsection
