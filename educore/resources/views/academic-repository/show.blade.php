@extends('layouts.app')

@section('title', $source->title.' — Academic Repository')

@section('content')
<div class="repo-shell repository-reader reader-detail">
    <nav class="repo-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('academic-repository.index') }}">Academic Repository</a>
        <svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
        <span>{{ $subjectLabel }}</span>
    </nav>

    <header class="reader-document-head">
        <div class="reader-document-title">
            <span class="reader-kicker"><i></i> Approved academic resource</span>
            <h1>{{ $source->title }}</h1>
            <div class="reader-document-tags">
                <span>{{ $classLabel }}</span><span>{{ $termLabel }}</span><span>{{ $subjectLabel }}</span>
            </div>
        </div>
        <div class="reader-document-actions">
            <a href="{{ route('academic-repository.index', ['selected_class' => $classLabel, 'selected_term' => $termLabel]) }}" class="repo-button repo-button-outline">
                <svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>Back to library
            </a>
            @if($source->source_file_path)
                <a href="{{ route('academic-repository.download', $source) }}" class="repo-button repo-button-primary">
                    <svg viewBox="0 0 24 24"><path d="M12 4v11m0 0 4-4m-4 4-4-4M5 19h14"/></svg>Download original
                </a>
            @endif
        </div>
    </header>

    <div class="reader-document-layout">
        <main class="reader-note" aria-label="Resource content">
            <div class="reader-note-cover">
                <span>{{ mb_strtoupper(mb_substr($subjectLabel, 0, 2)) }}</span>
                <div><small>{{ $classLabel }} · {{ $termLabel }}</small><h2>{{ $source->title }}</h2><p>{{ $subjectLabel }}</p></div>
            </div>

            @forelse($source->fragments as $fragment)
                <article class="reader-note-section" id="section-{{ $loop->iteration }}">
                    <header>
                        <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <div>
                            <small>Note section</small>
                            <h3>{{ $fragment->subtopic ?: $fragment->topic ?: 'Lesson content' }}</h3>
                        </div>
                    </header>
                    <div class="reader-note-content">{!! nl2br(e($fragment->content)) !!}</div>
                </article>
            @empty
                <div class="reader-note-empty">No readable sections are available for this resource.</div>
            @endforelse
        </main>

        <aside class="reader-document-aside">
            <section class="repo-card reader-info-card">
                <span class="reader-info-kicker">Resource details</span>
                <dl>
                    <div><dt>Class</dt><dd>{{ $classLabel }}</dd></div>
                    <div><dt>Term</dt><dd>{{ $termLabel }}</dd></div>
                    <div><dt>Subject</dt><dd>{{ $subjectLabel }}</dd></div>
                    <div><dt>Sections</dt><dd>{{ number_format($source->fragments->count()) }}</dd></div>
                    <div><dt>Version</dt><dd>{{ $source->version ?: 'Current' }}</dd></div>
                    <div><dt>File</dt><dd>{{ $source->original_filename }}</dd></div>
                    @if($source->file_size)<div><dt>Size</dt><dd>{{ number_format($source->file_size / 1048576, 1) }} MB</dd></div>@endif
                </dl>
                @if($source->source_file_path)
                    <a href="{{ route('academic-repository.download', $source) }}" class="repo-button repo-button-primary repo-button-full">
                        <svg viewBox="0 0 24 24"><path d="M12 4v11m0 0 4-4m-4 4-4-4M5 19h14"/></svg>Download original
                    </a>
                @endif
            </section>

            @if($source->fragments->count() > 1)
                <section class="repo-card reader-toc">
                    <span class="reader-info-kicker">On this page</span>
                    @foreach($source->fragments as $fragment)
                        <a href="#section-{{ $loop->iteration }}"><b>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</b><span>{{ str($fragment->subtopic ?: $fragment->topic ?: 'Lesson content')->limit(42) }}</span></a>
                    @endforeach
                </section>
            @endif
        </aside>
    </div>
</div>

@include('curriculum-sources._styles')
@include('academic-repository._styles')
@endsection
