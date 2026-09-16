@extends('layouts.app')
@section('title', 'ASC — Derived Section ' . strtoupper($section))
@section('page-title', 'Annual School Census — Section ' . strtoupper($section))

@section('content')
<div class="asc-derived-topbar">
    <div class="asc-derived-heading">
        <div class="asc-derived-title">Section {{ strtoupper($section) }} — {{ $section === 'c' ? 'Enrolment' : 'Teachers Qualification' }}</div>
        <div class="asc-derived-subtitle">AUTO / DERIVED from EduCore operational records. Values update when the census is synchronized.</div>
    </div>
    <a href="{{ route('asc.infrastructure',['year'=>$year]) }}" class="asc-back">← Census Workspace</a>
</div>

@if(!$ascReturn)
<div class="asc-note warn"><strong>No synchronized snapshot exists for {{ $year }}/{{ $year+1 }}.</strong> Return to the Census Workspace and synchronize from EduCore first.</div>
@elseif(empty($data))
<div class="asc-note warn"><strong>Derived data is not present in this older snapshot.</strong> Refresh the census from EduCore to generate Section {{ strtoupper($section) }}.</div>
@else
<div class="asc-note asc-snapshot-note">
    <span><strong>Snapshot:</strong> {{ ucfirst($ascReturn->status) }}</span>
    <span><strong>Reference date:</strong> {{ optional($ascReturn->reference_date)->format('d M Y') }}</span>
    <span><strong>Last synchronized:</strong> {{ optional($ascReturn->synchronized_at)->format('d M Y, h:i A') }}</span>
</div>

@if($section === 'c')
    <div class="asc-card">
        <div class="asc-head">C.3 / C.5 / C.10 / C.12 — Enrolment by Age and Grade</div>
        <div class="table-wrap"><table class="asc-table"><thead><tr><th>Grade</th><th>Age</th><th>Male</th><th>Female</th><th>Unknown</th><th>Total</th></tr></thead><tbody>
        @forelse(data_get($data,'age_by_grade',[]) as $row)
            <tr><td>{{ $row['level_name'] ?? $row['grade_key'] }}</td><td>{{ $row['age'] }}</td><td>{{ $row['male'] }}</td><td>{{ $row['female'] }}</td><td>{{ $row['unknown_gender'] }}</td><td><strong>{{ $row['total'] }}</strong></td></tr>
        @empty<tr><td colspan="6">No age-by-grade records available.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    <div class="asc-card">
        <div class="asc-head">Number of Streams</div>
        <div class="table-wrap"><table class="asc-table"><thead><tr><th>Grade</th><th>Streams</th></tr></thead><tbody>
        @forelse(data_get($data,'streams_by_grade',[]) as $row)
            <tr><td>{{ $row['level_name'] }}</td><td><strong>{{ $row['streams'] }}</strong></td></tr>
        @empty<tr><td colspan="2">No class arms available.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    <div class="asc-card">
        <div class="asc-head">C.4 / C.9 / C.11 — New Entrants by Age</div>
        <div class="table-wrap"><table class="asc-table"><thead><tr><th>Entry Grade</th><th>Age</th><th>Male</th><th>Female</th><th>Total</th></tr></thead><tbody>
        @forelse(data_get($data,'new_entrants',[]) as $row)
            <tr><td>{{ strtoupper($row['grade_key']) }}</td><td>{{ $row['age'] ?? 'Unknown' }}</td><td>{{ $row['male'] }}</td><td>{{ $row['female'] }}</td><td><strong>{{ $row['total'] }}</strong></td></tr>
        @empty<tr><td colspan="5">No first-time entrants into PRY1/JSS1/SS1 were identified for the current session.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    <div class="asc-card">
        <div class="asc-head">C.1 / C.2 — Birth Certificate Availability</div>
        <div class="metric"><strong>{{ data_get($data,'birth_certificate.with_certificate',0) }}</strong><span>current pupils/students linked to an admission record with a birth-certificate file</span></div>
        <div class="asc-note warn asc-inline-warning">EduCore can verify that a certificate file exists, but it does not currently record whether the issuing authority was the National Population Commission or another body.</div>
    </div>

    <div class="asc-card">
        <div class="asc-head">C.6 / C.14 — Special Needs by Grade</div>
        <div class="table-wrap"><table class="asc-table"><thead><tr><th>Grade</th><th>Challenge</th><th>Male</th><th>Female</th><th>Unknown</th><th>Total</th></tr></thead><tbody>
        @forelse(data_get($data,'special_needs_by_grade',[]) as $row)
            <tr><td>{{ $row['level_name'] }}</td><td>{{ $row['special_needs_type'] }}</td><td>{{ $row['male'] }}</td><td>{{ $row['female'] }}</td><td>{{ $row['unknown_gender'] }}</td><td><strong>{{ $row['total'] }}</strong></td></tr>
        @empty<tr><td colspan="6">No current special-needs records were found.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    <div class="asc-card">
        <div class="asc-head">C.8 / C.13 — Pupil/Student Flow</div>
        @foreach(['promoted'=>'Promoted','repeaters'=>'Repeaters','transfer_in'=>'Transfer In','transfer_out'=>'Transfer Out'] as $key=>$label)
            <div class="asc-subhead">{{ $label }}</div>
            <div class="table-wrap"><table class="asc-table"><thead><tr><th>Grade</th><th>Male</th><th>Female</th><th>Total</th></tr></thead><tbody>
            @forelse(data_get($data,'pupil_flow.'.$key,[]) as $row)
                <tr><td>{{ strtoupper($row['grade_key']) }}</td><td>{{ $row['male'] }}</td><td>{{ $row['female'] }}</td><td><strong>{{ $row['total'] }}</strong></td></tr>
            @empty<tr><td colspan="4">No evidence-backed records.</td></tr>@endforelse
            </tbody></table></div>
        @endforeach
    </div>

    @if(data_get($data,'unsupported_fields'))
    <div class="asc-card">
        <div class="asc-head">Manual / Unsupported Source Fields</div>
        <div class="gap-list">
        @foreach(data_get($data,'unsupported_fields',[]) as $field=>$reason)
            <div><strong>{{ str($field)->replace('_',' ')->title() }}</strong><span>{{ $reason }}</span></div>
        @endforeach
        </div>
    </div>
    @endif
@else
    <div class="asc-card">
        <div class="asc-head">E — Teachers by Highest Qualification and Main Teaching Input</div>
        <div class="asc-note asc-basis-note">{{ data_get($data,'basis') }}</div>
        <div class="table-wrap"><table class="asc-table"><thead><tr><th>Highest Qualification</th><th>Main Teaching Level</th><th>Male</th><th>Female</th><th>Unknown</th><th>Total</th></tr></thead><tbody>
        @forelse(data_get($data,'qualification_by_main_teaching_level',[]) as $row)
            <tr><td>{{ $row['qualification'] }}</td><td>{{ str($row['main_teaching_level'])->replace('_',' ')->title() }}</td><td>{{ $row['male'] }}</td><td>{{ $row['female'] }}</td><td>{{ $row['unknown_gender'] }}</td><td><strong>{{ $row['total'] }}</strong></td></tr>
        @empty<tr><td colspan="6">No teacher allocations were available for derivation.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    @if(count(data_get($data,'unclassified_or_unassigned_teachers',[])))
    <div class="asc-card">
        <div class="asc-head">Teacher Allocation Issues — Must Resolve Before Finalization</div>
        <div class="gap-list">
        @foreach(data_get($data,'unclassified_or_unassigned_teachers',[]) as $teacher)
            <div><strong>{{ $teacher['name'] }}</strong><span>{{ $teacher['reason'] }}</span></div>
        @endforeach
        </div>
    </div>
    @endif
@endif
@endif

@push('styles')
<style>
.asc-derived-topbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;min-width:0}
.asc-derived-heading{min-width:0;flex:1 1 280px}
.asc-derived-title{font-size:18px;font-weight:900;color:var(--midnight);overflow-wrap:anywhere}
.asc-derived-subtitle{font-size:12px;color:var(--slate-light);margin-top:3px;line-height:1.45}
.asc-back{background:#F1F5F9;border:1px solid var(--border);color:var(--midnight);padding:9px 14px;border-radius:9px;text-decoration:none;font-size:12px;font-weight:800;display:inline-flex;align-items:center;justify-content:center;min-height:38px;box-sizing:border-box}
.asc-card{background:#fff;border:1px solid var(--border);border-radius:13px;overflow:hidden;margin-bottom:16px;min-width:0}
.asc-head{padding:12px 15px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:12px;font-weight:900;color:var(--midnight);line-height:1.4;overflow-wrap:anywhere}
.asc-subhead{font-size:12px;font-weight:800;color:var(--midnight);margin:12px 12px 5px}
.table-wrap{overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;width:100%;max-width:100%;position:relative}
.asc-table{width:100%;min-width:520px;border-collapse:collapse;font-size:11px}
.asc-table th,.asc-table td{padding:8px 10px;border-bottom:1px solid #E2E8F0;text-align:left;white-space:nowrap}
.asc-table th{background:#FAFAFA;color:#64748B;text-transform:uppercase;font-size:9px;letter-spacing:.4px;position:sticky;top:0;z-index:1}
.asc-note{padding:10px 12px;border:1px solid #BFDBFE;background:#EFF6FF;border-radius:9px;color:#1E40AF;font-size:11px;margin-bottom:15px;line-height:1.55;overflow-wrap:anywhere}
.asc-note.warn{border-color:#FDE68A;background:#FFFBEB;color:#92400E}
.asc-snapshot-note{display:flex;flex-wrap:wrap;gap:4px 14px}
.asc-inline-warning{margin:12px}
.asc-basis-note{margin:12px}
.metric{display:flex;align-items:baseline;gap:10px;padding:18px;min-width:0}
.metric strong{font-size:28px;color:var(--midnight);flex:0 0 auto}
.metric span{font-size:11px;color:#64748B;line-height:1.5;overflow-wrap:anywhere}
.gap-list{padding:12px}.gap-list>div{display:flex;flex-direction:column;gap:3px;padding:9px 0;border-bottom:1px solid #E2E8F0;min-width:0}.gap-list>div:last-child{border-bottom:none}.gap-list strong{font-size:11px;color:var(--midnight);overflow-wrap:anywhere}.gap-list span{font-size:10px;color:#64748B;line-height:1.5;overflow-wrap:anywhere}

@media (max-width: 767px){
    .asc-derived-topbar{align-items:stretch;margin-bottom:14px}
    .asc-derived-heading{flex-basis:100%}
    .asc-derived-title{font-size:16px;line-height:1.35}
    .asc-derived-subtitle{font-size:11px}
    .asc-back{width:100%}
    .asc-card{border-radius:11px;margin-bottom:13px}
    .asc-head{padding:11px 12px;font-size:11px}
    .asc-note{font-size:10.5px;padding:10px}
    .asc-snapshot-note{display:grid;grid-template-columns:1fr;gap:5px}
    .metric{align-items:flex-start;flex-direction:column;gap:4px;padding:14px}
    .metric strong{font-size:24px}
    .asc-table{font-size:10.5px;min-width:500px}
    .asc-table th,.asc-table td{padding:8px}
    .asc-inline-warning,.asc-basis-note{margin:10px}
}

@media (max-width: 420px){
    .asc-derived-title{font-size:15px}
    .asc-table{min-width:470px}
    .table-wrap{border-top:1px solid #F1F5F9}
}
</style>
@endpush
@endsection
