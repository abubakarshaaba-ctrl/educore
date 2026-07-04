@php
    $embedded = $embedded ?? false;
    $avg = (float) ($summary->final_average ?? 0);
    $pos = (int) ($summary->position_in_class ?? 0);
    $total = (int) ($summary->total_students_in_class ?? 0);
    $isThird = (bool) ($isThirdTerm ?? false);

    $ordinal = function ($number) {
        $number = (int) $number;
        if ($number <= 0) return '—';
        $mod100 = $number % 100;
        if ($mod100 >= 11 && $mod100 <= 13) return $number . 'th';
        return $number . match ($number % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' };
    };

    $gradeRecord = $gradingSystem->sortByDesc('min_score')->first(function ($grade) use ($avg) {
        return $avg >= (float) $grade->min_score && $avg <= (float) $grade->max_score;
    });
    $overallGrade = $gradeRecord->grade_letter ?? '—';
    $overallRemark = $gradeRecord->remark ?? ($avg >= 50 ? 'Satisfactory' : 'Needs Improvement');
    $performanceClass = $avg >= 70 ? 'good' : ($avg >= 50 ? 'fair' : 'risk');

    $assessmentCount = max(1, $assessmentTypes->count());

    // Portrait (1st / 2nd term) academic table column sizing
    $termlySubjectBase  = 145;
    $termlyOtherColumns = 210;
    $assessmentPool     = 559 - $termlySubjectBase - $termlyOtherColumns;
    $assessmentWidth    = max(24, intdiv($assessmentPool, $assessmentCount));
    $subjectWidth       = $termlySubjectBase + (559 - ($termlySubjectBase + $termlyOtherColumns + ($assessmentWidth * $assessmentCount)));
    if ($subjectWidth < 120) $subjectWidth = 120;

    // Landscape (3rd-term cumulative) column sizing
    // Columns: Subject | 1st Term | 2nd Term | [3rd-term assessments × N] | 3rd Total | Annual Total | Average | Grade | Position | Lowest | Highest | Remark
    $lw             = 806; // usable width on A4 landscape with 18pt margins
    $lAssessW       = 34;
    $lFixedOther    = 52 + 52 + 50 + 55 + 52 + 35 + 40 + 40 + 40; // 416
    $lSubjectW      = 175;
    $lRemarkW       = max(55, $lw - $lSubjectW - $lFixedOther - ($lAssessW * $assessmentCount));

    $promotionDecision = data_get($summary, 'promotion_decision')
        ?? data_get($summary, 'promotion_status')
        ?? data_get($summary, 'decision')
        ?? null;
    $promotedTo = data_get($summary, 'promoted_to_class')
        ?? data_get($summary, 'promoted_to')
        ?? data_get($summary, 'next_class_name')
        ?? null;

    $logoAbsPath = null;
    if (!empty($tenant->logo_path)) {
        $cleanLogoPath = preg_replace('#^storage/#', '', ltrim($tenant->logo_path, '/'));
        $candidateLogoPath = storage_path('app/public/' . $cleanLogoPath);
        if (file_exists($candidateLogoPath)) $logoAbsPath = $candidateLogoPath;
    }
@endphp

@unless($embedded)
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
@endunless
@if($renderStyles ?? true)
<style>
@page { margin: 18pt; size: @if($isThird ?? false) A4 landscape @else A4 portrait @endif; }
* { box-sizing: border-box; }
body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #172033; }
.report-card { width: 559pt; margin: 0 auto; page-break-inside: avoid; }
table { border-collapse: collapse; }

/* Brand header */
.report-header { width: 559pt; border: 0.35pt solid #9aa8b6; }
.report-header td { vertical-align: middle; }
.brand-logo { width: 64pt; padding: 6pt; text-align: center; border-right: 0.35pt solid #cbd5e1; }
.logo-image { width: 48pt; height: 48pt; object-fit: contain; }
.logo-fallback { width: 46pt; height: 46pt; line-height: 46pt; margin: 0 auto; border: 0.9pt solid #1f4e79; border-radius: 23pt; color: #1f4e79; font-size: 22pt; font-weight: 700; }
.brand-copy { padding: 5pt 10pt; text-align: center; }
.school-name { color: #17365d; font-size: 14pt; line-height: 1.1; font-weight: 700; text-transform: uppercase; }
.school-contact { margin-top: 2pt; color: #52606d; font-size: 7.2pt; line-height: 1.35; }
.document-title { margin-top: 4pt; padding: 3pt 4pt; border-top: 0.35pt solid #7f93a8; border-bottom: 0.35pt solid #7f93a8; color: #17365d; font-size: 8.6pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.55pt; }

/* Identity */
.identity { width: 559pt; border: 0.35pt solid #9aa8b6; border-top: 0; table-layout: fixed; }
.identity td { padding: 3.2pt 5pt; border-right: 0.2pt solid #e1e6eb; border-bottom: 0.2pt solid #e1e6eb; font-size: 7.7pt; }
.identity td:last-child { border-right: 0; }
.identity tr:last-child td { border-bottom: 0; }
.label { color: #52606d; font-size: 6.5pt; font-weight: 700; text-transform: uppercase; }
.value { color: #172033; font-weight: 600; }

/* KPI strip */
.kpis { width: 559pt; border: 0.35pt solid #9aa8b6; border-top: 0; table-layout: fixed; }
.kpis td { padding: 5pt 2pt; text-align: center; border-right: 0.2pt solid #e1e6eb; }
.kpis td:last-child { border-right: 0; }
.kpi-value { font-size: 12pt; font-weight: 700; color: #17365d; line-height: 1.05; }
.kpi-label { margin-top: 1pt; color: #64748b; font-size: 6.2pt; text-transform: uppercase; }
.good { color: #16794b !important; }
.fair { color: #9a6700 !important; }
.risk { color: #b42318 !important; }

.section-bar { width: 559pt; margin-top: 6pt; table-layout: fixed; border-collapse: collapse; }
.section-bar td {
    padding: 3.6pt 5pt;
    border: 0.35pt solid #9aa8b6;
    border-top: 0.65pt solid #6f8499;
    background: #edf3f8;
    color: #17365d;
    font-size: 7.4pt;
    font-weight: 700;
    text-align: left;
    text-transform: uppercase;
    letter-spacing: 0.35pt;
}

/* Academic tables */
.academic {
    width: 559pt;
    table-layout: auto;
    border-collapse: collapse;
    border: 0.45pt solid #8fa0b2;
    font-size: 7pt;
}
.academic th {
    padding: 3pt 2.2pt;
    border: 0.35pt solid #aebbc9;
    background: #f3f6f9;
    color: #243b53;
    text-align: center;
    font-size: 6.4pt;
    text-transform: uppercase;
    line-height: 1.15;
    white-space: nowrap;
}
.academic td {
    padding: 2.8pt 2.2pt;
    border: 0.3pt solid #cbd5df;
    text-align: center;
    vertical-align: middle;
}
.academic th:last-child, .academic td:last-child { border-right: 0; }
.academic tbody tr:last-child td { border-bottom: 0; }
.academic tbody tr:nth-child(even) td { background: #fafbfd; }
.academic .subject {
    width: 22%;
    min-width: 110pt;
    padding: 3pt 4pt;
    text-align: left;
    font-weight: 700;
    line-height: 1.22;
    white-space: normal;
    word-wrap: break-word;
}
.academic .remark { padding-left: 3pt; text-align: left; font-size: 6.5pt; }
.academic .strong { font-weight: 700; }
.academic .pass { color: #16794b; font-weight: 700; }
.academic .fail { color: #b42318; font-weight: 700; }

.decision { font-size: 9pt; font-weight: 700; color: #17365d; }

/* Grade scale */
.grade-scale { width: 559pt; table-layout: fixed; border: 0.35pt solid #9aa8b6; border-top: 0; }
.grade-scale th, .grade-scale td { padding: 2.3pt 1pt; text-align: center; border-right: 0.2pt solid #e1e6eb; font-size: 6.2pt; }
.grade-scale th { background: #f6f8fa; color: #243b53; }
.grade-scale th:last-child, .grade-scale td:last-child { border-right: 0; }

/* Behavioural development and attendance */
.development-composite { width: 559pt; margin-top: 6pt; border: 0; border-top: 0.7pt solid #6f8499; border-bottom: 0.35pt solid #9aa8b6; table-layout: fixed; border-collapse: collapse; }
.development-composite > tbody > tr > th,
.development-composite > tbody > tr > td { padding: 0; vertical-align: top; }
.development-heading { padding: 3.1pt 5pt !important; background: #edf3f8; border-bottom: 0.35pt solid #9aa8b6; color: #17365d; font-size: 7.6pt; font-weight: 700; text-align: left; text-transform: uppercase; letter-spacing: 0.45pt; }
.development-cell { border-right: 0.35pt solid #9aa8b6; }
.development-cell.last { border-right: 0; }
.panel-title { padding: 3.2pt 4pt; background: #f3f6f9; border-bottom: 0.3pt solid #c4ced8; color: #243b53; font-size: 6.6pt; font-weight: 700; text-align: center; text-transform: uppercase; letter-spacing: 0.2pt; }
.domain-table, .attendance-table { width: 100%; table-layout: fixed; border-collapse: collapse; border: 0.35pt solid #aebbc9; }
.domain-table th { padding: 2.4pt 3pt; background: #fafbfd; border-bottom: 0.2pt solid #e1e6eb; color: #64748b; font-size: 5.4pt; text-align: left; text-transform: uppercase; vertical-align: middle; }
.domain-table th.domain-score, .domain-table td.domain-score { text-align: center; }
.domain-table th.domain-level { text-align: center; }
.domain-table td.domain-level { text-align: left; }
.domain-table td { padding: 2.7pt 3pt; border-bottom: 0.3pt solid #e7ebf0; font-size: 6.4pt; vertical-align: middle; }
.domain-table tr:last-child td { border-bottom: 0; }
.domain-score { width: 27pt; text-align: center; }
.domain-level { width: 51pt; text-align: left; color: #52606d; }
.score-pill { display: inline-block; min-width: 16pt; padding: 1.4pt 2.5pt; border: 0.6pt solid #9aa7b5; border-radius: 6pt; background: #f8fafc; color: #334155; font-size: 6pt; font-weight: 700; text-align: center; }
.score-pill.recorded { border-color: #1f4e79; background: #1f4e79; color: #fff; }
.attendance-table th { width: 62%; padding: 3.1pt 3pt; background: #fafbfd; border-right: 0.2pt solid #e1e6eb; border-bottom: 0.3pt solid #e7ebf0; color: #52606d; font-size: 5.5pt; font-weight: 700; text-align: left; text-transform: uppercase; vertical-align: middle; }
.attendance-table td { width: 38%; padding: 3.1pt 3pt; border-bottom: 0.3pt solid #e7ebf0; color: #17365d; font-size: 6.8pt; font-weight: 700; text-align: center; vertical-align: middle; }
.attendance-table tr:last-child th, .attendance-table tr:last-child td { border-bottom: 0; }
.rating-key-cell { padding: 2.4pt 4pt !important; border-top: 0.35pt solid #9aa8b6; background: #fafbfd; color: #66788a; font-size: 5.4pt; text-align: center; }

/* Remarks and report information */
.remarks { width: 559pt; margin-top: 6pt; border: 0; border-top: 0.7pt solid #6f8499; border-bottom: 0.35pt solid #9aa8b6; table-layout: fixed; }
.remarks td { width: 50%; padding: 0; vertical-align: top; border-right: 0.35pt solid #9aa8b6; }
.remarks td:last-child { border-right: 0; }
.remark-title { padding: 3pt 5pt; background: #f3f6f9; border-bottom: 0.3pt solid #c4ced8; color: #243b53; font-size: 6.5pt; font-weight: 700; text-transform: uppercase; }
.remark-body { min-height: 29pt; padding: 5pt 6pt; font-size: 7.5pt; line-height: 1.45; }
.footer {
    width: 559pt;
    margin-top: 0;
    border-collapse: collapse;
    border: 0.4pt solid #9aa8b6;
    table-layout: fixed;
}
.footer td {
    padding: 5pt 7pt;
    border: 0.3pt solid #cbd5df;
    vertical-align: middle;
}
.footer td:last-child { border-right: 0; }
.generated { margin-top: 3pt; color: #9aa7b5; font-size: 5.8pt; text-align: center; }
.page-break { page-break-after: always; }
</style>
@if($isThird ?? false)
<style>
/* Landscape overrides — all layout tables expand to 806pt */
.report-card { width: 806pt; }
.report-header, .identity, .kpis, .section-bar, .academic,
.grade-scale, .development-composite, .remarks, .footer { width: 806pt; }
.academic { table-layout: auto; }
</style>
@endif
@endif
@unless($embedded)
</head>
<body>
@endunless

<div class="report-card">
<table class="report-header">
<tr>
    <td class="brand-logo">
        @if($logoAbsPath)
            <img src="{{ $logoAbsPath }}" class="logo-image" alt="School logo">
        @else
            <div class="logo-fallback">{{ strtoupper(substr($tenant->name ?? 'E', 0, 1)) }}</div>
        @endif
    </td>
    <td class="brand-copy">
        <div class="school-name">{{ $tenant->name }}</div>
        <div class="school-contact">
            {{ $tenant->address ?? '' }}
            @if(!empty($tenant->phone)) &nbsp;|&nbsp; {{ $tenant->phone }} @endif
            @if(!empty($tenant->email)) &nbsp;|&nbsp; {{ $tenant->email }} @endif
        </div>
        <div class="document-title">
            {{ $isThird ? 'Third-Term Cumulative Student Report' : 'Student Termly Performance Report' }}
        </div>
    </td>
</tr>
</table>

<table class="identity">
@if($isThird)<colgroup><col style="width:260pt"><col style="width:182pt"><col style="width:182pt"><col style="width:182pt"></colgroup>
@else<colgroup><col style="width:180pt"><col style="width:126pt"><col style="width:126pt"><col style="width:127pt"></colgroup>
@endif
<tr>
    <td><span class="label">Student Name</span><br><span class="value">{{ $student->full_name }}</span></td>
    <td><span class="label">Admission Number</span><br><span class="value">{{ $student->admission_number }}</span></td>
    <td><span class="label">Class</span><br><span class="value">{{ $classArm->classLevel->name }} {{ $classArm->name }}</span></td>
    <td><span class="label">Gender</span><br><span class="value">{{ ucfirst($student->gender ?? '—') }}</span></td>
</tr>
<tr>
    <td><span class="label">Session / Term</span><br><span class="value">{{ $session->name }} / {{ $term->name }}</span></td>
    <td><span class="label">Date of Birth</span><br><span class="value">{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d M Y') : '—' }}</span></td>
    <td><span class="label">Date Issued</span><br><span class="value">{{ now()->format('d M Y') }}</span></td>
    <td><span class="label">Number in Class</span><br><span class="value">{{ $total ?: '—' }}</span></td>
</tr>
</table>

<table class="kpis">
@if($isThird)<colgroup><col style="width:134pt"><col style="width:134pt"><col style="width:134pt"><col style="width:134pt"><col style="width:134pt"><col style="width:136pt"></colgroup>
@else<colgroup><col style="width:94pt"><col style="width:94pt"><col style="width:94pt"><col style="width:94pt"><col style="width:94pt"><col style="width:89pt"></colgroup>
@endif
<tr>
    <td><div class="kpi-value">{{ number_format($avg, 1) }}%</div><div class="kpi-label">{{ $isThird ? 'Annual Average' : 'Term Average' }}</div></td>
    <td><div class="kpi-value">{{ $ordinal($pos) }}</div><div class="kpi-label">Class Position</div></td>
    <td><div class="kpi-value">{{ $overallGrade }}</div><div class="kpi-label">Overall Grade</div></td>
    <td><div class="kpi-value {{ $performanceClass }}">{{ $overallRemark }}</div><div class="kpi-label">Performance</div></td>
    <td><div class="kpi-value">{{ $summary->subjects_offered ?? count($subjectRows) }}</div><div class="kpi-label">Subjects Offered</div></td>
    <td><div class="kpi-value">{{ number_format((float) ($summary->total_score ?? 0), 1) }}</div><div class="kpi-label">Total Score</div></td>
</tr>
</table>

@if($isThird)
    <table class="section-bar"><tr><td>Third-Term Cumulative Academic Performance</td></tr></table>
    <table class="academic">
        <thead>
        <tr>
            <th style="text-align:left;padding-left:4pt">Subject</th>
            <th>1st Term<br>Total</th>
            <th>2nd Term<br>Total</th>
            @foreach($assessmentTypes as $assessmentType)
                <th>{{ strtoupper(substr($assessmentType->name, 0, 6)) }}<br><span style="font-size:5.5pt;font-weight:400">{{ $assessmentType->weight_percentage }}%</span></th>
            @endforeach
            <th>3rd Term<br>Total</th>
            <th>Annual<br>Total</th>
            <th>Avg</th>
            <th>Grade</th>
            <th>Pos</th>
            <th>Low</th>
            <th>High</th>
            <th style="text-align:left">Remark</th>
        </tr>
        </thead>
        <tbody>
        @foreach($subjectRows as $row)
        <tr>
            <td class="subject">{{ $row['subject_name'] }}</td>
            <td>{{ $row['term1_avg'] ?? '—' }}</td>
            <td>{{ $row['term2_avg'] ?? '—' }}</td>
            @foreach($assessmentTypes as $assessmentType)
                <td>{{ $row['scores'][$assessmentType->id] ?? '—' }}</td>
            @endforeach
            <td>{{ $row['term3_avg'] ?? $row['total'] ?? '—' }}</td>
            <td class="strong">{{ $row['annual_total'] ?? '—' }}</td>
            <td class="strong">{{ $row['cumulative_avg'] ?? '—' }}</td>
            <td class="{{ ($row['is_pass'] ?? false) ? 'pass' : 'fail' }}">{{ $row['grade'] ?? '—' }}</td>
            <td>{{ $row['class_position'] ?? '—' }}</td>
            <td>{{ $row['class_lowest'] ?? '—' }}</td>
            <td>{{ $row['class_highest'] ?? '—' }}</td>
            <td class="remark">{{ $row['remark'] ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
@else
    <table class="section-bar"><tr><td>{{ $term->name }} Academic Performance</td></tr></table>
    <table class="academic">
        <thead>
        <tr>
            <th style="text-align:left;padding-left:4pt">Subject</th>
            @foreach($assessmentTypes as $assessmentType)
                <th>{{ strtoupper(substr($assessmentType->name, 0, 6)) }}<br><span style="font-size:5.5pt;font-weight:400">{{ $assessmentType->weight_percentage }}%</span></th>
            @endforeach
            <th>Total</th><th>Grade</th><th>Pos</th><th>Low</th><th>High</th><th style="text-align:left">Remark</th>
        </tr>
        </thead>
        <tbody>
        @foreach($subjectRows as $row)
        <tr>
            <td class="subject">{{ $row['subject_name'] }}</td>
            @foreach($assessmentTypes as $assessmentType)<td>{{ $row['scores'][$assessmentType->id] ?? '—' }}</td>@endforeach
            <td class="strong">{{ $row['total'] ?? '—' }}</td>
            <td class="{{ ($row['is_pass'] ?? false) ? 'pass' : 'fail' }}">{{ $row['grade'] ?? '—' }}</td>
            <td>{{ $row['class_position'] ?? '—' }}</td>
            <td>{{ $row['class_lowest'] ?? '—' }}</td>
            <td>{{ $row['class_highest'] ?? '—' }}</td>
            <td class="remark">{{ $row['remark'] ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
@endif

@if($gradingSystem->count())
@php $gradeRows = $gradingSystem->sortByDesc('min_score'); $gradeWidth = intdiv(559, max(1, $gradeRows->count())); @endphp
<table class="grade-scale">
<colgroup>@foreach($gradeRows as $grade)<col style="width:{{ $gradeWidth }}pt">@endforeach</colgroup>
<thead><tr>@foreach($gradeRows as $grade)<th>{{ $grade->grade_letter }}</th>@endforeach</tr></thead>
<tbody><tr>@foreach($gradeRows as $grade)<td>{{ $grade->min_score }}-{{ $grade->max_score }}<br>{{ $grade->remark }}</td>@endforeach</tr></tbody>
</table>
@endif

@php
    $ratingLabels = [1 => 'Poor', 2 => 'Fair', 3 => 'Good', 4 => 'Very Good', 5 => 'Excellent'];
@endphp
<table class="development-composite">
    @if($isThird)
    <colgroup><col style="width:268pt"><col style="width:269pt"><col style="width:269pt"></colgroup>
    @else
    <colgroup><col style="width:186pt"><col style="width:186pt"><col style="width:187pt"></colgroup>
    @endif
    <tbody>
    <tr>
        <th colspan="3" class="development-heading">Behavioural Development and Attendance</th>
    </tr>
    <tr>
        <td class="development-cell">
            <div class="panel-title">Affective Domain</div>
            <table class="domain-table">
                <colgroup><col><col style="width:27pt"><col style="width:51pt"></colgroup>
                <thead>
                <tr><th>Attribute</th><th class="domain-score">Score</th><th class="domain-level">Rating</th></tr>
                </thead>
                <tbody>
                @forelse($affectiveSkills as $skill)
                    @php $rating = (int) ($skillRatings->where('skill_definition_id', $skill->id)->first()?->rating ?? 0); @endphp
                    <tr>
                        <td>{{ $skill->name }}</td>
                        <td class="domain-score"><span class="score-pill {{ $rating > 0 ? 'recorded' : '' }}">{{ $rating ?: '—' }}</span></td>
                        <td class="domain-level">{{ $ratingLabels[$rating] ?? 'Not rated' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No affective ratings recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </td>
        <td class="development-cell">
            <div class="panel-title">Psychomotor Domain</div>
            <table class="domain-table">
                <colgroup><col><col style="width:27pt"><col style="width:51pt"></colgroup>
                <thead>
                <tr><th>Skill</th><th class="domain-score">Score</th><th class="domain-level">Rating</th></tr>
                </thead>
                <tbody>
                @forelse($psychomotorSkills as $skill)
                    @php $rating = (int) ($skillRatings->where('skill_definition_id', $skill->id)->first()?->rating ?? 0); @endphp
                    <tr>
                        <td>{{ $skill->name }}</td>
                        <td class="domain-score"><span class="score-pill {{ $rating > 0 ? 'recorded' : '' }}">{{ $rating ?: '—' }}</span></td>
                        <td class="domain-level">{{ $ratingLabels[$rating] ?? 'Not rated' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No psychomotor ratings recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </td>
        <td class="development-cell last">
            <div class="panel-title">Attendance Record</div>
            <table class="attendance-table">
                <tbody>
                <tr><th>Days School Open</th><td>{{ $attendanceSummary['days_open'] ?? '—' }}</td></tr>
                <tr><th>Days Present</th><td class="good">{{ $attendanceSummary['days_present'] ?? '—' }}</td></tr>
                <tr><th>Days Absent</th><td class="risk">{{ $attendanceSummary['days_absent'] ?? '—' }}</td></tr>
                <tr><th>Attendance Rate</th><td>{{ $attendanceSummary['rate'] ?? '—' }}{{ is_numeric($attendanceSummary['rate'] ?? null) ? '%' : '' }}</td></tr>
                <tr><th>Class Position</th><td>{{ $ordinal($pos) }} of {{ $total ?: '—' }}</td></tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="3" class="rating-key-cell">Rating scale: 1 Poor &nbsp;•&nbsp; 2 Fair &nbsp;•&nbsp; 3 Good &nbsp;•&nbsp; 4 Very Good &nbsp;•&nbsp; 5 Excellent</td>
    </tr>
    </tbody>
</table>

<table class="remarks">
<tr>
<td><div class="remark-title">Form Teacher's Comment</div><div class="remark-body">{{ $summary->form_tutor_remark ?: '................................................................................................................' }}</div></td>
<td><div class="remark-title">Principal's Comment</div><div class="remark-body">{{ $summary->principal_remark ?: '................................................................................................................' }}</div></td>
</tr>
</table>

<table class="footer">
@if($isThird)
<colgroup>
    <col style="width:269pt">
    <col style="width:268pt">
    <col style="width:269pt">
</colgroup>
@else
<colgroup>
    <col style="width:280pt">
    <col style="width:279pt">
</colgroup>
@endif
<tr>
<td>
    <span class="label">Form Teacher</span><br>
    <span class="value">{{ optional($classArm->formTutor)->name ?? '—' }}</span>
</td>
<td>
    <span class="label">Next Term Begins</span><br>
    <span class="value">{{ $term->next_term_begins ? \Carbon\Carbon::parse($term->next_term_begins)->format('d M Y') : 'To be announced' }}</span>
</td>
@if($isThird)
<td>
    <span class="label">Promotion Decision</span><br>
    <span class="value">
        {{ $promotionDecision ? ucwords(str_replace('_', ' ', $promotionDecision)) : 'Pending / Not Recorded' }}
        @if($promotedTo)
            <br><span style="font-size:6.5pt;color:#52606d;">Promoted to: {{ $promotedTo }}</span>
        @endif
    </span>
</td>
@endif
</tr>
</table>
<div class="generated">Generated by EduCore for {{ $tenant->name }} on {{ now()->format('d F Y') }}</div>
</div>

@unless($embedded)
</body>
</html>
@endunless
