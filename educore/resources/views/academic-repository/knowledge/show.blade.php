@extends('layouts.app')
@section('title', $topic->topic)
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="small fw-bold text-muted text-uppercase">{{ $topic->class_label }} · {{ $topic->subject_label }} · {{ $topic->term_label }}</div>
            <h1 class="h3 mb-1">{{ $topic->topic }}</h1>
            @if($topic->sub_topic)<p class="text-muted">{{ $topic->sub_topic }}</p>@endif
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('academic-repository.knowledge.index') }}" class="btn btn-outline-secondary">Knowledge base</a>
            @if(auth()->user()?->isAdmin())<a href="{{ route('academic-repository.knowledge.edit', $topic) }}" class="btn btn-outline-primary">Edit</a>@endif
        </div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card mb-4"><div class="card-header"><strong>Generation readiness</strong></div><div class="card-body">
                <div class="display-5 fw-bold">{{ $readiness['score'] }}%</div>
                <div class="small text-muted">Coverage {{ $readiness['coverage_score'] ?? $readiness['score'] }}% · Quality {{ $readiness['quality_score'] ?? '—' }}%</div>
                <div class="progress my-3" style="height:10px"><div class="progress-bar" style="width:{{ $readiness['score'] }}%"></div></div>
                @foreach($readiness['checks'] as $label=>$ok)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $label }}</span><strong class="{{ $ok ? 'text-success' : 'text-danger' }}">{{ $ok ? 'Ready' : 'Missing' }}</strong></div>@endforeach
            </div></div>

            <div class="card mb-4"><div class="card-header"><strong>Deterministic quality checks</strong></div><div class="card-body">
                @foreach(($readiness['quality']['checks'] ?? []) as $label=>$ok)
                    <div class="d-flex justify-content-between gap-2 border-bottom py-2"><span>{{ $label }}</span><strong class="{{ $ok ? 'text-success' : 'text-warning' }}">{{ $ok ? 'Pass' : 'Check' }}</strong></div>
                @endforeach
                @if(!empty($readiness['quality']['critical'] ?? []))
                    <div class="alert alert-warning mt-3 mb-0"><strong>Critical quality items</strong><ul class="mb-0 mt-2">@foreach($readiness['quality']['critical'] as $issue)<li>{{ $issue }}</li>@endforeach</ul></div>
                @endif
            </div></div>

            <div class="card mb-4"><div class="card-header"><strong>Mapping</strong></div><div class="card-body small">
                <div><strong>Week:</strong> {{ $topic->week_number ?: '—' }}</div><div><strong>Lesson:</strong> {{ $topic->lesson_number ?: '—' }}</div><div><strong>Type:</strong> {{ str($topic->resource_type)->replace('_',' ')->title() }}</div><div><strong>Status:</strong> {{ ucfirst($topic->status) }}</div><div><strong>Source:</strong> {{ $topic->source?->title ?: 'Not linked' }}</div>
            </div></div>

            @if(!empty($readiness['sources']))
                <div class="card mb-4"><div class="card-header"><strong>Ranked repository sources</strong></div><div class="card-body small">
                    @foreach($readiness['sources'] as $source)
                        <div class="border-bottom py-2">
                            <div class="fw-semibold">{{ $source['title'] ?: ($source['filename'] ?: 'Repository source') }}</div>
                            <div class="text-muted">{{ str_replace('_', ' ', $source['resource_type'] ?? 'other') }} · priority {{ $source['priority'] ?? '—' }}@if(!empty($source['is_official'])) · official@endif</div>
                        </div>
                    @endforeach
                </div></div>
            @endif

            @if(auth()->user()?->isAdmin() && $topic->status !== 'approved')<form method="POST" action="{{ route('academic-repository.knowledge.approve', $topic) }}">@csrf<button class="btn btn-success w-100">Approve topic</button></form>@endif
        </div>
        <div class="col-xl-8">
            @if($readiness['ready'])<div class="card mb-4"><div class="card-header"><strong>Generate content</strong></div><div class="card-body d-flex gap-2"><a class="btn btn-primary" href="{{ route('academic-repository.knowledge.generate', [$topic, 'lesson-plan']) }}">Lesson plan</a><a class="btn btn-outline-primary" href="{{ route('academic-repository.knowledge.generate', [$topic, 'student-note']) }}">Student note</a></div></div>@else<div class="alert alert-warning">Complete the missing or quality-critical content before generation.</div>@endif
            <div class="card mb-4"><div class="card-header"><strong>Standard fields</strong></div><div class="card-body">
                @foreach(['Entry Behaviour'=>$topic->entry_behaviour,'Previous / Background Knowledge'=>$topic->previous_knowledge,'Instructional Resources'=>$topic->instructional_resources,'Introduction'=>$topic->introduction,'Student Note Summary'=>$topic->student_note_summary,'Reference'=>$topic->reference] as $label=>$value)<section class="mb-4"><h2 class="h6">{{ $label }}</h2><div class="text-muted" style="white-space:pre-wrap">{{ $value ?: 'Not supplied.' }}</div></section>@endforeach
            </div></div>
            @php $grouped = $topic->blocks->groupBy('block_type'); @endphp
            @foreach(['objective'=>'Behavioural Objectives','presentation'=>'Presentation Steps','definition'=>'Definitions','explanation'=>'Core Explanations','example'=>'Examples','practical'=>'Practical Activities','note'=>'Additional Notes','evaluation'=>'Evaluation','assignment'=>'Assignment'] as $type=>$label)
                @if(($grouped[$type] ?? collect())->isNotEmpty())<div class="card mb-4"><div class="card-header"><strong>{{ $label }}</strong></div><div class="card-body">@foreach($grouped[$type] as $block)<div class="mb-3 @if(!$loop->last) border-bottom pb-3 @endif">@if($block->title)<h3 class="h6">{{ $block->title }}</h3>@endif<div style="white-space:pre-wrap">{{ $block->content }}</div></div>@endforeach</div></div>@endif
            @endforeach
        </div>
    </div>
</div>
@endsection
