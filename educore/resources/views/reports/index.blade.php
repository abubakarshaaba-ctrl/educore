@extends('layouts.app')
@section('title', 'Report Cards')
@section('page-title', 'Report Cards')

@push('styles')
<style>
    .report-layout {
        display:grid;
        grid-template-columns:minmax(0,1fr) 320px;
        gap:20px;
        align-items:start;
    }

    .report-steps {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
        margin-bottom:22px;
    }

    .report-step {
        display:flex;
        gap:10px;
        align-items:flex-start;
        padding:13px;
        border:1px solid var(--brand-border, var(--border));
        border-radius:10px;
        background:var(--brand-page, #F8FAFC);
    }

    .report-step-number {
        width:26px;
        height:26px;
        flex:0 0 26px;
        display:flex;
        align-items:center;
        justify-content:center;
        border-radius:50%;
        background:var(--brand-gold, #D79A21);
        color:var(--brand-navy, #071E45);
        font-size:11px;
        font-weight:800;
    }

    .report-step-copy {
        color:var(--brand-text, var(--midnight));
        font-size:12px;
        line-height:1.5;
    }

    .report-step-copy strong { color:var(--brand-navy, var(--midnight)); }

    .report-workflow-copy {
        color:var(--brand-gray, var(--slate));
        font-size:13px;
        line-height:1.65;
    }

    .report-workflow-copy p { margin:0 0 12px; }
    .report-workflow-copy p:last-child { margin-bottom:0; }
    .report-workflow-copy strong { color:var(--brand-navy, var(--midnight)); }

    .report-side { position:sticky; top:calc(var(--header-h) + 16px); }

    @media(max-width:900px) {
        .report-layout { grid-template-columns:1fr; }
        .report-side { position:static; }
    }

    @media(max-width:760px) {
        .report-steps { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
<nav class="page-tabs" aria-label="Report card sections">
    <a href="{{ route('reports.index') }}" class="page-tab {{ request()->routeIs('reports.index') ? 'active' : '' }}" @if(request()->routeIs('reports.index')) aria-current="page" @endif>Generate</a>
    <a href="{{ route('reports.publications') }}" class="page-tab {{ request()->routeIs('reports.publications*') ? 'active' : '' }}">Publish / Unpublish</a>
    <a href="{{ route('reports.remarks') }}" class="page-tab {{ request()->routeIs('reports.remarks*') ? 'active' : '' }}">Remarks</a>
    @if(request()->filled('class_arm_id') && request()->filled('term_id'))
        <a href="{{ route('reports.preview', request()->only(['class_arm_id','term_id'])) }}" class="page-tab">Preview Cards</a>
    @endif
</nav>

@if(session('success'))
    <div class="alert-success" role="status" aria-live="polite">{{ session('success') }}</div>
@endif

<div class="report-layout">
    <section class="ec-card" aria-labelledby="generate-report-title">
        <div class="ec-card__header">
            <h2 id="generate-report-title" class="ec-card__title">Generate Report Cards</h2>
        </div>
        <div class="ec-card__body">
            <p class="hint" style="margin-top:0;margin-bottom:20px">
                Compute final averages, positions and grades for every student in the selected class and term, then prepare printable report cards.
            </p>

            <div class="report-steps" aria-label="Report card workflow summary">
                <div class="report-step">
                    <div class="report-step-number">1</div>
                    <div class="report-step-copy"><strong>Complete score entry.</strong> All subject assessments should be entered before computation.</div>
                </div>
                <div class="report-step">
                    <div class="report-step-number">2</div>
                    <div class="report-step-copy"><strong>Select class and term.</strong> EduCore computes averages, grades and positions for that scope.</div>
                </div>
                <div class="report-step">
                    <div class="report-step-number">3</div>
                    <div class="report-step-copy"><strong>Review, remark and publish.</strong> Add authorised remarks, preview cards and publish when ready.</div>
                </div>
            </div>

            @if(auth()->user()->canManage('reports'))
                <form method="POST" action="{{ route('reports.compute') }}">
                    @csrf
                    <div class="auto-grid-sm" style="margin-bottom:16px">
                        <div class="fg">
                            <label class="fl" for="report-class">Class <span aria-hidden="true">*</span></label>
                            <select id="report-class" name="class_arm_id" class="fc" required>
                                <option value="">Select class</option>
                                @foreach($classArms as $arm)
                                    <option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fl" for="report-term">Term <span aria-hidden="true">*</span></label>
                            <select id="report-term" name="term_id" class="fc" required>
                                <option value="">Select term</option>
                                @foreach($terms as $term)
                                    <option value="{{ $term->id }}" {{ $term->is_current ? 'selected' : '' }}>{{ $term->name }} — {{ $term->session->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
                        Compute Report Cards
                    </button>
                </form>
            @else
                <div class="alert-info">This account has read-only access to report cards.</div>
            @endif
        </div>
    </section>

    <aside class="ec-card report-side" aria-labelledby="report-workflow-title">
        <div class="ec-card__header">
            <h2 id="report-workflow-title" class="ec-card__title">Report Card Workflow</h2>
        </div>
        <div class="ec-card__body report-workflow-copy">
            <p><strong>Before computing:</strong> ensure all scores are fully entered for the selected class and term through Score Entry.</p>
            <p><strong>Computing:</strong> recalculates positions, averages and grades for the selected class and term.</p>
            <p><strong>Remarks:</strong> form-teacher and principal remarks are added from the Remarks tab after computation.</p>
            <p><strong>Publishing:</strong> makes report cards visible to authorised students and parents on the portal.</p>
        </div>
    </aside>
</div>
@endsection
