@extends('layouts.app')

@section('title', 'Curriculum Knowledge Base')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="text-uppercase small fw-bold text-muted mb-1">Academic Repository</div>
            <h1 class="h3 mb-1">Curriculum Knowledge Base</h1>
            <p class="text-muted mb-0">Structured, approved curriculum content for non-AI lesson plans and student notes.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('academic-repository.index') }}" class="btn btn-outline-secondary">Repository</a>
            @if(auth()->user()?->isAdmin())
                <a href="{{ route('academic-repository.knowledge.create') }}" class="btn btn-primary">Add topic</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $total = $topics->count();
        $approved = $topics->where('status', 'approved')->count();
        $ready = $readiness->filter(fn($r) => $r['ready'])->count();
        $avg = $total ? round($readiness->avg('score')) : 0;
    @endphp
    <div class="row g-3 mb-4">
        @foreach([
            ['Topics', $total],
            ['Approved', $approved],
            ['Generation-ready', $ready],
            ['Average readiness', $avg.'%'],
        ] as [$label, $value])
            <div class="col-6 col-xl-3">
                <div class="card h-100"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="fs-3 fw-bold">{{ $value }}</div></div></div>
            </div>
        @endforeach
    </div>

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
                <thead><tr><th>Week</th><th>Class / Subject</th><th>Topic</th><th>Status</th><th>Readiness</th><th></th></tr></thead>
                <tbody>
                @forelse($topics as $topic)
                    @php $r = $readiness[$topic->id]; @endphp
                    <tr>
                        <td>{{ $topic->week_number ? 'W'.$topic->week_number : '—' }}</td>
                        <td><strong>{{ $topic->class_label }}</strong><div class="small text-muted">{{ $topic->subject_label }} · {{ $topic->term_label }}</div></td>
                        <td><strong>{{ $topic->topic }}</strong>@if($topic->sub_topic)<div class="small text-muted">{{ $topic->sub_topic }}</div>@endif</td>
                        <td><span class="badge {{ $topic->status === 'approved' ? 'bg-success' : ($topic->status === 'review' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ ucfirst($topic->status) }}</span></td>
                        <td style="min-width:160px"><div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:8px"><div class="progress-bar" style="width:{{ $r['score'] }}%"></div></div><strong>{{ $r['score'] }}%</strong></div></td>
                        <td class="text-end"><a href="{{ route('academic-repository.knowledge.show', $topic) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">No structured curriculum topics yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
