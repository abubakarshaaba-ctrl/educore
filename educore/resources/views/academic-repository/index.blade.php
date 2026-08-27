@extends('layouts.app')

@section('title', 'Academic Repository')

@section('content')
<div class="repo-shell repository-reader" data-repository-browser>
    <nav class="repo-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
        <span>Academic Repository</span>
    </nav>

    <header class="reader-hero">
        <div class="reader-hero-copy">
            <span class="reader-kicker"><i></i> Platform-approved resources</span>
            <h1>Academic Repository</h1>
            <p>Read and download organised lesson materials for every available class, term and subject.</p>
        </div>
        <div class="reader-hero-mark" aria-hidden="true">
            <svg viewBox="0 0 64 64"><path d="M12 10h31a7 7 0 0 1 7 7v37H19a7 7 0 0 1-7-7V10Z"/><path d="M19 10v44m9-32h14M28 31h14M28 40h9"/></svg>
        </div>
        <div class="reader-metrics" aria-label="Repository summary">
            @foreach([
                ['resources', 'Resources', 'M6 4h12v16H6zM9 8h6M9 12h6'],
                ['classes', 'Classes', 'M3 7h7l2 2h9v10H3z'],
                ['subjects', 'Subjects', 'M4 5h16v14H4zM8 9h8M8 13h6'],
                ['sections', 'Note sections', 'M5 4h14v16H5zM9 8h6M9 12h6M9 16h4'],
            ] as [$key, $label, $path])
                <div class="reader-metric">
                    <span><svg viewBox="0 0 24 24"><path d="{{ $path }}"/></svg></span>
                    <div><strong>{{ number_format($metrics[$key]) }}</strong><small>{{ $label }}</small></div>
                </div>
            @endforeach
        </div>
    </header>

    <form method="GET" action="{{ route('academic-repository.index') }}" class="repo-card reader-toolbar">
        <input type="hidden" name="selected_class" value="{{ request('selected_class') }}" data-selection-class-field>
        <input type="hidden" name="selected_term" value="{{ request('selected_term') }}" data-selection-term-field>
        <label class="repo-search">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
            <input name="search" value="{{ request('search') }}" placeholder="Search resources, topics or filenames" aria-label="Search academic resources">
        </label>
        <div class="repo-field reader-subject-filter">
            <label class="sr-only" for="readerSubject">Filter by subject</label>
            <select id="readerSubject" name="subject">
                <option value="">All subjects</option>
                @foreach($subjectNames as $subject)
                    <option value="{{ $subject }}" @selected(request('subject') === $subject)>{{ $subject }}</option>
                @endforeach
            </select>
        </div>
        <button class="repo-button repo-button-primary">Search</button>
        @if(request()->hasAny(['search', 'subject']))
            <a href="{{ route('academic-repository.index') }}" class="repo-button repo-button-outline">Clear</a>
        @endif
    </form>

    @if($groups->isNotEmpty())
        <section class="repo-class-section" aria-labelledby="readerClassesHeading">
            <div class="repo-section-heading">
                <div><h2 id="readerClassesHeading">Browse by class</h2><p>Your selected class and term remain active when this page is reloaded.</p></div>
                <span>{{ $groups->count() }} {{ str('class')->plural($groups->count()) }}</span>
            </div>
            <div class="repo-class-grid" role="tablist" aria-label="Available classes">
                @foreach($groups as $classLabel => $terms)
                    @php
                        $classResources = $terms->flatten(2);
                        $classId = 'reader-class-'.$loop->index;
                        $subjectCount = $terms->flatMap(fn ($subjects) => $subjects->keys())->unique()->count();
                    @endphp
                    <button type="button" class="repo-class-stat @if($loop->first) active @endif" data-class-target="{{ $classId }}" data-class-key="{{ $classLabel }}" role="tab" aria-controls="{{ $classId }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        <span class="repo-class-icon"><svg viewBox="0 0 24 24"><path d="M3 7h7l2 2h9v10H3z"/></svg></span>
                        <span class="repo-class-copy"><strong>{{ $classLabel }}</strong><small>{{ number_format($classResources->count()) }} resources · {{ $terms->count() }} {{ str('term')->plural($terms->count()) }}</small></span>
                        <span class="reader-class-foot"><b>{{ $subjectCount }}</b> {{ str('subject')->plural($subjectCount) }}<svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></span>
                    </button>
                @endforeach
            </div>
        </section>

        <section class="repo-hierarchy reader-hierarchy">
            @foreach($groups as $classLabel => $terms)
                @php $classId = 'reader-class-'.$loop->index; @endphp
                <div class="repo-class-panel" id="{{ $classId }}" data-class-key="{{ $classLabel }}" data-class-panel @if(!$loop->first) hidden @endif>
                    <div class="repo-hierarchy-head">
                        <div><span>Selected class</span><h2>{{ $classLabel }}</h2></div>
                        <label class="repo-local-search">
                            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                            <input type="search" placeholder="Filter subjects in this class" data-subject-search aria-label="Filter subjects in {{ $classLabel }}">
                        </label>
                    </div>

                    <div class="repo-term-tabs" role="tablist" aria-label="Terms in {{ $classLabel }}">
                        @foreach($terms as $termLabel => $subjects)
                            @php $termId = $classId.'-term-'.$loop->index; @endphp
                            <button type="button" class="repo-term-tab @if($loop->first) active @endif" data-term-target="{{ $termId }}" data-term-key="{{ $termLabel }}" aria-controls="{{ $termId }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                <span>{{ $termLabel }}</span><b>{{ $subjects->flatten(1)->count() }}</b>
                            </button>
                        @endforeach
                    </div>

                    @foreach($terms as $termLabel => $subjects)
                        @php $termId = $classId.'-term-'.$loop->index; @endphp
                        <div class="repo-term-panel" id="{{ $termId }}" data-term-panel @if(!$loop->first) hidden @endif>
                            <div class="repo-term-summary">
                                <div><span>Selected term</span><h3>{{ $termLabel }}</h3></div>
                                <p>{{ $subjects->count() }} {{ str('subject')->plural($subjects->count()) }} · {{ $subjects->flatten(1)->count() }} resources</p>
                            </div>

                            <div class="repo-subject-list">
                                @foreach($subjects as $subjectLabel => $subjectSources)
                                    <details class="repo-subject reader-subject" data-subject-name="{{ mb_strtolower($subjectLabel) }}" @if($subjects->count() === 1) open @endif>
                                        <summary>
                                            <span class="repo-subject-mark">{{ mb_strtoupper(mb_substr($subjectLabel, 0, 2)) }}</span>
                                            <span class="repo-subject-copy"><strong>{{ $subjectLabel }}</strong><small>{{ $subjectSources->count() }} ready-to-use {{ str('resource')->plural($subjectSources->count()) }}</small></span>
                                            <span class="reader-ready"><i></i> Available</span>
                                            <svg class="repo-chevron" viewBox="0 0 24 24"><path d="m8 10 4 4 4-4"/></svg>
                                        </summary>
                                        <div class="repo-subject-resources">
                                            @foreach($subjectSources as $source)
                                                @php
                                                    $extension = strtoupper(pathinfo($source->original_filename ?? '', PATHINFO_EXTENSION) ?: 'FILE');
                                                    $fileSize = $source->file_size ? number_format($source->file_size / 1048576, 1).' MB' : 'Original file';
                                                @endphp
                                                <article class="repo-resource reader-resource">
                                                    <span class="repo-file-mark">{{ substr($extension, 0, 4) }}</span>
                                                    <div class="repo-resource-copy">
                                                        <h3>{{ $source->title }}</h3>
                                                        <p>{{ $source->original_filename }} · {{ number_format($source->fragments_count) }} {{ str('section')->plural($source->fragments_count) }} · {{ $fileSize }}</p>
                                                    </div>
                                                    <div class="repo-resource-actions">
                                                        <a href="{{ route('academic-repository.show', $source) }}" class="repo-button repo-button-soft">
                                                            <svg viewBox="0 0 24 24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>Read
                                                        </a>
                                                        @if($source->source_file_path)
                                                            <a href="{{ route('academic-repository.download', $source) }}" class="repo-button repo-button-outline">
                                                                <svg viewBox="0 0 24 24"><path d="M12 4v11m0 0 4-4m-4 4-4-4M5 19h14"/></svg>Download
                                                            </a>
                                                        @else
                                                            <button type="button" class="repo-button repo-button-outline" disabled>File unavailable</button>
                                                        @endif
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
        <section class="repo-card reader-empty">
            <span><svg viewBox="0 0 24 24"><path d="M3 7h7l2 2h9v10H3z"/><circle cx="15" cy="15" r="4"/><path d="m18 18 3 3"/></svg></span>
            <h2>No matching resources</h2>
            <p>Try a different keyword or subject filter.</p>
            <a href="{{ route('academic-repository.index') }}" class="repo-button repo-button-primary">View all resources</a>
        </section>
    @endif
</div>

@include('curriculum-sources._styles')
@include('academic-repository._styles')

@push('scripts')
<script>
@include('curriculum-sources._browser_script', ['repositorySelectionKey' => 'educore_school_repository_selection'])
</script>
@endpush
@endsection
