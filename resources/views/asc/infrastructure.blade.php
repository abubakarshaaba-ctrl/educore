@extends('layouts.app')
@section('title','ASC — School Infrastructure')
@section('page-title','Annual School Census — Infrastructure & Profile')

@section('content')
@if(session('success'))
<div style="background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;font-weight:600">
    ✓ {{ session('success') }}
</div>
@endif

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
        <div style="font-size:18px;font-weight:800;color:var(--midnight)">School Census Data</div>
        <div style="font-size:12px;color:var(--slate-light);margin-top:2px">Fill in physical infrastructure and school profile for the census year</div>
    </div>
    <a href="{{ route('asc.report', ['year' => $year]) }}"
       style="display:inline-flex;align-items:center;gap:6px;background:var(--indigo);color:white;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none">
        📊 View ASC Report
    </a>
</div>

<form method="POST" action="{{ route('asc.infrastructure.save') }}">
@csrf

{{-- Year selector --}}
<div style="background:white;border:1px solid var(--border);border-radius:14px;padding:20px;margin-bottom:20px">
    <label style="font-size:12px;font-weight:700;color:var(--slate);display:block;margin-bottom:6px">CENSUS YEAR</label>
    <select name="census_year" onchange="this.form.submit()"
            style="border:1px solid var(--border);border-radius:8px;padding:9px 14px;font-size:14px;font-weight:700;color:var(--midnight);width:200px">
        @for($y = now()->year; $y >= 2020; $y--)
        <option value="{{ $y }}" @selected($y == $year)>{{ $y }}/{{ $y+1 }}</option>
        @endfor
    </select>
</div>

{{-- ── Section A: School Identity ── --}}
<div style="background:white;border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px">
    <div style="background:#F8FAFC;padding:14px 20px;border-bottom:1px solid var(--border)">
        <div style="font-size:13px;font-weight:800;color:var(--midnight)">Section A — School Identification</div>
    </div>
    <div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px">

        <div>
            <label class="flabel">State</label>
            <input type="text" name="school_state" value="{{ old('school_state', $infra?->school_state) }}"
                   class="finput" placeholder="e.g. Lagos">
        </div>
        <div>
            <label class="flabel">LGA</label>
            <input type="text" name="school_lga" value="{{ old('school_lga', $infra?->school_lga) }}"
                   class="finput" placeholder="e.g. Kosofe">
        </div>
        <div>
            <label class="flabel">Senatorial District</label>
            <input type="text" name="school_senatorial_district" value="{{ old('school_senatorial_district', $infra?->school_senatorial_district) }}"
                   class="finput" placeholder="e.g. Lagos East">
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
        <div style="font-size:13px;font-weight:800;color:var(--midnight)">Section B — Head Teacher / Principal</div>
    </div>
    <div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px">
        <div>
            <label class="flabel">Full Name</label>
            <input type="text" name="head_teacher_name" value="{{ old('head_teacher_name',$infra?->head_teacher_name) }}"
                   class="finput" placeholder="Principal's full name">
        </div>
        <div>
            <label class="flabel">Gender</label>
            <select name="head_teacher_gender" class="finput">
                <option value="">— Select —</option>
                <option value="male"   @selected(old('head_teacher_gender',$infra?->head_teacher_gender)==='male')>Male</option>
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
<div style="background:white;border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px">
    <div style="background:#F8FAFC;padding:14px 20px;border-bottom:1px solid var(--border)">
        <div style="font-size:13px;font-weight:800;color:var(--midnight)">Section C — Classrooms</div>
    </div>
    <div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px">
        @foreach([
            'classrooms_permanent'      => 'Permanent Classrooms',
            'classrooms_temporary'      => 'Temporary Classrooms',
            'classrooms_good_condition' => 'In Good Condition',
            'classrooms_bad_condition'  => 'Need Repair / Bad Condition',
        ] as $field => $label)
        <div>
            <label class="flabel">{{ $label }}</label>
            <input type="number" name="{{ $field }}" min="0"
                   value="{{ old($field, $infra?->{$field} ?? 0) }}" class="finput">
        </div>
        @endforeach
    </div>
</div>

{{-- ── Section D: Toilets ── --}}
<div style="background:white;border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px">
    <div style="background:#F8FAFC;padding:14px 20px;border-bottom:1px solid var(--border)">
        <div style="font-size:13px;font-weight:800;color:var(--midnight)">Section D — Toilet / Latrine Facilities</div>
    </div>
    <div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px">
        @foreach([
            'toilets_male_pupils'   => 'Male Pupils',
            'toilets_female_pupils' => 'Female Pupils',
            'toilets_male_staff'    => 'Male Staff',
            'toilets_female_staff'  => 'Female Staff',
        ] as $field => $label)
        <div>
            <label class="flabel">{{ $label }}</label>
            <input type="number" name="{{ $field }}" min="0"
                   value="{{ old($field, $infra?->{$field} ?? 0) }}" class="finput">
        </div>
        @endforeach
    </div>
</div>

{{-- ── Section E: Utilities ── --}}
<div style="background:white;border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px">
    <div style="background:#F8FAFC;padding:14px 20px;border-bottom:1px solid var(--border)">
        <div style="font-size:13px;font-weight:800;color:var(--midnight)">Section E — Water, Electricity & Fence</div>
    </div>
    <div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px">
        <div>
            <label class="flabel">Water Source</label>
            <select name="water_source" class="finput">
                <option value="">— Select —</option>
                @foreach(['pipe'=>'Pipe-borne / Tap','borehole'=>'Borehole','well'=>'Well','river'=>'River / Stream','none'=>'None'] as $v=>$l)
                <option value="{{ $v }}" @selected(old('water_source',$infra?->water_source)===$v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="flabel">Electricity Source</label>
            <select name="electricity_source" class="finput">
                <option value="">— Select —</option>
                @foreach(['nepa'=>'NEPA / PHCN Grid','generator'=>'Generator','solar'=>'Solar','none'=>'None'] as $v=>$l)
                <option value="{{ $v }}" @selected(old('electricity_source',$infra?->electricity_source)===$v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="flabel">Perimeter Fence</label>
            <select name="fence_type" class="finput">
                <option value="">— Select —</option>
                @foreach(['full'=>'Full Fence','partial'=>'Partial Fence','none'=>'No Fence'] as $v=>$l)
                <option value="{{ $v }}" @selected(old('fence_type',$infra?->fence_type)===$v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- ── Section F: Other Facilities ── --}}
<div style="background:white;border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:24px">
    <div style="background:#F8FAFC;padding:14px 20px;border-bottom:1px solid var(--border)">
        <div style="font-size:13px;font-weight:800;color:var(--midnight)">Section F — Other Facilities</div>
    </div>
    <div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
        @foreach([
            'has_library'         => 'Library',
            'has_science_lab'     => 'Science Laboratory',
            'has_computer_lab'    => 'Computer Laboratory',
            'has_sports_facility' => 'Sports / Play Ground',
            'has_first_aid'       => 'First Aid / Sick Bay',
        ] as $field => $label)
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px;border:1px solid var(--border);border-radius:10px">
            <input type="hidden"   name="{{ $field }}" value="0">
            <input type="checkbox" name="{{ $field }}" value="1"
                   style="width:18px;height:18px;accent-color:var(--indigo)"
                   @checked(old($field, $infra?->{$field}))>
            <span style="font-size:13px;font-weight:600;color:var(--midnight)">{{ $label }}</span>
        </label>
        @endforeach

        <div>
            <label class="flabel">No. of Computers</label>
            <input type="number" name="computer_count" min="0"
                   value="{{ old('computer_count', $infra?->computer_count ?? 0) }}" class="finput">
        </div>
    </div>
</div>

<div style="display:flex;justify-content:flex-end">
    <button type="submit" style="background:var(--indigo);color:white;border:none;padding:11px 28px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer">
        💾 Save Infrastructure Data
    </button>
</div>

</form>

@push('styles')
<style>
.flabel{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px}
.finput{width:100%;border:1px solid var(--border);border-radius:8px;padding:9px 12px;font-size:13px;font-family:inherit;color:var(--midnight);box-sizing:border-box}
.finput:focus{outline:none;border-color:var(--indigo);box-shadow:0 0 0 3px rgba(99,102,241,0.1)}
</style>
@endpush
@endsection
