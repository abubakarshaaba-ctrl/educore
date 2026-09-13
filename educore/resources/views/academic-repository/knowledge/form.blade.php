@extends('layouts.app')

@section('title', $topic->exists ? 'Edit Knowledge Topic' : 'Add Knowledge Topic')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div><div class="text-uppercase small fw-bold text-muted mb-1">Curriculum Knowledge Base</div><h1 class="h3 mb-1">{{ $topic->exists ? 'Edit topic' : 'Add topic' }}</h1><p class="text-muted mb-0">Structure approved curriculum content for deterministic lesson-plan and student-note generation.</p></div>
        <a class="btn btn-outline-secondary" href="{{ $topic->exists ? route('academic-repository.knowledge.show', $topic) : route('academic-repository.knowledge.index') }}">Back</a>
    </div>

    @if($errors->any())<div class="alert alert-danger"><strong>Please correct the highlighted fields.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ $topic->exists ? route('academic-repository.knowledge.update', $topic) : route('academic-repository.knowledge.store') }}">
        @csrf
        @if($topic->exists) @method('PUT') @endif

        <div class="card mb-4"><div class="card-header"><strong>Curriculum mapping</strong></div><div class="card-body row g-3">
            <div class="col-md-4"><label class="form-label">Class</label><input class="form-control" name="class_label" required value="{{ old('class_label', $topic->class_label) }}" placeholder="Year 12"></div>
            <div class="col-md-4"><label class="form-label">Subject</label><input class="form-control" name="subject_label" required value="{{ old('subject_label', $topic->subject_label) }}" placeholder="Biology"></div>
            <div class="col-md-4"><label class="form-label">Term</label><input class="form-control" name="term_label" required value="{{ old('term_label', $topic->term_label) }}" placeholder="First Term"></div>
            <div class="col-md-2"><label class="form-label">Week</label><input class="form-control" type="number" min="1" max="60" name="week_number" value="{{ old('week_number', $topic->week_number) }}"></div>
            <div class="col-md-2"><label class="form-label">Lesson</label><input class="form-control" type="number" min="1" max="20" name="lesson_number" value="{{ old('lesson_number', $topic->lesson_number) }}"></div>
            <div class="col-md-4"><label class="form-label">Resource type</label><select class="form-select" name="resource_type">@foreach(['curriculum','scheme_of_work','syllabus','lesson_note','teacher_note','textbook_extract','practical_guide','past_questions','lesson_plan','other'] as $type)<option value="{{ $type }}" @selected(old('resource_type', $topic->resource_type ?: 'lesson_note')===$type)>{{ str($type)->replace('_',' ')->title() }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Linked repository source</label><select class="form-select" name="curriculum_source_id"><option value="">None</option>@foreach($sources as $source)<option value="{{ $source->id }}" @selected((string)old('curriculum_source_id', $topic->curriculum_source_id)===(string)$source->id)>{{ $source->title ?: $source->original_filename }}</option>@endforeach</select></div>
            <div class="col-md-8"><label class="form-label">Topic</label><input class="form-control" name="topic" required value="{{ old('topic', $topic->topic) }}"></div>
            <div class="col-md-8"><label class="form-label">Sub-topic</label><input class="form-control" name="sub_topic" value="{{ old('sub_topic', $topic->sub_topic) }}"></div>
            <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status">@foreach(['draft','review','approved'] as $status)<option value="{{ $status }}" @selected(old('status', $topic->status ?: 'draft')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        </div></div>

        <div class="card mb-4"><div class="card-header"><strong>Standard lesson-plan fields</strong></div><div class="card-body row g-3">
            @foreach([
                ['entry_behaviour','Entry Behaviour'],['previous_knowledge','Previous / Background Knowledge'],['instructional_resources','Instructional Resources'],['introduction','Introduction'],['reference','Reference'],['student_note_summary','Student Note Summary']
            ] as [$name,$label])
                <div class="col-md-6"><label class="form-label">{{ $label }}</label><textarea class="form-control" rows="4" name="{{ $name }}">{{ old($name, $topic->{$name}) }}</textarea></div>
            @endforeach
        </div></div>

        <div class="card mb-4"><div class="card-header"><strong>Instructional content blocks</strong><div class="small text-muted mt-1">Separate individual items with a blank line. For presentation steps, use <code>Step title :: content</code>.</div></div><div class="card-body row g-3">
            @foreach([
                'objective'=>'Behavioural Objectives','presentation'=>'Presentation Steps','definition'=>'Definitions','explanation'=>'Explanations / Core Content','example'=>'Examples','practical'=>'Practical Activities','note'=>'Additional Student-note Content','evaluation'=>'Evaluation Questions','assignment'=>'Assignment'
            ] as $type=>$label)
                <div class="col-lg-6"><label class="form-label">{{ $label }}</label><textarea class="form-control" rows="7" name="blocks[{{ $type }}]" placeholder="One item per paragraph">{{ old('blocks.'.$type, $blockText[$type] ?? '') }}</textarea></div>
            @endforeach
        </div></div>

        <div class="d-flex justify-content-end gap-2"><a href="{{ route('academic-repository.knowledge.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary">{{ $topic->exists ? 'Save changes' : 'Create knowledge record' }}</button></div>
    </form>
</div>
@endsection
