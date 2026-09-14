@extends('layouts.app')

@section('title', 'Repository Ingestion')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="text-uppercase small fw-bold text-muted mb-1">Academic Repository</div>
            <h1 class="h3 mb-1">Repository Ingestion</h1>
            <p class="text-muted mb-0">Convert existing indexed repository resources into structured draft curriculum topics without AI.</p>
        </div>
        <a href="{{ route('academic-repository.knowledge.index') }}" class="btn btn-outline-secondary">Back to Knowledge Base</a>
    </div>

    <div class="alert alert-info">
        <strong>Safe import:</strong> ingestion never auto-approves content. Imported records remain <strong>Draft</strong> until an administrator reviews the mapping and instructional fields.
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Repository sources', $summary['total_sources']],
            ['Mapped sources', $summary['mapped_sources']],
            ['Ready to ingest', $summary['ready_to_ingest']],
            ['Candidate topics', $summary['candidate_topics']],
            ['Already ingested', $summary['already_ingested']],
            ['Unmapped sources', $summary['unmapped_sources']],
        ] as [$label, $value])
            <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body"><div class="small text-muted">{{ $label }}</div><div class="fs-3 fw-bold">{{ number_format($value) }}</div></div></div></div>
        @endforeach
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center"><strong>Ingestion preview</strong><span class="small text-muted">Only mapped, not-yet-ingested sources will be processed</span></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Source</th><th>Mapping</th><th>Type</th><th>Topics</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($sources as $row)
                    <tr>
                        <td><strong>{{ $row['title'] }}</strong><div class="small text-muted">Source #{{ $row['source_id'] }}</div></td>
                        <td>
                            @if($row['mapped'])
                                <strong>{{ $row['class_label'] }}</strong><div class="small text-muted">{{ $row['subject_label'] }} · {{ $row['term_label'] }}</div>
                            @else
                                <span class="text-danger">Class / subject / term metadata incomplete</span>
                            @endif
                        </td>
                        <td>{{ str($row['resource_type'])->replace('_', ' ')->title() }}</td>
                        <td>{{ $row['candidate_topics'] }}</td>
                        <td>
                            @if($row['already_ingested'])
                                <span class="badge bg-secondary">Already ingested</span>
                            @elseif(!$row['mapped'])
                                <span class="badge bg-warning text-dark">Needs mapping</span>
                            @else
                                <span class="badge bg-success">Ready</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">No indexed repository sources were found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($summary['ready_to_ingest'] > 0)
        <form method="POST" action="{{ route('academic-repository.knowledge.ingestion.store') }}" class="card border-primary">
            @csrf
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <strong>Import mapped repository content</strong>
                    <div class="small text-muted">Standard lesson-plan headings are parsed deterministically. Scheme-of-work week rows become draft topic scaffolds. Generic note fragments become unapproved explanatory blocks for review.</div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <label class="form-check mb-0"><input class="form-check-input" type="checkbox" name="confirm" value="1" required> <span class="form-check-label">Create draft records</span></label>
                    <button class="btn btn-primary">Ingest {{ $summary['ready_to_ingest'] }} source(s)</button>
                </div>
            </div>
        </form>
    @else
        <div class="alert alert-secondary mb-0">There are no new mapped sources ready for ingestion.</div>
    @endif
</div>
@endsection
