@extends('layouts.app')
@section('title','ASC — Census Workspace')
@section('page-title','Annual School Census — Data Workspace')

@section('content')
@if(session('success'))
<div style="background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;font-weight:600">
    ✓ {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;font-weight:600">
    ⚠ {{ session('error') }}
</div>
@endif
@if($errors->any())
<div style="background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px">
    <strong>Please correct the following:</strong>
    <ul style="margin:7px 0 0 18px">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
        <div style="font-size:18px;font-weight:800;color:var(--midnight)">School Census Data Workspace</div>
        <div style="font-size:12px;color:var(--slate-light);margin-top:2px">Enter census-only infrastructure once; synchronize enrolment, age, staff and school data from EduCore.</div>
    </div>
    <a href="{{ route('asc.report', ['year' => $year]) }}"
       style="display:inline-flex;align-items:center;gap:6px;background:var(--indigo);color:white;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none">
        📊 View ASC Report
    </a>
</div>

{{-- Census year selector uses GET so changing year never saves a partially filled form. --}}
<form method="GET" action="{{ route('asc.infrastructure') }}" style="background:white;border:1px solid var(--border);border-radius:14px;padding:16px 20px;margin-bottom:16px;display:flex;align-items:end;gap:12px;flex-wrap:wrap">
    <div>
        <label class="flabel">Census Year</label>
        <select name="year" class="finput" style="width:190px">
            @for($y = now()->year; $y >= 2020; $y--)
            <option value="{{ $y }}" @selected($y == $year)>{{ $y }}/{{ $y+1 }}</option>
            @endfor
        </select>
    </div>
    <button type="submit" class="btn-secondary">Load Year</button>
</form>

@include('asc._official_sections')

{{-- ── Synchronization / readiness workspace ── --}}
@php
    $status = $ascReturn?->status ?? 'not synchronized';
    $score = $ascReturn?->completeness['score'] ?? 0;
    $issues = $ascReturn?->completeness['issues'] ?? [];
    $blocking = $ascReturn?->completeness['blocking_issues'] ?? [];
    $ready = (bool) ($ascReturn?->completeness['ready_to_finalize'] ?? false);
    $recon = $ascReturn?->reconciliation ?? [];
    $auto = $ascReturn?->auto_data ?? [];
    $locked = $ascReturn?->isLocked() ?? false;
@endphp
<div style="background:white;border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <div>
            <div style="font-size:13px;font-weight:800;color:var(--midnight)">EduCore Synchronization</div>
            <div style="font-size:12px;color:var(--slate-light);margin-top:3px">AUTO / DERIVED values are read from the school's existing operational records.</div>
        </div>
        <span style="padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $locked ? '#DBEAFE' : ($ascReturn ? '#FEF3C7' : '#F1F5F9') }};color:{{ $locked ? '#1D4ED8' : ($ascReturn ? '#92400E' : '#475569') }}">
            {{ $status }}
        </span>
    </div>

    <div style="padding:18px 20px">
        <div class="sync-grid">
            <div class="metric-card">
                <span>Data Completeness</span>
                <strong>{{ $score }}%</strong>
                <small>{{ $ascReturn ? (($ascReturn->completeness['complete_checks'] ?? 0).' / '.($ascReturn->completeness['total_checks'] ?? 0).' checks complete') : 'Synchronize to calculate' }}</small>
            </div>
            <div class="metric-card">
                <span>Students</span>
                <strong>{{ data_get($auto, 'enrolment.total', '—') }}</strong>
                <small>Current enrolments</small>
            </div>
            <div class="metric-card">
                <span>Staff</span>
                <strong>{{ data_get($auto, 'staff.total', '—') }}</strong>
                <small>Active census staff roles</small>
            </div>
            <div class="metric-card">
                <span>Reconciliation</span>
                <strong>{{ !empty($recon) ? (($recon['balanced'] ?? false) ? 'Balanced' : 'Review') : '—' }}</strong>
                <small>{{ !empty($recon) ? (($recon['duplicate_current_enrollments'] ?? 0).' duplicate current enrolment(s)') : 'Synchronize to check' }}</small>
            </div>
        </div>

        @if($ascReturn)
        <div style="margin-top:14px;padding:12px 14px;background:#F8FAFC;border:1px solid var(--border);border-radius:10px;font-size:12px;color:var(--slate)">
            <strong>Reference date:</strong> {{ optional($ascReturn->reference_date)->format('d M Y') }}
            &nbsp;•&nbsp; <strong>Last synchronized:</strong> {{ optional($ascReturn->synchronized_at)->format('d M Y, h:i A') ?? '—' }}
            @if($ascReturn->finalized_at)
                &nbsp;•&nbsp; <strong>Finalized:</strong> {{ $ascReturn->finalized_at->format('d M Y, h:i A') }}
            @endif
        </div>
        @endif

        @if(count($issues))
        <div style="margin-top:14px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:12px 14px">
            <div style="font-size:12px;font-weight:800;color:#9A3412;margin-bottom:6px">Data issues to resolve</div>
            <ul style="margin:0 0 0 18px;padding:0;color:#7C2D12;font-size:12px;line-height:1.7">
                @foreach($issues as $issue)<li>{{ $issue }}</li>@endforeach
            </ul>
        </div>
        @endif

        @if(!$locked)
        <div style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-top:16px">
            <form method="POST" action="{{ route('asc.infrastructure.save') }}" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin:0">
                @csrf
                <input type="hidden" name="_asc_action" value="sync">
                <input type="hidden" name="census_year" value="{{ $year }}">
                <div>
                    <label class="flabel">Census Reference Date</label>
                    <input type="date" name="reference_date" required class="finput" style="width:190px"
                           value="{{ old('reference_date', $ascReturn?->reference_date?->format('Y-m-d') ?? now()->toDateString()) }}">
                </div>
                <button type="submit" class="btn-sync">↻ {{ $ascReturn ? 'Refresh from EduCore' : 'Synchronize from EduCore' }}</button>
            </form>

            @if($ascReturn)
            <form method="POST" action="{{ route('asc.infrastructure.save') }}" style="margin:0" onsubmit="return confirm('Finalize and lock this census snapshot? Auto-synchronized values will no longer change for this return.')">
                @csrf
                <input type="hidden" name="_asc_action" value="finalize">
                <input type="hidden" name="census_year" value="{{ $year }}">
                <button type="submit" class="btn-finalize" @disabled(!$ready) title="{{ !$ready ? 'Resolve blocking issues and synchronize again first' : 'Lock this historical census snapshot' }}">
                    🔒 Finalize Census Snapshot
                </button>
            </form>
            @endif
        </div>
        @else
        <div style="margin-top:16px;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:12px 14px;font-size:12px;color:#1E40AF">
            <strong>Historical snapshot locked.</strong> Changes made later to students, staff or infrastructure will not alter this finalized census return.
        </div>
        @endif
    </div>
</div>

<form method="POST" action="{{ route('asc.infrastructure.save') }}">
@csrf
<input type="hidden" name="_asc_action" value="save_infrastructure">
<input type="hidden" name="census_year" value="{{ $year }}">

{{-- ── Section A: School Identity ── --}}
<div style="background:white;border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px">
    <div style="background:#F8FAFC;padding:14px 20px;border-bottom:1px solid var(--border)">
        <div style="font-size:13px;font-weight:800;color:var(--midnight)">Section A — School Identification <span class="source-badge profile">PROFILE</span></div>
    </div>
    <div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px">
        <div>
            <label class="flabel">State</label>
            <input type="text" name="school_state" value="{{ old('school_state', $infra?->school_state) }}" class="finput" placeholder="e.g. Nasarawa">
        </div>
        <div>
            <label class="flabel">LGA</label>
            <input type="text" name="school_lga" value="{{ old('school_lga', $infra?->school_lga) }}" class="finput" placeholder="e.g. Karu">
        </div>
        <div>
            <label class="flabel">Senatorial District</label>
            <input type="text" name="school_senatorial_district" value="{{ old('school_senatorial_district', $infra?->school_senatorial_district) }}" class="finput">
        </div>
        <div>
            <label class="flabel">School Ownership</label>
            <select name="school_ownership" class="finput">
                <option value="">— Select —</option>
                @foreach(['federal'=>'Federal Government','state'=>'State Government','lga'=>'Local Government (LGA)','private'=>'Private','mission'=>'Mission / Religious','community'=>'Community'] as $v=>$l)
                <option value="{{ $v }}" @selected(old('school_ownership',$infra?->school_ownership)===$v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="flabel">School Type</label>
            <select name="school_type" class="finput">
                <option value="">— Select —</option>
                @foreach(['day'=>'Day School','boarding'=>'Boarding','mixed'=>'Day & Boarding (Mixed)'] as $v=>$l)
                <option value="{{ $v }}" @selected(old('school_type',$infra?->school_type)===$v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- ── Section B: Head Teacher ── --}}
<div style="background:white;border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px">
    <div style="background:#F8FAFC;padding:14px 20px;border-bottom:1px solid var(--border)">
        <div style="font-size:13px;font-weight:800;color:var(--midnight)">Section B — Head Teacher / Principal <span class="source-badge manual">MANUAL</span></div>
    </div>
    <div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px">
        <div>
            <label class="flabel">Full Name</label>
            <input type="text" name="head_teacher_name" value="{{ old('head_teacher_name',$infra?->head_teacher_name) }}" class="finput" placeholder="Principal's full name">
        </div>
        <div>
            <label class="flabel">Gender</label>
            <select name="head_teacher_gender" class="finput">
                <option value="">— Select —</option>
                <option value="male" @selected(old('head_teacher_gender',$infra?->head_teacher_gender)==='male')>Male</option>
                <option value="female" @selected(old('head_teacher_gender',$infra?->head_teacher_gender)==='female')>Female</option>
            </select>
        </div>
        <div>
            <label class="flabel">Qualification</label>
            <select name="head_teacher_qualification" class="finput">
                <option value="">— Select —</option>
                @foreach(['phd'=>'PhD','masters'=>'Masters','pgde'=>'PGDE','bed'=>'B.Ed','bsc'=>'B.Sc / B.A','hnd'=>'HND','nce'=>'NCE','nd'=>'ND / OND','ssce'=>'SSCE','other'=>'Other'] as $v=>$l)
                <option value="{{ $v }}" @selected(old('head_teacher_qualification',$infra?->head_teacher_qualification)===$v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- ── Section C: Classrooms ── --}}
<div class="asc-section">
    <div class="section-head"><div>Section C — Classrooms <span class="source-badge manual">MANUAL</span></div></div>
    <div class="section-grid small">
        @foreach([
            'classrooms_permanent' => 'Permanent Classrooms',
            'classrooms_temporary' => 'Temporary Classrooms',
            'classrooms_good_condition' => 'In Good Condition',
            'classrooms_bad_condition' => 'Need Repair / Bad Condition',
        ] as $field => $label)
        <div><label class="flabel">{{ $label }}</label><input type="number" name="{{ $field }}" min="0" value="{{ old($field, $infra?->{$field} ?? 0) }}" class="finput"></div>
        @endforeach
    </div>
</div>

{{-- ── Section D: Toilets ── --}}
<div class="asc-section">
    <div class="section-head"><div>Section D — Toilet / Latrine Facilities <span class="source-badge manual">MANUAL</span></div></div>
    <div class="section-grid small">
        @foreach([
            'toilets_male_pupils' => 'Male Pupils',
            'toilets_female_pupils' => 'Female Pupils',
            'toilets_male_staff' => 'Male Staff',
            'toilets_female_staff' => 'Female Staff',
        ] as $field => $label)
        <div><label class="flabel">{{ $label }}</label><input type="number" name="{{ $field }}" min="0" value="{{ old($field, $infra?->{$field} ?? 0) }}" class="finput"></div>
        @endforeach
    </div>
</div>

{{-- ── Section E: Utilities ── --}}
<div class="asc-section">
    <div class="section-head"><div>Section E — Water, Electricity & Fence <span class="source-badge profile">PROFILE</span></div></div>
    <div class="section-grid">
        <div>
            <label class="flabel">Water Source</label>
            <select name="water_source" class="finput"><option value="">— Select —</option>
                @foreach(['pipe'=>'Pipe-borne / Tap','borehole'=>'Borehole','well'=>'Well','river'=>'River / Stream','none'=>'None'] as $v=>$l)<option value="{{ $v }}" @selected(old('water_source',$infra?->water_source)===$v)>{{ $l }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="flabel">Electricity Source</label>
            <select name="electricity_source" class="finput"><option value="">— Select —</option>
                @foreach(['nepa'=>'NEPA / PHCN Grid','generator'=>'Generator','solar'=>'Solar','none'=>'None'] as $v=>$l)<option value="{{ $v }}" @selected(old('electricity_source',$infra?->electricity_source)===$v)>{{ $l }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="flabel">Perimeter Fence</label>
            <select name="fence_type" class="finput"><option value="">— Select —</option>
                @foreach(['full'=>'Full Fence','partial'=>'Partial Fence','none'=>'No Fence'] as $v=>$l)<option value="{{ $v }}" @selected(old('fence_type',$infra?->fence_type)===$v)>{{ $l }}</option>@endforeach
            </select>
        </div>
    </div>
</div>

{{-- ── Section F: Other Facilities ── --}}
<div class="asc-section" style="margin-bottom:24px">
    <div class="section-head"><div>Section F — Other Facilities <span class="source-badge manual">MANUAL</span></div></div>
    <div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
        @foreach([
            'has_library' => 'Library',
            'has_science_lab' => 'Science Laboratory',
            'has_computer_lab' => 'Computer Laboratory',
            'has_sports_facility' => 'Sports / Play Ground',
            'has_first_aid' => 'First Aid / Sick Bay',
        ] as $field => $label)
        <label class="check-card">
            <input type="hidden" name="{{ $field }}" value="0">
            <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $infra?->{$field}))>
            <span>{{ $label }}</span>
        </label>
        @endforeach
        <div><label class="flabel">No. of Computers</label><input type="number" name="computer_count" min="0" value="{{ old('computer_count', $infra?->computer_count ?? 0) }}" class="finput"></div>
    </div>
</div>

<div style="display:flex;justify-content:flex-end">
    <button type="submit" class="btn-sync">💾 Save Infrastructure & Profile</button>
</div>
</form>

@push('styles')
<style>
.flabel{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px}
.finput{width:100%;border:1px solid var(--border);border-radius:8px;padding:9px 12px;font-size:13px;font-family:inherit;color:var(--midnight);box-sizing:border-box;background:#fff}
.finput:focus{outline:none;border-color:var(--indigo);box-shadow:0 0 0 3px rgba(99,102,241,.1)}
.sync-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px}
.metric-card{border:1px solid var(--border);border-radius:11px;padding:13px 14px;background:#FAFCFF;display:flex;flex-direction:column;gap:3px}
.metric-card span{font-size:10px;text-transform:uppercase;letter-spacing:.5px;font-weight:800;color:var(--slate-light)}
.metric-card strong{font-size:20px;color:var(--midnight)}
.metric-card small{font-size:11px;color:var(--slate-light)}
.btn-sync,.btn-secondary,.btn-finalize{border:none;padding:10px 17px;border-radius:9px;font-size:12px;font-weight:800;cursor:pointer;font-family:inherit}
.btn-sync{background:var(--indigo);color:#fff}.btn-secondary{background:#F1F5F9;color:var(--midnight);border:1px solid var(--border)}
.btn-finalize{background:#0F766E;color:#fff}.btn-finalize:disabled{opacity:.45;cursor:not-allowed}
.source-badge{font-size:9px;font-weight:800;border-radius:999px;padding:3px 7px;margin-left:6px;vertical-align:1px}.source-badge.profile{background:#E0E7FF;color:#3730A3}.source-badge.manual{background:#FEF3C7;color:#92400E}
.asc-section{background:#fff;border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px}.section-head{background:#F8FAFC;padding:14px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:800;color:var(--midnight)}
.section-grid{padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}.section-grid.small{grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}
.check-card{display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px;border:1px solid var(--border);border-radius:10px;font-size:13px;font-weight:600;color:var(--midnight)}.check-card input[type=checkbox]{width:18px;height:18px;accent-color:var(--indigo)}
</style>
@endpush
@endsection