@extends('layouts.app')
@section('title','ASC — Review & Validation')
@section('page-title','Annual School Census — Review & Validation')

@section('content')
@php
    $locked = $ascReturn?->isLocked() ?? false;
    $syncScore = (int) ($ascReturn?->completeness['score'] ?? 0);
    $ready = (bool) ($ascReturn?->completeness['ready_to_finalize'] ?? false);
    $balanced = (bool) ($reconciliation['balanced'] ?? false);
    $status = strtoupper($ascReturn?->status ?? 'NOT SYNCHRONIZED');
@endphp

<div class="asc-review-shell">
    <div class="asc-review-topbar">
        <div class="asc-review-heading">
            <div class="asc-review-title">Consolidated ASC Review</div>
            <div class="asc-review-subtitle">Review Sections B–H, synchronization quality, blocking issues and finalization readiness before producing the census return.</div>
        </div>
        <div class="asc-review-actions">
            <a href="{{ route('asc.infrastructure', ['year' => $year]) }}" class="asc-btn secondary">← Census Workspace</a>
            <a href="{{ route('asc.report', ['year' => $year]) }}" class="asc-btn primary">View ASC Report</a>
        </div>
    </div>

    <div class="asc-review-yearline">
        <div><strong>Census year:</strong> {{ $year }}/{{ $year + 1 }}</div>
        <span class="asc-status-pill {{ $locked ? 'locked' : ($ascReturn ? 'draft' : 'neutral') }}">{{ $status }}</span>
    </div>

    <div class="asc-metric-grid">
        <div class="asc-review-metric">
            <span>Official Sections</span>
            <strong>{{ $completionCount }}/{{ $sections->count() }}</strong>
            <small>{{ $sectionCompletionPercent }}% section readiness</small>
        </div>
        <div class="asc-review-metric">
            <span>Data Completeness</span>
            <strong>{{ $syncScore }}%</strong>
            <small>{{ $ascReturn ? (($ascReturn->completeness['complete_checks'] ?? 0).' / '.($ascReturn->completeness['total_checks'] ?? 0).' checks') : 'Synchronize first' }}</small>
        </div>
        <div class="asc-review-metric">
            <span>Reconciliation</span>
            <strong>{{ $ascReturn ? ($balanced ? 'Balanced' : 'Review') : '—' }}</strong>
            <small>{{ $ascReturn ? (($reconciliation['duplicate_current_enrollments'] ?? 0).' duplicate current enrolment(s)') : 'No synchronized snapshot' }}</small>
        </div>
        <div class="asc-review-metric">
            <span>Finalization</span>
            <strong>{{ $locked ? 'Locked' : ($ready ? 'Ready' : 'Not Ready') }}</strong>
            <small>{{ count($blockingIssues) }} blocking issue(s)</small>
        </div>
    </div>

    @if($ascReturn)
    <div class="asc-sync-summary">
        <div><span>Reference date</span><strong>{{ optional($ascReturn->reference_date)->format('d M Y') ?? '—' }}</strong></div>
        <div><span>Last synchronized</span><strong>{{ optional($ascReturn->synchronized_at)->format('d M Y, h:i A') ?? '—' }}</strong></div>
        <div><span>Students analysed</span><strong>{{ data_get($auto, 'enrolment.total', '—') }}</strong></div>
        <div><span>Staff analysed</span><strong>{{ data_get($auto, 'staff.total', '—') }}</strong></div>
        @if($ascReturn->finalized_at)
        <div><span>Finalized</span><strong>{{ $ascReturn->finalized_at->format('d M Y, h:i A') }}</strong></div>
        @endif
    </div>
    @else
    <div class="asc-notice info">
        <strong>No synchronized snapshot yet.</strong> Return to the Census Workspace, select the census reference date and synchronize EduCore data before reviewing Sections C and E.
    </div>
    @endif

    <section class="asc-review-card">
        <div class="asc-card-head">
            <div>
                <h2>Sections B–H</h2>
                <p>Manual sections and AUTO / DERIVED sections are shown together in official sequence.</p>
            </div>
        </div>

        <div class="asc-section-review-grid">
            @foreach($sections as $item)
            <a href="{{ $item['route'] }}" class="asc-review-section {{ $item['complete'] ? 'complete' : 'attention' }}">
                <div class="asc-review-section-top">
                    <div>
                        <span class="asc-section-code">SECTION {{ $item['section'] }}</span>
                        <strong>{{ $item['title'] }}</strong>
                    </div>
                    <span class="asc-section-state">{{ $item['status'] }}</span>
                </div>
                <div class="asc-section-source">{{ $item['source'] }}</div>
                <p>{{ $item['detail'] }}</p>
                <span class="asc-open-link">Review section →</span>
            </a>
            @endforeach
        </div>
    </section>

    <div class="asc-review-two-column">
        <section class="asc-review-card">
            <div class="asc-card-head">
                <div>
                    <h2>Validation & Data Quality</h2>
                    <p>Issues detected during the latest synchronization.</p>
                </div>
                <span class="asc-count-badge">{{ count($issues) }}</span>
            </div>

            @if(count($issues))
                <div class="asc-issue-list">
                    @foreach($issues as $issue)
                    <div class="asc-issue-row {{ in_array($issue, $blockingIssues, true) ? 'blocking' : 'warning' }}">
                        <span>{{ in_array($issue, $blockingIssues, true) ? '!' : '•' }}</span>
                        <div>
                            <strong>{{ in_array($issue, $blockingIssues, true) ? 'Blocking' : 'Review' }}</strong>
                            <p>{{ $issue }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="asc-empty-state">{{ $ascReturn ? 'No synchronization issues are currently recorded.' : 'Synchronize the return to run validation checks.' }}</div>
            @endif
        </section>

        <section class="asc-review-card">
            <div class="asc-card-head">
                <div>
                    <h2>Reconciliation</h2>
                    <p>Cross-check synchronized enrolment against current EduCore records.</p>
                </div>
            </div>

            @if($ascReturn)
            <div class="asc-recon-list">
                <div><span>Current enrolment rows</span><strong>{{ $reconciliation['current_enrollment_rows'] ?? '—' }}</strong></div>
                <div><span>Distinct current students</span><strong>{{ $reconciliation['distinct_current_students'] ?? '—' }}</strong></div>
                <div><span>Synchronized students</span><strong>{{ $reconciliation['synchronized_students'] ?? '—' }}</strong></div>
                <div><span>Duplicate current enrolments</span><strong>{{ $reconciliation['duplicate_current_enrollments'] ?? '—' }}</strong></div>
                <div class="asc-recon-result {{ $balanced ? 'ok' : 'bad' }}"><span>Overall result</span><strong>{{ $balanced ? 'BALANCED' : 'REVIEW REQUIRED' }}</strong></div>
            </div>
            @else
            <div class="asc-empty-state">No reconciliation result is available until the census return is synchronized.</div>
            @endif
        </section>
    </div>

    @if(count($sectionCUnsupported) || count($sectionEUnassigned))
    <section class="asc-review-card">
        <div class="asc-card-head">
            <div>
                <h2>Derived-Data Exceptions</h2>
                <p>Items EduCore cannot safely infer are surfaced for explicit review instead of being guessed.</p>
            </div>
        </div>

        <div class="asc-exception-grid">
            <div>
                <h3>Section C — Source gaps</h3>
                @if(count($sectionCUnsupported))
                    @foreach($sectionCUnsupported as $field => $reason)
                    <div class="asc-exception-item"><strong>{{ ucwords(str_replace('_', ' ', $field)) }}</strong><span>{{ $reason }}</span></div>
                    @endforeach
                @else
                    <div class="asc-empty-state compact">No Section C source gaps recorded.</div>
                @endif
            </div>
            <div>
                <h3>Section E — Teacher allocation review</h3>
                @if(count($sectionEUnassigned))
                    @foreach($sectionEUnassigned as $teacher)
                    <div class="asc-exception-item"><strong>{{ $teacher['name'] ?? 'Teacher' }}</strong><span>{{ $teacher['reason'] ?? 'Allocation requires review.' }}</span></div>
                    @endforeach
                @else
                    <div class="asc-empty-state compact">All synchronized teachers have a resolved main teaching level.</div>
                @endif
            </div>
        </div>
    </section>
    @endif

    <div class="asc-final-readiness {{ $locked ? 'locked' : ($ready ? 'ready' : 'not-ready') }}">
        <div>
            <strong>{{ $locked ? 'Census snapshot finalized and locked' : ($ready ? 'Census is ready for finalization' : 'Census is not ready for finalization') }}</strong>
            <p>{{ $locked ? 'The historical synchronized snapshot is protected from later operational-data changes.' : ($ready ? 'All blocking checks currently pass. Finalization is performed from the Census Workspace.' : 'Resolve the blocking issues, complete manual sections and synchronize again before finalizing.') }}</p>
        </div>
        <a href="{{ route('asc.infrastructure', ['year' => $year]) }}" class="asc-btn {{ $ready && !$locked ? 'primary' : 'secondary' }}">{{ $locked ? 'Open Workspace' : 'Return to Workspace' }}</a>
    </div>
</div>
@endsection

@push('styles')
<style>
.asc-review-shell{min-width:0;max-width:1440px;margin:0 auto}
.asc-review-topbar{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:16px}
.asc-review-heading{min-width:0;flex:1 1 420px}.asc-review-title{font-size:20px;font-weight:900;color:var(--midnight)}.asc-review-subtitle{font-size:12px;line-height:1.6;color:var(--slate-light);margin-top:4px;max-width:760px}
.asc-review-actions{display:flex;gap:8px;flex-wrap:wrap}.asc-btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:9px 14px;border-radius:9px;text-decoration:none;font-size:12px;font-weight:800;border:1px solid transparent}.asc-btn.primary{background:var(--indigo);color:#fff}.asc-btn.secondary{background:#fff;color:var(--midnight);border-color:var(--border)}
.asc-review-yearline{background:#fff;border:1px solid var(--border);border-radius:12px;padding:12px 15px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;font-size:12px;color:var(--slate)}
.asc-status-pill{padding:5px 9px;border-radius:999px;font-size:10px;font-weight:900}.asc-status-pill.neutral{background:#F1F5F9;color:#475569}.asc-status-pill.draft{background:#FEF3C7;color:#92400E}.asc-status-pill.locked{background:#DBEAFE;color:#1D4ED8}
.asc-metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:14px}.asc-review-metric{background:#fff;border:1px solid var(--border);border-radius:12px;padding:14px;min-width:0}.asc-review-metric span{display:block;font-size:10px;font-weight:800;color:var(--slate-light);text-transform:uppercase;letter-spacing:.03em}.asc-review-metric strong{display:block;font-size:21px;color:var(--midnight);margin:5px 0 2px;overflow-wrap:anywhere}.asc-review-metric small{display:block;color:#64748B;font-size:10px;line-height:1.45}
.asc-sync-summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1px;background:var(--border);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}.asc-sync-summary>div{background:#fff;padding:12px 14px;min-width:0}.asc-sync-summary span{display:block;font-size:10px;color:var(--slate-light);margin-bottom:3px}.asc-sync-summary strong{display:block;font-size:12px;color:var(--midnight);overflow-wrap:anywhere}
.asc-notice{border-radius:11px;padding:12px 14px;margin-bottom:14px;font-size:12px;line-height:1.6}.asc-notice.info{background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF}
.asc-review-card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:16px;margin-bottom:14px;min-width:0}.asc-card-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:13px}.asc-card-head h2{font-size:14px;color:var(--midnight);margin:0;font-weight:900}.asc-card-head p{font-size:11px;color:var(--slate-light);line-height:1.5;margin:3px 0 0}.asc-count-badge{min-width:28px;height:28px;border-radius:999px;background:#F1F5F9;color:#475569;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:900}
.asc-section-review-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(225px,1fr));gap:10px}.asc-review-section{display:block;text-decoration:none;border:1px solid #E2E8F0;border-radius:12px;padding:13px;min-width:0;transition:box-shadow .15s ease,transform .15s ease}.asc-review-section:hover{box-shadow:0 6px 18px rgba(15,23,42,.07);transform:translateY(-1px)}.asc-review-section.complete{background:#F0FDF4;border-color:#BBF7D0}.asc-review-section.attention{background:#FFFBEB;border-color:#FDE68A}.asc-review-section-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}.asc-review-section-top>div{min-width:0}.asc-section-code{display:block;font-size:9px;letter-spacing:.05em;font-weight:900;color:#64748B;margin-bottom:3px}.asc-review-section-top strong{display:block;font-size:13px;color:var(--midnight);overflow-wrap:anywhere}.asc-section-state{font-size:9px;font-weight:900;white-space:nowrap;color:#475569}.asc-section-source{display:inline-block;font-size:9px;font-weight:800;color:#475569;background:rgba(255,255,255,.75);padding:4px 7px;border-radius:999px;margin-top:9px}.asc-review-section p{font-size:10.5px;line-height:1.5;color:#64748B;margin:8px 0}.asc-open-link{font-size:10px;font-weight:800;color:#2563EB}
.asc-review-two-column{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:14px}.asc-review-two-column>.asc-review-card{height:fit-content}
.asc-issue-list{display:grid;gap:8px}.asc-issue-row{display:flex;gap:9px;align-items:flex-start;padding:10px 11px;border-radius:9px}.asc-issue-row>span{width:22px;height:22px;flex:0 0 22px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;font-weight:900}.asc-issue-row strong{font-size:10px}.asc-issue-row p{font-size:10.5px;line-height:1.5;margin:2px 0 0;overflow-wrap:anywhere}.asc-issue-row.blocking{background:#FEF2F2;color:#991B1B}.asc-issue-row.blocking>span{background:#FEE2E2}.asc-issue-row.warning{background:#FFF7ED;color:#9A3412}.asc-issue-row.warning>span{background:#FFEDD5}
.asc-recon-list{display:grid;gap:1px;background:#E2E8F0;border:1px solid #E2E8F0;border-radius:10px;overflow:hidden}.asc-recon-list>div{background:#fff;padding:10px 12px;display:flex;justify-content:space-between;gap:12px;align-items:center;font-size:11px}.asc-recon-list span{color:#64748B}.asc-recon-list strong{color:var(--midnight);text-align:right}.asc-recon-result.ok{background:#ECFDF5!important}.asc-recon-result.ok strong{color:#047857}.asc-recon-result.bad{background:#FFF7ED!important}.asc-recon-result.bad strong{color:#9A3412}
.asc-empty-state{border:1px dashed #CBD5E1;border-radius:10px;padding:18px;text-align:center;font-size:11px;color:#64748B;background:#F8FAFC}.asc-empty-state.compact{padding:12px}
.asc-exception-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.asc-exception-grid h3{font-size:12px;color:var(--midnight);margin:0 0 8px}.asc-exception-item{border:1px solid #E2E8F0;border-radius:9px;padding:10px;margin-bottom:7px;min-width:0}.asc-exception-item strong{display:block;font-size:10.5px;color:var(--midnight);overflow-wrap:anywhere}.asc-exception-item span{display:block;font-size:10px;color:#64748B;line-height:1.5;margin-top:3px;overflow-wrap:anywhere}
.asc-final-readiness{border-radius:13px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap}.asc-final-readiness strong{font-size:13px}.asc-final-readiness p{font-size:10.5px;line-height:1.5;margin:3px 0 0;max-width:760px}.asc-final-readiness.ready{background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46}.asc-final-readiness.not-ready{background:#FFF7ED;border:1px solid #FED7AA;color:#9A3412}.asc-final-readiness.locked{background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF}

@media (max-width: 900px){
    .asc-metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .asc-review-two-column,.asc-exception-grid{grid-template-columns:1fr}
}
@media (max-width: 640px){
    .asc-review-shell{width:100%;overflow-x:hidden}
    .asc-review-topbar{display:block}.asc-review-heading{margin-bottom:12px}.asc-review-title{font-size:17px}.asc-review-subtitle{font-size:11px}
    .asc-review-actions{display:grid;grid-template-columns:1fr;width:100%}.asc-btn{width:100%}
    .asc-metric-grid{grid-template-columns:1fr}
    .asc-sync-summary{grid-template-columns:1fr}
    .asc-review-card{padding:13px;border-radius:12px}
    .asc-section-review-grid{grid-template-columns:1fr}
    .asc-review-section-top{flex-direction:column;gap:5px}.asc-section-state{white-space:normal}
    .asc-recon-list>div{align-items:flex-start}.asc-recon-list strong{max-width:45%;overflow-wrap:anywhere}
    .asc-final-readiness{align-items:stretch}.asc-final-readiness .asc-btn{margin-top:3px}
}
</style>
@endpush
