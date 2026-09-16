<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Annual School Census {{ $year }}/{{ $year + 1 }}</title>
<style>
    @page { margin: 24px 28px; }
    body { font-family: DejaVu Sans, sans-serif; color:#172033; font-size:10px; line-height:1.35; }
    h1,h2,h3,p { margin:0; }
    .header { border-bottom:2px solid #1e3a8a; padding-bottom:10px; margin-bottom:12px; }
    .header h1 { font-size:17px; color:#132a57; }
    .muted { color:#667085; }
    .meta { width:100%; border-collapse:collapse; margin-top:8px; }
    .meta td { padding:3px 5px; vertical-align:top; }
    .card { border:1px solid #d7deea; border-radius:6px; margin:10px 0; padding:10px; }
    .section-title { font-size:12px; font-weight:700; color:#132a57; border-bottom:1px solid #e3e8f0; padding-bottom:5px; margin-bottom:7px; }
    table.data { width:100%; border-collapse:collapse; margin-top:6px; }
    table.data th, table.data td { border:1px solid #dfe4ec; padding:4px 5px; text-align:left; vertical-align:top; }
    table.data th { background:#f4f6f9; font-weight:700; }
    .status { font-weight:700; }
    .ok { color:#067647; }
    .warn { color:#b54708; }
    .bad { color:#b42318; }
    .page-break { page-break-before:always; }
    .small { font-size:8.5px; }
    .footer-note { margin-top:12px; border-top:1px solid #d7deea; padding-top:7px; color:#667085; font-size:8.5px; }
</style>
</head>
<body>
@php
    $school = data_get($auto, 'school', []);
    $enrolment = data_get($auto, 'enrolment', []);
    $staff = data_get($auto, 'staff', []);
    $sectionC = data_get($auto, 'official.section_c', []);
    $sectionE = data_get($auto, 'official.section_e', []);
    $balanced = (bool)($reconciliation['balanced'] ?? false);
@endphp

<div class="header">
    <h1>Annual School Census — EduCore Consolidated Return</h1>
    <p class="muted">Census year {{ $year }}/{{ $year + 1 }} · Generated from the synchronized EduCore ASC snapshot</p>
    <table class="meta">
        <tr><td><strong>School:</strong> {{ $tenant->name }}</td><td><strong>Status:</strong> {{ strtoupper($ascReturn->status) }}</td></tr>
        <tr><td><strong>State:</strong> {{ data_get($school, 'state', $infrastructure?->school_state ?? '—') }}</td><td><strong>LGA:</strong> {{ data_get($school, 'lga', $infrastructure?->school_lga ?? '—') }}</td></tr>
        <tr><td><strong>Reference date:</strong> {{ optional($ascReturn->reference_date)->format('d M Y') ?? '—' }}</td><td><strong>Synchronized:</strong> {{ optional($ascReturn->synchronized_at)->format('d M Y, h:i A') ?? '—' }}</td></tr>
    </table>
</div>

<div class="card">
    <div class="section-title">Return Summary</div>
    <table class="data">
        <tr><th>Students</th><th>Staff</th><th>Completeness</th><th>Reconciliation</th></tr>
        <tr>
            <td>{{ data_get($enrolment, 'total', '—') }}</td>
            <td>{{ data_get($staff, 'total', '—') }}</td>
            <td>{{ $ascReturn->completeness['score'] ?? 0 }}%</td>
            <td class="status {{ $balanced ? 'ok' : 'bad' }}">{{ $balanced ? 'BALANCED' : 'REVIEW REQUIRED' }}</td>
        </tr>
    </table>
</div>

<div class="card">
    <div class="section-title">School Profile & Infrastructure</div>
    <table class="data">
        <tr><th>Ownership</th><td>{{ $infrastructure?->school_ownership ?? '—' }}</td><th>School type</th><td>{{ $infrastructure?->school_type ?? '—' }}</td></tr>
        <tr><th>Senatorial district</th><td>{{ $infrastructure?->school_senatorial_district ?? '—' }}</td><th>Head teacher</th><td>{{ $infrastructure?->head_teacher_name ?? '—' }}</td></tr>
        <tr><th>Permanent classrooms</th><td>{{ $infrastructure?->classrooms_permanent ?? '—' }}</td><th>Temporary classrooms</th><td>{{ $infrastructure?->classrooms_temporary ?? '—' }}</td></tr>
        <tr><th>Water source</th><td>{{ $infrastructure?->water_source ?? '—' }}</td><th>Electricity source</th><td>{{ $infrastructure?->electricity_source ?? '—' }}</td></tr>
        <tr><th>Library</th><td>{{ $infrastructure?->has_library ? 'Yes' : 'No' }}</td><th>Science laboratory</th><td>{{ $infrastructure?->has_science_lab ? 'Yes' : 'No' }}</td></tr>
    </table>
</div>

<div class="card">
    <div class="section-title">Section C — Enrolment (AUTO / DERIVED)</div>
    <table class="data">
        <tr><th>Total</th><th>Male</th><th>Female</th><th>Unknown gender</th></tr>
        <tr><td>{{ data_get($enrolment,'total',0) }}</td><td>{{ data_get($enrolment,'male',0) }}</td><td>{{ data_get($enrolment,'female',0) }}</td><td>{{ data_get($enrolment,'unknown_gender',0) }}</td></tr>
    </table>
    @if(count((array)data_get($enrolment,'by_level',[])))
    <table class="data small">
        <tr><th>Level</th><th>Section</th><th>Male</th><th>Female</th><th>Unknown</th><th>Total</th></tr>
        @foreach((array)data_get($enrolment,'by_level',[]) as $row)
        <tr><td>{{ $row['level_name'] ?? '—' }}</td><td>{{ $row['section'] ?? '—' }}</td><td>{{ $row['male'] ?? 0 }}</td><td>{{ $row['female'] ?? 0 }}</td><td>{{ $row['unknown_gender'] ?? 0 }}</td><td>{{ $row['total'] ?? 0 }}</td></tr>
        @endforeach
    </table>
    @endif
    @if(count((array)data_get($sectionC,'unsupported_fields',[])))
        <p class="warn small" style="margin-top:6px"><strong>Manual review gaps:</strong> {{ count((array)data_get($sectionC,'unsupported_fields',[])) }} field(s) cannot currently be derived safely.</p>
    @endif
</div>

<div class="card">
    <div class="section-title">Section E — Teachers (AUTO / DERIVED)</div>
    <table class="data">
        <tr><th>Total staff</th><th>Teaching</th><th>Management</th><th>Non-teaching</th></tr>
        <tr><td>{{ data_get($staff,'total',0) }}</td><td>{{ data_get($staff,'teaching',0) }}</td><td>{{ data_get($staff,'management',0) }}</td><td>{{ data_get($staff,'non_teaching',0) }}</td></tr>
    </table>
    @if(count((array)data_get($sectionE,'qualification_by_main_teaching_level',[])))
    <table class="data small">
        <tr><th>Qualification</th><th>Main teaching level</th><th>Male</th><th>Female</th><th>Unknown</th><th>Total</th></tr>
        @foreach((array)data_get($sectionE,'qualification_by_main_teaching_level',[]) as $row)
        <tr><td>{{ $row['qualification'] ?? '—' }}</td><td>{{ $row['main_teaching_level'] ?? '—' }}</td><td>{{ $row['male'] ?? 0 }}</td><td>{{ $row['female'] ?? 0 }}</td><td>{{ $row['unknown_gender'] ?? 0 }}</td><td>{{ $row['total'] ?? 0 }}</td></tr>
        @endforeach
    </table>
    @endif
    @if(count((array)data_get($sectionE,'unclassified_or_unassigned_teachers',[])))
        <p class="warn small" style="margin-top:6px"><strong>Teacher allocation review:</strong> {{ count((array)data_get($sectionE,'unclassified_or_unassigned_teachers',[])) }} teacher(s).</p>
    @endif
</div>

<div class="page-break"></div>

@foreach(['B','D','F','G','H'] as $section)
@php
    $record = $manualSections->get($section);
    $definition = config("asc.sections.{$section}", []);
    $values = $record?->data ?? [];
@endphp
<div class="card">
    <div class="section-title">Section {{ $section }} — {{ $definition['title'] ?? $section }} <span class="muted">({{ $record?->is_complete ? 'Complete' : 'Incomplete' }})</span></div>
    @if($record)
    <table class="data small">
        <tr><th style="width:38%">Field</th><th>Recorded value</th></tr>
        @foreach(($definition['fields'] ?? []) as $key => $field)
        @php $value = data_get($values, $key); @endphp
        <tr>
            <td>{{ $field['label'] ?? ucwords(str_replace('_',' ',$key)) }}</td>
            <td>
                @if(is_array($value))
                    {{ json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}
                @elseif(is_bool($value))
                    {{ $value ? 'Yes' : 'No' }}
                @else
                    {{ ($value === null || $value === '') ? '—' : $value }}
                @endif
            </td>
        </tr>
        @endforeach
    </table>
    @else
        <p class="warn">No data recorded for this section.</p>
    @endif
</div>
@endforeach

<div class="card">
    <div class="section-title">Validation & Reconciliation</div>
    <table class="data">
        <tr><th>Current enrolment rows</th><td>{{ $reconciliation['current_enrollment_rows'] ?? '—' }}</td><th>Distinct students</th><td>{{ $reconciliation['distinct_current_students'] ?? '—' }}</td></tr>
        <tr><th>Synchronized students</th><td>{{ $reconciliation['synchronized_students'] ?? '—' }}</td><th>Duplicate current enrolments</th><td>{{ $reconciliation['duplicate_current_enrollments'] ?? '—' }}</td></tr>
    </table>
    @if(count($issues))
        <div style="margin-top:7px"><strong>Recorded data-quality issues</strong></div>
        <ul class="small">
            @foreach($issues as $issue)<li class="{{ in_array($issue,$blockingIssues,true) ? 'bad' : 'warn' }}">{{ $issue }}</li>@endforeach
        </ul>
    @else
        <p class="ok" style="margin-top:7px">No synchronization issues recorded.</p>
    @endif
</div>

<div class="footer-note">
    This document is an EduCore-generated consolidated census return/review copy based on the school's synchronized records and census-specific inputs. It does not replace any prescribed government submission format or external submission acknowledgement where those are separately required.
</div>
</body>
</html>
