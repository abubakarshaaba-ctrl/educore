@extends('layouts.app')

@section('title', 'Curriculum Knowledge Base')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="text-uppercase small fw-bold text-muted mb-1">Academic Repository</div>
            <h1 class="h3 mb-1">Curriculum Knowledge Base</h1>
            <p class="text-muted mb-0">Structured curriculum content for deterministic lesson plans and student notes.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('academic-repository.index') }}" class="btn btn-outline-secondary">Repository</a>
            @if(auth()->user()?->isAdmin())
                <a href="{{ route('academic-repository.knowledge.ingestion.index') }}" class="btn btn-outline-primary">Import Repository</a>
                <a href="{{ route('academic-repository.knowledge.create') }}" class="btn btn-primary">Add topic</a>
            @endif
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @php
        $total = $topics->count();
        $ready = $readiness->filter(fn($r) => $r['ready'])->count();
        $avgCoverage = $total ? round($readiness->avg('coverage_score')) : 0;
        $avgQuality = $total ? round($readiness->avg('quality_score')) : 0;
        $multiSource = $readiness->filter(fn($r) => ($r['consolidation']['source_count'] ?? 0) > 1)->count();
        $nearDuplicateRecords = $readiness->sum(fn($r) => count($r['consolidation']['near_duplicates'] ?? []));
    @endphp

    <div class="row g-3 mb-4">
        @foreach([
            ['Topics', $total],
            ['Generation-ready', $ready],
            ['Average coverage', $avgCoverage.'%'],
            ['Average quality', $avgQuality.'%'],
            ['Multi-source topics', $multiSource],
            ['Related variants', $nearDuplicateRecords],
        ] as [$label, $value])
            <div class="col-6 col-lg-4 col-xl-2">
                <div class="card h-100"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="fs-3 fw-bold">{{ $value }}</div></div></div>
            </div>
        @endforeach
    </div>

    @if(auth()->user()?->isAdmin())
        <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div><strong>Repository consolidation is active.</strong><div class="small text-muted">Matching topic variants from higher-priority curriculum, scheme, syllabus and note sources can complement one another during deterministic generation.</div></div>
            <a href="{{ route('academic-repository.knowledge.ingestion.index') }}" class="btn btn-sm btn-outline-primary">Preview import</a>
        </div>
    @endif

    <form method="GET" class="card mb-4">
        <div class="card-body row g-3">
            <div class="col-lg-3"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Topic or sub-topic"></div>
            <div class="col-lg-2"><select class="form-select" name="class"><option value="">All classes</option>@foreach($filters['classes'] as $value)<option @selected(request('class')===$value)>{{ $value }}</option>@endforeach</select></div>
            <div class="col-lg-2"><select class="form-select" name="subject"><option value="">All subjects</option>@foreach($filters['subjects'] as $value)<option @selected(request('subject')===$value)>{{ $value }}</option>@endforeach</select></div>
            <div class="col-lg-2"><select class="form-select" name="term"><option value="">All terms</option>@foreach($filters['terms'] as $value)<option @selected(request('term')===$value)>{{ $value }}</option>@endforeach</select></div>
            <div class="col-lg-2"><select class="form-select" name="status"><option value="">All status</option>@foreach(['draft','review','approved'] as $value)<option value="{{ $value }}" @selected(request('status')===$value)>{{ ucfirst($value) }}</option>@endforeach</select></div>
            <div class="col-lg-1 d-grid"><button class="btn btn-primary">Filter</button></div>
        </div>
    </form>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center"><strong>Mapped curriculum topics</strong><span class="small text-muted">{{ $total }} records</span></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Week</th><th>Class / Subject</th><th>Topic</th><th>Sources</th><th>Coverage / Quality</th><th>Readiness</th><th></th></tr></thead>
                <tbody>
                @forelse($topics as $topic)
                    @php $r = $readiness[$topic->id]; @endphp
                    <tr>
                        <td>{{ $topic->week_number ? 'W'.$topic->week_number : '—' }}</td>
                        <td><strong>{{ $topic->class_label }}</strong><div class="small text-muted">{{ $topic->subject_label }} · {{ $topic->term_label }}</div></td>
                        <td><strong>{{ $topic->topic }}</strong>@if($topic->sub_topic)<div class="small text-muted">{{ $topic->sub_topic }}</div>@endif</td>
                        <td><strong>{{ $r['consolidation']['source_count'] ?? 1 }}</strong>@if(($r['consolidation']['topic_count'] ?? 1) > 1)<div class="small text-muted">{{ $r['consolidation']['topic_count'] }} related records</div>@endif</td>
                        <td><div class="small">Coverage <strong>{{ $r['coverage_score'] ?? 0 }}%</strong></div><div class="small">Quality <strong>{{ $r['quality_score'] ?? 0 }}%</strong></div></td>
                        <td style="min-width:160px"><div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:8px"><div class="progress-bar" style="width:{{ $r['score'] }}%"></div></div><strong>{{ $r['score'] }}%</strong></div></td>
                        <td class="text-end"><a href="{{ route('academic-repository.knowledge.show', $topic) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">No structured curriculum topics yet. @if(auth()->user()?->isAdmin())Use <strong>Import Repository</strong> to create topic scaffolds from your existing indexed resources.@endif</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
