@extends('layouts.super')

@section('title', 'Curriculum Topic Mapping')

@section('content')
<div class="topics-page">
    <header class="topics-header">
        <div>
            <span class="eyebrow">CURRICULUM STRUCTURE</span>
            <h1>Topic mapping</h1>
            <p>Organise topics by subject and class.</p>
        </div>
    </header>

    @include('curriculum-sources._navigation')

    @if(session('success'))
        <div class="notice notice-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="notice notice-error">{{ $errors->first() }}</div>
    @endif

    <div class="topics-layout">
        <section class="topics-panel topic-form-panel">
            <div class="panel-heading">
                <span class="panel-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                </span>
                <div><h2>Add topic</h2><span>New curriculum mapping</span></div>
            </div>

            <form method="POST" action="{{ route('super.curriculum-sources.topics.store') }}" class="topic-form">
                @csrf
                <div class="field-row">
                    <div class="field">
                        <label for="topicSubject">Subject</label>
                        <select id="topicSubject" name="subject_id" required>
                            <option value="" selected disabled>Select subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected((string) old('subject_id') === (string) $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="topicClass">Class</label>
                        <select id="topicClass" name="curriculum_level_id" required>
                            <option value="" selected disabled>Select class</option>
                            @foreach($classLevels as $classLevel)
                                <option value="{{ $classLevel->id }}" @selected((string) old('curriculum_level_id') === (string) $classLevel->id)>{{ $classLevel->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label for="topicName">Topic</label>
                    <input id="topicName" name="topic" value="{{ old('topic') }}" placeholder="Topic name" required>
                </div>

                <div class="field">
                    <label for="topicSubtopics">Subtopics <span>Optional</span></label>
                    <textarea id="topicSubtopics" name="subtopics_text" rows="4" placeholder="Separate with commas">{{ old('subtopics_text') }}</textarea>
                </div>

                <div class="field">
                    <label for="topicKeywords">Keywords <span>Optional</span></label>
                    <textarea id="topicKeywords" name="keywords_text" rows="4" placeholder="Separate with commas">{{ old('keywords_text') }}</textarea>
                </div>

                <button class="button button-primary button-full">
                    Save topic
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </form>
        </section>

        <section class="topics-panel topic-list-panel">
            <div class="list-heading">
                <div><h2>Mapped topics</h2><span>{{ number_format($topics->total()) }} total</span></div>
                <form method="GET" action="{{ route('super.curriculum-sources.topics.index') }}" class="topic-search" role="search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                    <input name="search" value="{{ request('search') }}" placeholder="Search topics" aria-label="Search topics">
                    @if(request()->filled('search'))<a href="{{ route('super.curriculum-sources.topics.index') }}">Clear</a>@endif
                </form>
            </div>

            <div class="topic-list">
                @forelse($topics as $topic)
                    <article class="topic-row">
                        <span class="topic-mark">{{ strtoupper(substr($subjectNames->get($topic->subject_id, 'NA'), 0, 2)) }}</span>
                        <div class="topic-copy">
                            <div class="topic-meta">
                                <span>{{ $subjectNames->get($topic->subject_id, 'Unmapped subject') }}</span>
                                <i></i>
                                <span>{{ $classLevelNames->get($topic->curriculum_level_id, 'Unmapped class') }}</span>
                            </div>
                            <h3>{{ $topic->topic }}</h3>
                            @if(!empty($topic->subtopics))
                                <p>{{ collect($topic->subtopics)->take(4)->implode(' · ') }}</p>
                            @else
                                <p>No subtopics</p>
                            @endif
                        </div>
                        <span class="topic-status">{{ ucfirst($topic->status) }}</span>
                    </article>
                @empty
                    <div class="empty-state">
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h10M4 17h7"/><circle cx="18" cy="15" r="3"/></svg></span>
                        <h3>{{ request()->filled('search') ? 'No matches found' : 'No topics mapped' }}</h3>
                        <p>{{ request()->filled('search') ? 'Try a different search.' : 'Add the first topic.' }}</p>
                    </div>
                @endforelse
            </div>

            @if($topics->hasPages())
                <div class="topics-pagination">{{ $topics->links() }}</div>
            @endif
        </section>
    </div>
</div>

<style>
:root{--topic-blue:#1756a9;--topic-navy:#09244a;--topic-ink:#12233d;--topic-muted:#6b7a90;--topic-line:#dfe7f0;--topic-soft:#f4f7fb}
.topics-page{max-width:1320px;margin:0 auto;padding:28px 30px 50px;color:var(--topic-ink)}
.topics-header{margin-bottom:22px}.eyebrow{display:block;margin-bottom:7px;color:var(--topic-blue);font-size:10px;font-weight:850;letter-spacing:.14em}.topics-header h1{margin:0;color:#071b38;font-size:30px;line-height:1.15;letter-spacing:-.035em}.topics-header p{margin:7px 0 0;color:var(--topic-muted);font-size:13px}
.notice{margin:0 0 18px;padding:12px 15px;border:1px solid;border-radius:10px;font-size:13px;font-weight:650}.notice-success{color:#17633b;background:#effbf4;border-color:#ccebd9}.notice-error{color:#9c2f2f;background:#fff5f5;border-color:#f1cccc}
.topics-layout{display:grid;grid-template-columns:390px minmax(0,1fr);align-items:start;gap:18px}.topics-panel{overflow:hidden;background:#fff;border:1px solid var(--topic-line);border-radius:15px;box-shadow:0 5px 20px rgba(15,39,72,.035)}.topic-form-panel{position:sticky;top:18px}.panel-heading,.list-heading{min-height:70px;padding:15px 18px;display:flex;align-items:center;border-bottom:1px solid var(--topic-line)}.panel-heading{gap:12px}.panel-icon{width:35px;height:35px;display:grid;place-items:center;border-radius:10px;color:#2c68ab;background:#eaf2fb}.panel-icon svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round}.panel-heading h2,.list-heading h2{margin:0;color:#102744;font-size:16px}.panel-heading div span,.list-heading div span{display:block;margin-top:4px;color:var(--topic-muted);font-size:10px}
.topic-form{padding:18px}.field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.field label{display:block;margin-bottom:6px;color:#354a65;font-size:11px;font-weight:750}.field label span{color:#8d99a9;font-size:9px;font-weight:600}.field input,.field select,.field textarea{width:100%;margin:0 0 15px;padding:10px 11px;border:1px solid #d4deea;border-radius:9px;outline:0;color:#1c304d;background:#fff;font:inherit;font-size:12px;transition:border .15s ease,box-shadow .15s ease}.field input,.field select{height:42px}.field textarea{resize:vertical}.field input:focus,.field select:focus,.field textarea:focus{border-color:#4f86c4;box-shadow:0 0 0 3px rgba(53,112,180,.1)}
.button{min-height:42px;padding:0 17px;border:0;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:13px;font-weight:750;text-decoration:none;cursor:pointer;transition:transform .15s ease,background .15s ease}.button:hover{transform:translateY(-1px)}.button svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.button-primary{color:#fff;background:var(--topic-blue);box-shadow:0 8px 20px rgba(23,86,169,.2)}.button-primary:hover{background:#10498f}.button-full{width:100%}
.list-heading{justify-content:space-between;gap:16px}.topic-search{width:min(330px,55%);height:39px;padding:0 11px;display:flex;align-items:center;gap:8px;border:1px solid #dbe4ef;border-radius:9px;background:var(--topic-soft)}.topic-search svg{width:16px;flex:0 0 16px;fill:none;stroke:#73839a;stroke-width:2;stroke-linecap:round}.topic-search input{min-width:0;flex:1;border:0;outline:0;color:var(--topic-ink);background:transparent;font:inherit;font-size:11px}.topic-search a{color:var(--topic-blue);font-size:10px;font-weight:750;text-decoration:none}
.topic-row{min-height:92px;padding:14px 18px;display:grid;grid-template-columns:42px minmax(0,1fr) auto;align-items:center;gap:13px;border-bottom:1px solid #edf1f6;transition:background .15s ease}.topic-row:last-child{border-bottom:0}.topic-row:hover{background:#fbfcfe}.topic-mark{width:42px;height:42px;display:grid;place-items:center;border-radius:11px;color:#2b66a8;background:#eaf2fb;font-size:10px;font-weight:850}.topic-copy{min-width:0}.topic-meta{display:flex;align-items:center;gap:7px;color:#718096;font-size:9px;font-weight:800;letter-spacing:.05em;text-transform:uppercase}.topic-meta i{width:3px;height:3px;border-radius:50%;background:#b6c1ce}.topic-copy h3{margin:5px 0;color:#17304f;font-size:13px}.topic-copy p{margin:0;overflow:hidden;color:#78869a;font-size:10px;text-overflow:ellipsis;white-space:nowrap}.topic-status{padding:5px 8px;border-radius:999px;color:#157044;background:#e8f8ef;font-size:9px;font-weight:750}.empty-state{padding:65px 20px;text-align:center}.empty-state>span{width:50px;height:50px;margin:0 auto 13px;display:grid;place-items:center;border-radius:14px;color:#2d68aa;background:#eaf2fb}.empty-state svg{width:24px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round}.empty-state h3{margin:0;color:#17304f;font-size:14px}.empty-state p{margin:6px 0 0;color:var(--topic-muted);font-size:11px}.topics-pagination{padding:14px 18px;border-top:1px solid var(--topic-line)}
@media(max-width:1030px){.topics-layout{grid-template-columns:1fr}.topic-form-panel{position:static}.topic-form{display:grid;grid-template-columns:1fr 1fr;gap:0 14px}.topic-form>.field:nth-of-type(2),.topic-form>.field:nth-of-type(3){grid-column:auto}.topic-form>.button{grid-column:1/-1}.field-row{grid-column:1/-1}}
@media(max-width:700px){.topics-page{padding:20px 14px 40px}.topics-header h1{font-size:25px}.topics-header p{display:none}.list-heading{align-items:stretch;flex-direction:column}.topic-search{width:100%}.topic-form{display:block}.topic-row{grid-template-columns:38px minmax(0,1fr)}.topic-mark{width:38px;height:38px}.topic-status{grid-column:2;justify-self:start}.field-row{grid-template-columns:1fr}}
@media(max-width:430px){.topic-row{padding:13px}.topic-meta{flex-wrap:wrap}.topic-copy p{max-width:220px}}
</style>
@endsection
