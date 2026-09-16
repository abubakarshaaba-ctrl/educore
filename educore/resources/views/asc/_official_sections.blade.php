@php
    $officialRecords = \App\Models\AscSectionData::where('tenant_id', $tenant->id)
        ->where('census_year', $year)
        ->get()
        ->keyBy('section');
    $snapshotAuto = $ascReturn?->auto_data ?? [];
    $derivedC = data_get($snapshotAuto, 'official.section_c', []);
    $derivedE = data_get($snapshotAuto, 'official.section_e', []);
    $cManualGapCount = count((array) data_get($derivedC, 'unsupported_fields', []));
    $eGapCount = count((array) data_get($derivedE, 'unclassified_or_unassigned_teachers', []));
    $sectionOrder = ['B', 'C', 'D', 'E', 'F', 'G', 'H'];
@endphp

<div class="official-sections-card">
    <div class="official-sections-head">
        <div>
            <div class="official-sections-title">Official Census Sections</div>
            <div class="official-sections-subtitle">Sections C and E are derived from EduCore records; B, D, F, G and H require census-year confirmation/input.</div>
        </div>
        <span class="official-mapping-badge">19-page form mapping</span>
    </div>

    <div class="official-section-grid">
        @foreach($sectionOrder as $s)
            @if(in_array($s, ['C', 'E'], true))
                @php
                    $isC = $s === 'C';
                    $gapCount = $isC ? $cManualGapCount : $eGapCount;
                    $hasSnapshot = (bool) $ascReturn;
                    $label = $isC ? 'Enrolment' : 'Teachers';
                    $status = $hasSnapshot ? ($gapCount ? 'REVIEW REQUIRED' : 'AUTO / DERIVED') : 'SYNC REQUIRED';
                    $tone = !$hasSnapshot ? 'sync' : ($gapCount ? 'warning' : 'derived');
                @endphp
                <a href="{{ route('asc.derived.show', ['section' => strtolower($s), 'year' => $year]) }}"
                   class="official-section-item {{ $tone }}"
                   aria-label="Open Section {{ $s }} — {{ $label }}">
                    <div class="official-section-main">
                        <span class="official-section-name">Section {{ $s }} — {{ $label }}</span>
                        <span class="official-section-status">{{ $status }}</span>
                    </div>
                    <span class="official-section-chevron" aria-hidden="true">›</span>
                    @if($hasSnapshot)
                        <div class="official-section-detail">
                            {{ $isC ? 'Age-by-grade, streams, entrants, progression, transfers and special needs synchronized.' : 'Qualification × sex × main teaching level derived from current allocations.' }}
                        </div>
                        @if($gapCount)
                            <div class="official-section-gap">
                                {{ $isC ? $gapCount.' source gap(s) remain manual/unsupported.' : $gapCount.' teacher(s) need allocation review.' }}
                            </div>
                        @endif
                    @endif
                </a>
            @else
                @php $r = $officialRecords->get($s); @endphp
                <a href="{{ route('asc.section.show',['section'=>strtolower($s),'year'=>$year]) }}"
                   class="official-section-item manual {{ $r?->is_complete ? 'complete' : 'open' }}"
                   aria-label="Open Section {{ $s }}">
                    <div class="official-section-main">
                        <span class="official-section-name">Section {{ $s }}</span>
                        <span class="official-section-status">{{ $r?->is_complete ? 'COMPLETE' : 'OPEN' }}</span>
                    </div>
                    <span class="official-section-chevron" aria-hidden="true">›</span>
                </a>
            @endif
        @endforeach
    </div>
</div>

@push('styles')
<style>
.official-sections-card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:16px 20px;margin-bottom:18px;min-width:0}
.official-sections-head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.official-sections-title{font-size:13px;font-weight:900;color:var(--midnight)}
.official-sections-subtitle{font-size:11px;color:var(--slate-light);margin-top:2px;line-height:1.5}
.official-mapping-badge{font-size:10px;font-weight:800;color:#475569;background:#F1F5F9;border-radius:999px;padding:5px 9px;white-space:nowrap}
.official-section-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(165px,1fr));gap:9px;min-width:0}
.official-section-item{position:relative;text-decoration:none;border:1px solid #E2E8F0;background:#FAFAFA;border-radius:10px;padding:11px 34px 11px 12px;display:block;min-width:0;transition:border-color .15s ease,box-shadow .15s ease,transform .15s ease}
.official-section-item:hover{box-shadow:0 5px 16px rgba(15,23,42,.07);transform:translateY(-1px)}
.official-section-main{display:flex;justify-content:space-between;align-items:center;gap:8px;min-width:0}
.official-section-name{font-size:12px;font-weight:800;color:var(--midnight);min-width:0;overflow-wrap:anywhere}
.official-section-status{font-size:9px;font-weight:900;white-space:nowrap}
.official-section-chevron{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:20px;font-weight:700;color:#94A3B8}
.official-section-item.manual.open .official-section-status{color:#92400E}
.official-section-item.manual.complete{border-color:#A7F3D0;background:#ECFDF5}.official-section-item.manual.complete .official-section-status{color:#047857}
.official-section-item.derived{border-color:#BFDBFE;background:#EFF6FF}.official-section-item.derived .official-section-name,.official-section-item.derived .official-section-status{color:#1E40AF}
.official-section-item.sync{border-color:#BFDBFE;background:#EFF6FF}.official-section-item.sync .official-section-name,.official-section-item.sync .official-section-status{color:#2563EB}
.official-section-item.warning{border-color:#FDE68A;background:#FFFBEB}.official-section-item.warning .official-section-name,.official-section-item.warning .official-section-status{color:#92400E}
.official-section-detail,.official-section-gap{font-size:10px;line-height:1.45;margin-top:5px;overflow-wrap:anywhere}.official-section-detail{color:#64748B}.official-section-gap{color:#92400E}

@media (max-width: 767px){
    .official-sections-card{padding:14px;margin-left:0;margin-right:0}
    .official-sections-head{align-items:flex-start}
    .official-mapping-badge{white-space:normal}
    .official-section-grid{grid-template-columns:1fr;gap:10px}
    .official-section-item{padding:13px 36px 13px 14px}
    .official-section-main{align-items:flex-start;flex-direction:column;gap:4px}
    .official-section-status{white-space:normal}

    /* Shared ASC workspace mobile rules. This partial is loaded by the workspace, so these also protect the surrounding page. */
    .sync-grid,.section-grid,.section-grid.small{grid-template-columns:1fr !important}
    .finput{max-width:100%}
    .btn-sync,.btn-secondary,.btn-finalize{width:100%;min-height:42px}
    .asc-section,.metric-card{min-width:0}
    .source-badge{display:inline-block;margin-top:4px;margin-left:0}
}

@media (max-width: 420px){
    .official-sections-card{padding:12px;border-radius:12px}
    .official-sections-title{font-size:14px}
    .official-sections-subtitle{font-size:10.5px}
    .official-section-name{font-size:12px}
}
</style>
@endpush
