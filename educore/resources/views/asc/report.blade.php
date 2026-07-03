@extends('layouts.app')
@section('title','ASC Report ' . $year)
@section('page-title','Annual School Census Report — ' . $year . '/' . ($year+1))

@push('styles')
<style>
@media print {
    .no-print { display:none !important; }
    body { background:white !important; }
    .asc-page { box-shadow:none !important; }
}
.asc-page { background:white; border:1px solid var(--border); border-radius:14px; padding:32px; margin-bottom:20px; }
.asc-title { font-size:20px; font-weight:900; color:var(--midnight); text-align:center; text-transform:uppercase; letter-spacing:1px; }
.asc-sub   { font-size:12px; color:var(--slate-light); text-align:center; margin-top:4px; }
.section-header { background:var(--midnight); color:white; padding:8px 14px; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.8px; border-radius:6px; margin:20px 0 12px; }
table.asc-table { width:100%; border-collapse:collapse; font-size:12px; }
table.asc-table th { background:#F1F5F9; color:var(--slate); font-weight:700; padding:8px 12px; text-align:left; border:1px solid #E2E8F0; font-size:11px; text-transform:uppercase; }
table.asc-table td { padding:8px 12px; border:1px solid #E2E8F0; color:var(--midnight); }
table.asc-table tr:nth-child(even) td { background:#FAFAFA; }
table.asc-table .num { text-align:center; font-weight:700; }
table.asc-table tfoot td { background:#EEF2FF; font-weight:800; color:var(--indigo); }
.kv-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap:12px; }
.kv-item { border:1px solid var(--border); border-radius:8px; padding:10px 14px; }
.kv-label { font-size:10px; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.5px; }
.kv-value { font-size:14px; font-weight:800; color:var(--midnight); margin-top:3px; }
.badge { display:inline-flex;align-items:center;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700 }
.badge-green { background:#D1FAE5;color:#065F46 }
.badge-blue  { background:#DBEAFE;color:#1E40AF }
.badge-amber { background:#FEF3C7;color:#92400E }
.badge-red   { background:#FEE2E2;color:#991B1B }
</style>
@endpush

@section('content')
{{-- Toolbar --}}
<div class="no-print" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div style="display:flex;gap:8px;align-items:center">
        <a href="{{ route('asc.infrastructure', ['year'=>$year]) }}"
           style="display:inline-flex;align-items:center;gap:5px;background:#F1F5F9;color:var(--slate);padding:8px 16px;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none">
            ✏️ Edit Infrastructure Data
        </a>

        <form method="GET" style="display:flex;align-items:center;gap:6px">
            <select name="year" onchange="this.form.submit()"
                    style="border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-weight:700">
                @for($y = now()->year; $y >= 2020; $y--)
                <option value="{{ $y }}" @selected($y==$year)>{{ $y }}/{{ $y+1 }}</option>
                @endfor
            </select>
        </form>
    </div>
    <button onclick="window.print()"
            style="background:var(--indigo);color:white;border:none;padding:9px 20px;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer">
        🖨️ Print / Save PDF
    </button>
</div>

<div class="asc-page">
    {{-- Header --}}
    <div style="text-align:center;margin-bottom:24px;border-bottom:2px solid var(--midnight);padding-bottom:16px">
        @if($tenant->logo_path)
        <img src="{{ asset('storage/'.$tenant->logo_path) }}" style="height:64px;margin-bottom:8px;display:block;margin:0 auto 8px">
        @endif
        <div class="asc-title">Federal Republic of Nigeria</div>
        <div class="asc-title" style="font-size:14px;margin-top:2px">Annual School Census (ASC)</div>
        <div class="asc-sub" style="font-size:13px;font-weight:700;color:var(--midnight);margin-top:4px">
            {{ $tenant->name }} — {{ $year }}/{{ $year+1 }} Academic Year
        </div>
        <div class="asc-sub">Federal Ministry of Education — Education Management Information System (EMIS)</div>
    </div>

    {{-- ── Section 1: School ID ── --}}
    <div class="section-header">Section 1 — School Identification</div>
    <div class="kv-grid">
        <div class="kv-item">
            <div class="kv-label">School Name</div>
            <div class="kv-value">{{ $tenant->name }}</div>
        </div>
        <div class="kv-item">
            <div class="kv-label">State</div>
            <div class="kv-value">{{ $infra?->school_state ?? '—' }}</div>
        </div>
        <div class="kv-item">
            <div class="kv-label">LGA</div>
            <div class="kv-value">{{ $infra?->school_lga ?? '—' }}</div>
        </div>
        <div class="kv-item">
            <div class="kv-label">Senatorial District</div>
            <div class="kv-value">{{ $infra?->school_senatorial_district ?? '—' }}</div>
        </div>
        <div class="kv-item">
            <div class="kv-label">Address</div>
            <div class="kv-value" style="font-size:12px">{{ $tenant->address ?? '—' }}</div>
        </div>
        <div class="kv-item">
            <div class="kv-label">Phone</div>
            <div class="kv-value">{{ $tenant->phone ?? '—' }}</div>
        </div>
        <div class="kv-item">
            <div class="kv-label">Email</div>
            <div class="kv-value" style="font-size:12px">{{ $tenant->email ?? '—' }}</div>
        </div>
        <div class="kv-item">
            <div class="kv-label">Ownership</div>
            <div class="kv-value">{{ ucwords(str_replace('_',' ',$infra?->school_ownership ?? '—')) }}</div>
        </div>
        <div class="kv-item">
            <div class="kv-label">School Type</div>
            <div class="kv-value">{{ ucwords($infra?->school_type ?? '—') }}</div>
        </div>
        <div class="kv-item">
            <div class="kv-label">Head Teacher</div>
            <div class="kv-value" style="font-size:12px">{{ $infra?->head_teacher_name ?? '—' }}</div>
        </div>
        <div class="kv-item">
            <div class="kv-label">HT Qualification</div>
            <div class="kv-value">{{ strtoupper($infra?->head_teacher_qualification ?? '—') }}</div>
        </div>
        <div class="kv-item">
            <div class="kv-label">HT Gender</div>
            <div class="kv-value">{{ ucfirst($infra?->head_teacher_gender ?? '—') }}</div>
        </div>
    </div>

    {{-- ── Section 2: Enrollment ── --}}
    <div class="section-header">Section 2 — Student Enrollment by Class & Gender</div>
    @php
        $sections = [
            'creche'            => 'Crèche',
            'nursery'           => 'Pre-Primary (Nursery)',
            'primary'           => 'Primary',
            'junior_secondary'  => 'Junior Secondary (JSS)',
            'senior_secondary'  => 'Senior Secondary (SSS)',
        ];
        $grandMale = $grandFemale = $grandTotal = $grandSN = 0;
    @endphp

    @foreach($sections as $sectionKey => $sectionLabel)
    @php
        $rows = $enrollments->where('section', $sectionKey);
        if($rows->isEmpty()) continue;
        $levelGroups = $rows->groupBy('level_name');
    @endphp
    <div style="margin-bottom:16px">
        <div style="font-size:11px;font-weight:800;color:var(--indigo);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;padding:4px 0;border-bottom:1px solid #E2E8F0">
            {{ $sectionLabel }}
        </div>
        <table class="asc-table">
            <thead>
                <tr>
                    <th>Class / Level</th>
                    <th class="num">Male</th>
                    <th class="num">Female</th>
                    <th class="num">Total</th>
                    <th class="num">Special Needs</th>
                </tr>
            </thead>
            <tbody>
            @php $secMale = $secFemale = $secSN = 0; @endphp
            @foreach($levelGroups as $levelName => $levelRows)
            @php
                $male   = $levelRows->where('gender','male')->sum('count');
                $female = $levelRows->where('gender','female')->sum('count');
                $sn     = $levelRows->where('has_special_needs',1)->sum('count');
                $total  = $male + $female;
                $secMale   += $male;
                $secFemale += $female;
                $secSN     += $sn;
                $grandMale   += $male;
                $grandFemale += $female;
                $grandSN     += $sn;
                $grandTotal  += $total;
            @endphp
            <tr>
                <td>{{ $levelName }}</td>
                <td class="num">{{ $male }}</td>
                <td class="num">{{ $female }}</td>
                <td class="num" style="font-weight:700">{{ $total }}</td>
                <td class="num">{{ $sn ?: '—' }}</td>
            </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>{{ $sectionLabel }} Sub-total</strong></td>
                    <td class="num">{{ $secMale }}</td>
                    <td class="num">{{ $secFemale }}</td>
                    <td class="num">{{ $secMale + $secFemale }}</td>
                    <td class="num">{{ $secSN ?: '—' }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endforeach

    {{-- Grand Total --}}
    <table class="asc-table" style="margin-top:8px">
        <tfoot>
            <tr style="background:#071E45 !important">
                <td style="color:white;font-weight:800;font-size:13px">GRAND TOTAL — ALL STUDENTS</td>
                <td class="num" style="color:white;font-weight:800">{{ $grandMale }}</td>
                <td class="num" style="color:white;font-weight:800">{{ $grandFemale }}</td>
                <td class="num" style="color:white;font-weight:800">{{ $grandTotal }}</td>
                <td class="num" style="color:white;font-weight:800">{{ $grandSN ?: '—' }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ── Section 3: Staff ── --}}
    <div class="section-header">Section 3 — Teaching Staff by Qualification</div>
    @php
        $qualLabels = [
            'phd'     => 'PhD / Doctorate',
            'masters' => 'Masters Degree',
            'pgde'    => 'PGDE',
            'bed'     => 'B.Ed',
            'bsc'     => 'B.Sc / B.A',
            'hnd'     => 'HND',
            'nce'     => 'NCE',
            'nd'      => 'ND / OND',
            'ssce'    => 'SSCE / WAEC',
            'other'   => 'Other',
            ''        => 'Not Specified',
        ];
        $teachers    = $allStaff->whereIn('role', $teachingRoles);
        $management  = $allStaff->whereIn('role', $managementRoles);
        $nonTeaching = $allStaff->whereIn('role', $nonTeachingRoles);
    @endphp

    <table class="asc-table">
        <thead>
            <tr>
                <th>Qualification</th>
                <th class="num">Teaching Staff</th>
                <th class="num">Management</th>
                <th class="num">Non-Teaching</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
        @foreach($qualLabels as $qualKey => $qualLabel)
        @php
            $t  = $teachers->where('qualification', $qualKey ?: null)->count()
                + $teachers->where('qualification', '')->count() * ($qualKey === '' ? 1 : 0);
            $m  = $management->where('qualification', $qualKey ?: null)->count()
                + $management->where('qualification', '')->count() * ($qualKey === '' ? 1 : 0);
            $nt = $nonTeaching->where('qualification', $qualKey ?: null)->count()
                + $nonTeaching->where('qualification', '')->count() * ($qualKey === '' ? 1 : 0);
            if($t + $m + $nt === 0) continue;
        @endphp
        <tr>
            <td>{{ $qualLabel }}</td>
            <td class="num">{{ $t ?: '—' }}</td>
            <td class="num">{{ $m ?: '—' }}</td>
            <td class="num">{{ $nt ?: '—' }}</td>
            <td class="num" style="font-weight:700">{{ $t+$m+$nt }}</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td><strong>TOTAL</strong></td>
                <td class="num">{{ $teachers->count() }}</td>
                <td class="num">{{ $management->count() }}</td>
                <td class="num">{{ $nonTeaching->count() }}</td>
                <td class="num">{{ $allStaff->count() }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Staff by role --}}
    <div class="section-header">Section 4 — Staff by Role / Category</div>
    @php
        $roleLabels = [
            'principal'              => 'Principal',
            'head'                   => 'Head Teacher',
            'head_teacher'           => 'Head Teacher',
            'vice_principal'         => 'Vice Principal',
            'academic_administrator' => 'Academic Administrator',
            'admin'                  => 'Administrator',
            'admission_officer'      => 'Admission Officer',
            'form_teacher'           => 'Form Teacher',
            'asst_form_teacher'      => 'Asst. Form Teacher',
            'subject_teacher'        => 'Subject Teacher',
            'form_subject_teacher'   => 'Form & Subject Teacher',
            'accountant'             => 'Accountant',
            'health_officer'         => 'Health Officer',
            'librarian'              => 'Librarian',
        ];
        $staffByRole = $allStaff->groupBy('role');
    @endphp
    <table class="asc-table">
        <thead>
            <tr><th>Role / Category</th><th class="num">Count</th><th>Category</th></tr>
        </thead>
        <tbody>
        @foreach($roleLabels as $role => $label)
        @php $count = ($staffByRole[$role] ?? collect())->count(); if(!$count) continue; @endphp
        <tr>
            <td>{{ $label }}</td>
            <td class="num">{{ $count }}</td>
            <td>
                @if(in_array($role, $managementRoles))
                    <span class="badge badge-blue">Management</span>
                @elseif(in_array($role, $teachingRoles))
                    <span class="badge badge-green">Teaching</span>
                @else
                    <span class="badge badge-amber">Non-Teaching</span>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><td><strong>TOTAL STAFF</strong></td><td class="num">{{ $allStaff->count() }}</td><td></td></tr>
        </tfoot>
    </table>

    {{-- ── Section 5: Infrastructure ── --}}
    <div class="section-header">Section 5 — Physical Infrastructure</div>
    @if($infra)
    <div class="kv-grid" style="margin-bottom:16px">
        <div class="kv-item"><div class="kv-label">Permanent Classrooms</div><div class="kv-value">{{ $infra->classrooms_permanent }}</div></div>
        <div class="kv-item"><div class="kv-label">Temporary Classrooms</div><div class="kv-value">{{ $infra->classrooms_temporary }}</div></div>
        <div class="kv-item"><div class="kv-label">Good Condition</div><div class="kv-value">{{ $infra->classrooms_good_condition }}</div></div>
        <div class="kv-item"><div class="kv-label">Need Repair</div><div class="kv-value">{{ $infra->classrooms_bad_condition }}</div></div>
        <div class="kv-item"><div class="kv-label">Toilets — Male Pupils</div><div class="kv-value">{{ $infra->toilets_male_pupils }}</div></div>
        <div class="kv-item"><div class="kv-label">Toilets — Female Pupils</div><div class="kv-value">{{ $infra->toilets_female_pupils }}</div></div>
        <div class="kv-item"><div class="kv-label">Toilets — Male Staff</div><div class="kv-value">{{ $infra->toilets_male_staff }}</div></div>
        <div class="kv-item"><div class="kv-label">Toilets — Female Staff</div><div class="kv-value">{{ $infra->toilets_female_staff }}</div></div>
        <div class="kv-item"><div class="kv-label">Water Source</div><div class="kv-value">{{ ucwords(str_replace('_',' ',$infra->water_source ?? '—')) }}</div></div>
        <div class="kv-item"><div class="kv-label">Electricity</div><div class="kv-value">{{ strtoupper($infra->electricity_source ?? '—') }}</div></div>
        <div class="kv-item"><div class="kv-label">Fence</div><div class="kv-value">{{ ucwords(str_replace('_',' ',$infra->fence_type ?? '—')) }}</div></div>
        <div class="kv-item"><div class="kv-label">Computers</div><div class="kv-value">{{ $infra->computer_count }}</div></div>
    </div>

    <table class="asc-table">
        <thead><tr><th>Facility</th><th class="num">Available</th></tr></thead>
        <tbody>
        @foreach([
            'has_library'         => 'Library',
            'has_science_lab'     => 'Science Laboratory',
            'has_computer_lab'    => 'Computer Laboratory',
            'has_sports_facility' => 'Sports / Playground',
            'has_first_aid'       => 'First Aid / Sick Bay',
        ] as $field => $label)
        <tr>
            <td>{{ $label }}</td>
            <td class="num">
                @if($infra->{$field})
                    <span class="badge badge-green">✓ Yes</span>
                @else
                    <span class="badge badge-red">✗ No</span>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @else
    <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:8px;padding:16px;text-align:center;color:#92400E;font-size:13px">
        ⚠️ Infrastructure data not yet filled. <a href="{{ route('asc.infrastructure', ['year'=>$year]) }}" style="color:var(--indigo);font-weight:700">Fill it in now →</a>
    </div>
    @endif

    {{-- ── Section 6: Summary ── --}}
    <div class="section-header">Section 6 — Summary Statistics</div>
    <div class="kv-grid">
        <div class="kv-item" style="border-color:#6366F1">
            <div class="kv-label">Total Students</div>
            <div class="kv-value" style="font-size:28px;color:var(--indigo)">{{ $grandTotal }}</div>
            <div style="font-size:11px;color:var(--slate-light);margin-top:2px">{{ $grandMale }} male · {{ $grandFemale }} female</div>
        </div>
        <div class="kv-item" style="border-color:#10B981">
            <div class="kv-label">Total Staff</div>
            <div class="kv-value" style="font-size:28px;color:#059669">{{ $allStaff->count() }}</div>
            <div style="font-size:11px;color:var(--slate-light);margin-top:2px">{{ $totalTeachers }} teachers · {{ $totalManagement }} management · {{ $totalNonTeaching }} non-teaching</div>
        </div>
        <div class="kv-item" style="border-color:#F59E0B">
            <div class="kv-label">Pupil : Teacher Ratio</div>
            <div class="kv-value" style="font-size:28px;color:#D97706">
                @if($totalTeachers > 0) {{ number_format($grandTotal / $totalTeachers, 1) }} : 1
                @else —
                @endif
            </div>
        </div>
        @if($specialNeeds > 0)
        <div class="kv-item" style="border-color:#EF4444">
            <div class="kv-label">Special Needs Students</div>
            <div class="kv-value" style="font-size:28px;color:#DC2626">{{ $specialNeeds }}</div>
        </div>
        @endif
    </div>

    {{-- Footer --}}
    <div style="margin-top:40px;padding-top:16px;border-top:1px solid var(--border);display:flex;justify-content:space-between;font-size:11px;color:var(--slate-light)">
        <div>Generated: {{ now()->format('d M Y, H:i') }} — EduCore SaaS</div>
        <div>Academic Year: {{ $year }}/{{ $year+1 }}</div>
    </div>
</div>
@endsection
