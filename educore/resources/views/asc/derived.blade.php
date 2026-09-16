@extends('layouts.app')
@section('title', 'ASC — Derived Section ' . strtoupper($section))
@section('page-title', 'Annual School Census — Section ' . strtoupper($section))

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px">
    <div>
        <div style="font-size:18px;font-weight:900;color:var(--midnight)">Section {{ strtoupper($section) }} — {{ $section === 'c' ? 'Enrolment' : 'Teachers Qualification' }}</div>
        <div style="font-size:12px;color:var(--slate-light);margin-top:3px">AUTO / DERIVED from EduCore operational records. Values update when the census is synchronized.</div>
    </div>
    <a href="{{ route('asc.infrastructure',['year'=>$year]) }}" class="asc-back">← Census Workspace</a>
</div>

@if(!$ascReturn)
<div class="asc-note warn"><strong>No synchronized snapshot exists for {{ $year }}/{{ $year+1 }}.</strong> Return to the Census Workspace and synchronize from EduCore first.</div>
@elseif(empty($data))
<div class="asc-note warn"><strong>Derived data is not present in this older snapshot.</strong> Refresh the census from EduCore to generate Section {{ strtoupper($section) }}.</div>
@else
<div class="asc-note">
    <strong>Snapshot:</strong> {{ ucfirst($ascReturn->status) }}
    &nbsp;•&nbsp; <strong>Reference date:</strong> {{ optional($ascReturn->reference_date)->format('d M Y') }}
    &nbsp;•&nbsp; <strong>Last synchronized:</strong> {{ optional($ascReturn->synchronized_at)->format('d M Y, h:i A') }}
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
        <div class="asc-note warn" style="margin:12px 0 0">EduCore can verify that a certificate file exists, but it does not currently record whether the issuing authority was the National Population Commission or another body.</div>
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
            <div style="font-size:12px;font-weight:800;color:var(--midnight);margin:12px 0 5px">{{ $label }}</div>
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
        <div class="asc-note" style="margin:0 0 12px">{{ data_get($data,'basis') }}</div>
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
.asc-back{background:#F1F5F9;border:1px solid var(--border);color:var(--midnight);padding:9px 14px;border-radius:9px;text-decoration:none;font-size:12px;font-weight:800}.asc-card{background:#fff;border:1px solid var(--border);border-radius:13px;overflow:hidden;margin-bottom:16px}.asc-head{padding:12px 15px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:12px;font-weight:900;color:var(--midnight)}.table-wrap{overflow:auto}.asc-table{width:100%;border-collapse:collapse;font-size:11px}.asc-table th,.asc-table td{padding:8px 10px;border-bottom:1px solid #E2E8F0;text-align:left;white-space:nowrap}.asc-table th{background:#FAFAFA;color:#64748B;text-transform:uppercase;font-size:9px;letter-spacing:.4px}.asc-note{padding:10px 12px;border:1px solid #BFDBFE;background:#EFF6FF;border-radius:9px;color:#1E40AF;font-size:11px;margin-bottom:15px}.asc-note.warn{border-color:#FDE68A;background:#FFFBEB;color:#92400E}.metric{display:flex;align-items:baseline;gap:10px;padding:18px}.metric strong{font-size:28px;color:var(--midnight)}.metric span{font-size:11px;color:#64748B}.gap-list{padding:12px}.gap-list>div{display:flex;flex-direction:column;gap:3px;padding:9px 0;border-bottom:1px solid #E2E8F0}.gap-list>div:last-child{border-bottom:none}.gap-list strong{font-size:11px;color:var(--midnight)}.gap-list span{font-size:10px;color:#64748B}
</style>
@endpush
@endsection
