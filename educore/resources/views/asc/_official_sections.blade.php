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
@endphp
<div style="background:white;border:1px solid var(--border);border-radius:14px;padding:16px 20px;margin-bottom:18px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px">
        <div>
            <div style="font-size:13px;font-weight:900;color:var(--midnight)">Official Census Sections</div>
            <div style="font-size:11px;color:var(--slate-light);margin-top:2px">Sections C and E are derived from EduCore records; B, D, F, G and H require census-year confirmation/input.</div>
        </div>
        <span style="font-size:10px;font-weight:800;color:#475569;background:#F1F5F9;border-radius:999px;padding:5px 9px">19-page form mapping</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(165px,1fr));gap:9px">
        @foreach(config('asc.manual_sections') as $s)
            @php $r = $officialRecords->get($s); @endphp
            <a href="{{ route('asc.section.show',['section'=>strtolower($s),'year'=>$year]) }}"
               style="text-decoration:none;border:1px solid {{ $r?->is_complete ? '#A7F3D0' : '#E2E8F0' }};background:{{ $r?->is_complete ? '#ECFDF5' : '#FAFAFA' }};border-radius:10px;padding:11px 12px;display:flex;justify-content:space-between;align-items:center;gap:8px">
                <span style="font-size:12px;font-weight:800;color:var(--midnight)">Section {{ $s }}</span>
                <span style="font-size:9px;font-weight:900;color:{{ $r?->is_complete ? '#047857' : '#92400E' }}">{{ $r?->is_complete ? 'COMPLETE' : 'OPEN' }}</span>
            </a>
        @endforeach

        <div style="border:1px solid {{ $cManualGapCount ? '#FDE68A' : '#BFDBFE' }};background:{{ $cManualGapCount ? '#FFFBEB' : '#EFF6FF' }};border-radius:10px;padding:11px 12px">
            <div style="font-size:12px;font-weight:800;color:{{ $cManualGapCount ? '#92400E' : '#1E40AF' }}">Section C — Enrolment</div>
            <div style="font-size:9px;font-weight:900;color:{{ $cManualGapCount ? '#B45309' : '#2563EB' }};margin-top:3px">
                {{ $ascReturn ? 'AUTO / DERIVED' : 'SYNC REQUIRED' }}
            </div>
            @if($ascReturn)
                <div style="font-size:10px;color:#64748B;margin-top:5px">Age-by-grade, streams, entrants, progression, transfers and special needs synchronized.</div>
                @if($cManualGapCount)
                    <div style="font-size:10px;color:#92400E;margin-top:5px">{{ $cManualGapCount }} source gap(s) remain manual/unsupported.</div>
                @endif
            @endif
        </div>

        <div style="border:1px solid {{ $eGapCount ? '#FCA5A5' : '#BFDBFE' }};background:{{ $eGapCount ? '#FEF2F2' : '#EFF6FF' }};border-radius:10px;padding:11px 12px">
            <div style="font-size:12px;font-weight:800;color:{{ $eGapCount ? '#991B1B' : '#1E40AF' }}">Section E — Teachers</div>
            <div style="font-size:9px;font-weight:900;color:{{ $eGapCount ? '#B91C1C' : '#2563EB' }};margin-top:3px">
                {{ $ascReturn ? 'AUTO / DERIVED' : 'SYNC REQUIRED' }}
            </div>
            @if($ascReturn)
                <div style="font-size:10px;color:#64748B;margin-top:5px">Qualification × sex × main teaching level derived from current allocations.</div>
                @if($eGapCount)
                    <div style="font-size:10px;color:#991B1B;margin-top:5px">{{ $eGapCount }} teacher(s) need allocation review.</div>
                @endif
            @endif
        </div>
    </div>
</div>
