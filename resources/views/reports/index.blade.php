@extends('layouts.app')
@section('title', 'Report Cards')
@section('page-title', 'Report Cards')

@push('styles')
<style>
    .page-tabs{display:flex;gap:6px;padding:5px;margin-bottom:20px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04);flex-wrap:wrap}
    .page-tab{padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;color:var(--slate);text-decoration:none;transition:.15s ease}
    .page-tab:hover{background:#F1F5F9;color:var(--midnight)}
    .page-tab.active{background:var(--indigo);color:#fff}
    .compute-card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:28px;box-shadow:0 8px 24px rgba(15,23,42,.05)}
    .compute-title{font-size:20px;font-weight:750;color:var(--midnight);letter-spacing:-.02em;margin-bottom:6px}
    .compute-sub{font-size:13px;color:var(--slate);line-height:1.6;margin-bottom:22px}
    .pg-split{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}
    @media(max-width:900px){.pg-split{grid-template-columns:1fr}}
    .steps{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:24px}
    .step{display:flex;align-items:flex-start;gap:10px;padding:13px;background:#F8FAFC;border:1px solid var(--border);border-radius:10px}
    .step-num{width:25px;height:25px;border-radius:50%;background:var(--indigo);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex:0 0 auto}
    .step-text{font-size:12px;color:var(--midnight);line-height:1.5}.step-text strong{font-weight:700}
    .form-group{margin-bottom:16px}.form-label{display:block;margin-bottom:6px;font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}.form-label span{color:var(--crimson)}
    .form-control{width:100%;padding:11px 12px;border:1px solid var(--border);border-radius:9px;background:#F8FAFC;color:var(--midnight);font:inherit;font-size:13px;outline:none}.form-control:focus{background:#fff;border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
    .btn{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border:0;border-radius:9px;font:inherit;font-size:13px;font-weight:700;cursor:pointer}.btn-primary{background:var(--indigo);color:#fff}.btn-primary:hover{background:#1D4ED8}
    .alert-success{margin-bottom:16px;padding:12px 15px;border:1px solid #A7F3D0;border-radius:9px;background:#ECFDF5;color:#047857;font-size:13px}
    @media(max-width:760px){.steps{grid-template-columns:1fr}.compute-card{padding:20px}}
</style>
@endpush

@section('content')
<div class="page-tabs" style="margin-bottom:20px">
    <a href="{{ route('reports.index') }}"        class="page-tab {{ request()->routeIs('reports.index') ? 'active' : '' }}">Generate</a>
    <a href="{{ route('reports.publications') }}" class="page-tab {{ request()->routeIs('reports.publications*') ? 'active' : '' }}">Publish / Unpublish</a>
    <a href="{{ route('reports.remarks') }}" class="page-tab {{ request()->routeIs('reports.remarks*') ? 'active' : '' }}">Remarks</a>
    @if(request()->filled('class_arm_id') && request()->filled('term_id'))
        <a href="{{ route('reports.preview', request()->only(['class_arm_id','term_id'])) }}" class="page-tab">Preview Cards</a>
    @endif
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="pg-split">
<div class="compute-card">
    <div class="compute-title">Generate Report Cards</div>
    <div class="compute-sub">Computes final averages, positions, and grades for every student in the selected class and term, then prepares printable PDF report cards.</div>

    <div class="steps">
        <div class="step">
            <div class="step-num">1</div>
            <div class="step-text"><strong>Scores must be fully entered</strong> for all subjects and assessment types before computing.</div>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <div class="step-text"><strong>Select the class and term</strong> below, then click Compute to calculate positions and averages.</div>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <div class="step-text"><strong>Add remarks</strong> from the preview page, then download individual or bulk PDFs.</div>
        </div>
    </div>

    @if(auth()->user()->canManage('reports'))
    <form method="POST" action="{{ route('reports.compute') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Class <span>*</span></label>
            <select name="class_arm_id" class="form-control" required>
                <option value="">Select class</option>
                @foreach($classArms as $arm)
                    <option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Term <span>*</span></label>
            <select name="term_id" class="form-control" required>
                <option value="">Select term</option>
                @foreach($terms as $term)
                    <option value="{{ $term->id }}" {{ $term->is_current ? 'selected' : '' }}>{{ $term->name }} — {{ $term->session->name ?? '' }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
            Compute Report Cards
        </button>
    </form>
    @endif
</div>

<div style="display:flex;flex-direction:column;gap:16px">
    <div class="card" style="position:sticky;top:calc(var(--header-h) + 16px)">
        <div class="ch">Report Card Workflow</div>
        <div class="cb" style="font-size:13px;color:var(--slate);line-height:1.7">
            <p style="margin-bottom:10px"><strong style="color:var(--midnight)">Before computing</strong>, ensure all scores are fully entered for the selected class and term via Score Entry.</p>
            <p style="margin-bottom:10px"><strong style="color:var(--midnight)">Computing</strong> calculates positions, averages, and grades — it replaces any previous computation for that class/term.</p>
            <p style="margin-bottom:10px"><strong style="color:var(--midnight)">Remarks</strong> (form teacher & principal) are added from the Report Cards → Remarks tab after computing.</p>
            <p><strong style="color:var(--midnight)">Publishing</strong> makes report cards visible to students and parents on the portal.</p>
        </div>
    </div>
</div>
</div>
@endsection
