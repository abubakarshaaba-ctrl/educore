@extends('layouts.app')
@section('title', $type === 'lesson-plan' ? 'Generated Lesson Plan' : 'Generated Student Note')
@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 no-print">
        <div><div class="small text-uppercase fw-bold text-muted">Curriculum Knowledge Base</div><h1 class="h3 mb-1">{{ $type === 'lesson-plan' ? 'Standard Lesson Plan' : 'Student Note' }}</h1><p class="text-muted mb-0">Generated deterministically from approved repository content. AI is not required.</p></div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('academic-repository.knowledge.show', $academicTopic) }}" class="btn btn-outline-secondary">Back</a>
            @if($type === 'lesson-plan')
                <form method="POST" action="{{ route('academic-repository.knowledge.lesson-planner.store', $academicTopic) }}">
                    @csrf
                    <button class="btn btn-success">Save to Lesson Planner</button>
                </form>
            @else
                <form method="POST" action="{{ route('academic-repository.knowledge.student-note.store', $academicTopic) }}">
                    @csrf
                    <button class="btn btn-success">Save Student Note</button>
                </form>
            @endif
            <button class="btn btn-primary" onclick="window.print()">Print / Save PDF</button>
        </div>
    </div>

    <article class="card generated-document"><div class="card-body p-4 p-lg-5">
        @if($type === 'lesson-plan')
            <div class="row g-2 mb-3 border-bottom pb-3">
                <div class="col-md-3"><strong>CLASS:</strong> {{ $document['class'] }}</div><div class="col-md-3"><strong>SUBJECT:</strong> {{ $document['subject'] }}</div><div class="col-md-3"><strong>WEEK:</strong> {{ $document['week'] ?: '—' }}</div><div class="col-md-3"><strong>LESSON:</strong> {{ $document['lesson'] ?: '—' }}</div>
            </div>
            <section class="mb-4"><h2 class="h5">TOPIC</h2><p>{{ $document['topic'] }}</p></section>
            <section class="mb-4"><h2 class="h5">SUB-TOPIC</h2><p>{{ $document['sub_topic'] ?: '—' }}</p></section>
            <div class="row g-2 mb-4 border-bottom pb-3">
                <div class="col-md-3"><strong>TIME:</strong> {{ $document['time'] ?: '—' }}</div>
                <div class="col-md-3"><strong>DURATION:</strong> {{ $document['duration'] ? $document['duration'].' minutes' : '—' }}</div>
                <div class="col-md-3"><strong>AVERAGE AGE:</strong> {{ $document['average_age'] ?: '—' }}</div>
                <div class="col-md-3"><strong>SEX:</strong> {{ $document['sex'] ?: '—' }}</div>
            </div>
            <section class="mb-4"><h2 class="h5">ENTRY BEHAVIOUR</h2><p>{{ $document['entry_behaviour'] }}</p></section>
            <section class="mb-4"><h2 class="h5">PREVIOUS / BACKGROUND KNOWLEDGE</h2><p>{{ $document['previous_knowledge'] }}</p></section>
            <section class="mb-4"><h2 class="h5">BEHAVIOURAL OBJECTIVES</h2><p>At the end of the lesson, students should be able to:</p><ol>@foreach($document['behavioural_objectives'] as $item)<li class="mb-1">{{ $item }}</li>@endforeach</ol></section>
            <section class="mb-4"><h2 class="h5">INSTRUCTIONAL RESOURCES</h2><p>{{ $document['instructional_resources'] }}</p></section>
            <section class="mb-4"><h2 class="h5">INTRODUCTION</h2><p style="white-space:pre-wrap">{{ $document['introduction'] }}</p></section>
            <section class="mb-4"><h2 class="h5">PRESENTATION</h2>@foreach($document['presentation'] as $step)<div class="mb-3"><h3 class="h6 text-uppercase">{{ $step['title'] }}</h3><p style="white-space:pre-wrap">{{ $step['content'] }}</p></div>@endforeach</section>
            <section class="mb-4"><h2 class="h5">EVALUATION</h2><ol>@foreach($document['evaluation'] as $item)<li class="mb-1">{{ $item }}</li>@endforeach</ol></section>
            <section class="mb-4"><h2 class="h5">ASSIGNMENT</h2><ol>@foreach($document['assignment'] as $item)<li class="mb-1">{{ $item }}</li>@endforeach</ol></section>
            <section><h2 class="h5">REFERENCE</h2><p>{{ $document['reference'] }}</p></section>
        @else
            <div class="border-bottom pb-3 mb-4"><div class="small text-muted">{{ $document['class'] }} · {{ $document['subject'] }} · {{ $document['term'] }} · Week {{ $document['week'] ?: '—' }}</div><h1 class="h2 mt-2">{{ $document['topic'] }}</h1>@if($document['sub_topic'])<p class="lead mb-0">{{ $document['sub_topic'] }}</p>@endif</div>
            @if($document['objectives'])<section class="mb-4"><h2 class="h5">Learning Objectives</h2><ul>@foreach($document['objectives'] as $item)<li>{{ $item }}</li>@endforeach</ul></section>@endif
            @if($document['summary'])<section class="mb-4"><h2 class="h5">Overview</h2><p style="white-space:pre-wrap">{{ $document['summary'] }}</p></section>@endif
            @foreach($document['content'] as $section)<section class="mb-4"><h2 class="h5">{{ $section['heading'] }}</h2><div style="white-space:pre-wrap">{{ $section['content'] }}</div></section>@endforeach
            @if($document['review_questions'])<section class="mb-4"><h2 class="h5">Review Questions</h2><ol>@foreach($document['review_questions'] as $item)<li>{{ $item }}</li>@endforeach</ol></section>@endif
            @if($document['assignment'])<section class="mb-4"><h2 class="h5">Assignment</h2><ol>@foreach($document['assignment'] as $item)<li>{{ $item }}</li>@endforeach</ol></section>@endif
            <section><h2 class="h5">Reference</h2><p>{{ $document['reference'] }}</p></section>
        @endif
    </div></article>
</div>
<style>@media print{.no-print,.sidebar,.topbar{display:none!important}.generated-document{border:0!important;box-shadow:none!important}.container{max-width:none!important;padding:0!important}body{background:#fff!important}}</style>
@endsection
