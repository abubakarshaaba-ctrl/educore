@extends('layouts.super')

@section('title', 'Curriculum Topic Mapping')

@section('content')
<div class="repo-shell">
    <nav class="repo-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('super.curriculum-sources.index') }}">Academic Repository</a>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        <span>Topic mapping</span>
    </nav>

    <header class="repo-page-head">
        <div>
            <h1>Topic mapping</h1>
            <p>Organise topics by subject and class.</p>
        </div>
    </header>

    @include('curriculum-sources._navigation')

    @if(session('success'))
        <div class="repo-notice repo-notice-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="repo-notice repo-notice-error">{{ $errors->first() }}</div>
    @endif

    <div class="repo-topics-grid">
        <section class="repo-card repo-topic-form-panel">
            <div class="repo-panel-head">
                <div><h2>Add a topic</h2><p>Create a new curriculum mapping.</p></div>
            </div>
            <form method="POST" action="{{ route('super.curriculum-sources.topics.store') }}" class="repo-topic-form">
                @csrf
                <div class="repo-field-row">
                    <div class="repo-field">
                        <label for="topicSubject">Subject</label>
                        <select id="topicSubject" name="subject_id" required>
                            <option value="" selected disabled>Select subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected((string) old('subject_id') === (string) $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="repo-field">
                        <label for="topicClass">Class</label>
                        <select id="topicClass" name="curriculum_level_id" required>
                            <option value="" selected disabled>Select class</option>
                            @foreach($classLevels as $classLevel)
                                <option value="{{ $classLevel->id }}" @selected((string) old('curriculum_level_id') === (string) $classLevel->id)>{{ $classLevel->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="repo-field">
                    <label for="topicName">Topic</label>
                    <input id="topicName" name="topic" value="{{ old('topic') }}" placeholder="Enter topic name" required>
                </div>

                <div class="repo-field">
                    <label for="topicSubtopics">Subtopics <span>Optional</span></label>
                    <textarea id="topicSubtopics" name="subtopics_text" rows="4" placeholder="Enter subtopics (comma separated)">{{ old('subtopics_text') }}</textarea>
                </div>

                <button class="repo-button repo-button-primary">
                    <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                    Add topic
                </button>
            </form>
        </section>

        <section class="repo-card repo-topic-list-panel">
            <div class="repo-topic-list-head">
                <div><h2>Mapped topics</h2><span>{{ number_format($topics->total()) }} total</span></div>
                <form method="GET" action="{{ route('super.curriculum-sources.topics.index') }}" class="repo-topic-search" role="search">
                    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                    <input name="search" value="{{ request('search') }}" placeholder="Search topics..." aria-label="Search topics">
                    @if(request()->filled('search'))<a href="{{ route('super.curriculum-sources.topics.index') }}">Clear</a>@endif
                </form>
            </div>

            @forelse($topics as $topic)
                <article class="repo-topic-row">
                    <span class="repo-topic-mark">{{ strtoupper(substr($subjectNames->get($topic->subject_id, 'NA'), 0, 2)) }}</span>
                    <div class="repo-topic-copy">
                        <div class="repo-topic-meta">
                            <span>{{ $subjectNames->get($topic->subject_id, 'Unmapped subject') }}</span><i></i>
                            <span>{{ $classLevelNames->get($topic->curriculum_level_id, 'Unmapped class') }}</span>
                        </div>
                        <h3>{{ $topic->topic }}</h3>
                        <p>{{ !empty($topic->subtopics) ? collect($topic->subtopics)->take(4)->implode(' · ') : 'No subtopics' }}</p>
                    </div>
                    <span class="repo-topic-status">{{ ucfirst($topic->status) }}</span>
                </article>
            @empty
                <div class="repo-empty repo-topic-empty">
                    <svg class="repo-topic-art" viewBox="0 0 200 150" aria-hidden="true">
                        <ellipse cx="100" cy="135" rx="62" ry="6" fill="#edf3fa"/>
                        <path d="M62 35h58l17 17v65H62z" fill="#edf4fd"/>
                        <path d="M120 35v18h18" fill="#dceafb"/>
                        <circle cx="78" cy="66" r="6" fill="#5599e2"/><rect x="90" y="62" width="33" height="7" rx="3.5" fill="#c8dcf2"/>
                        <circle cx="78" cy="84" r="6" fill="#5599e2"/><rect x="90" y="80" width="28" height="7" rx="3.5" fill="#c8dcf2"/>
                        <circle cx="78" cy="102" r="6" fill="#5599e2"/><rect x="90" y="98" width="34" height="7" rx="3.5" fill="#c8dcf2"/>
                        <circle cx="136" cy="105" r="20" fill="#fff" stroke="#5599e2" stroke-width="6"/><path d="m151 120 15 15" stroke="#5599e2" stroke-width="7" stroke-linecap="round"/>
                        <path d="M42 50v12M36 56h12M153 37v10M148 42h10" stroke="#7eb2e9" stroke-width="1.5" opacity=".75"/>
                    </svg>
                    <h2>{{ request()->filled('search') ? 'No topics found' : 'No topics mapped yet' }}</h2>
                    <p>{{ request()->filled('search') ? 'Try a different search.' : 'Add the first topic to get started.' }}</p>
                </div>
            @endforelse

            @if($topics->hasPages())<div class="repo-pagination">{{ $topics->links() }}</div>@endif
        </section>
    </div>
</div>

@include('curriculum-sources._styles')
@endsection
