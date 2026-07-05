<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Academic Transcript - {{ $student->full_name }}</title>
<style>
@page{size:A4 landscape;margin:18pt}
*{box-sizing:border-box}
body{margin:0;font-family:DejaVu Sans,sans-serif;font-size:11pt;color:#172033}
table{border-collapse:collapse}
.page{width:806pt;page-break-after:always}
.page:last-child{page-break-after:auto}
.header{width:806pt;border:.35pt solid #9aa8b6}
.header td{vertical-align:middle}
.logo{width:74pt;padding:7pt;text-align:center;border-right:.35pt solid #cbd5e1}
.logo img{width:54pt;height:54pt;object-fit:contain}
.fallback{width:50pt;height:50pt;line-height:50pt;margin:auto;border:.9pt solid #1f4e79;border-radius:25pt;color:#1f4e79;font-size:22pt;font-weight:700}
.brand{padding:6pt 12pt;text-align:center}
.school{color:#17365d;font-size:16pt;font-weight:700;text-transform:uppercase}
.contact{margin-top:3pt;color:#52606d;font-size:9pt}
.title{margin-top:5pt;padding:4pt;border-top:.35pt solid #7f93a8;border-bottom:.35pt solid #7f93a8;color:#17365d;font-size:11pt;font-weight:700;text-transform:uppercase;letter-spacing:.55pt}
.identity{width:806pt;border:.35pt solid #9aa8b6;border-top:0;table-layout:fixed}
.identity td{padding:5pt 6pt;border-right:.2pt solid #e1e6eb;font-size:10pt;vertical-align:top}
.identity td:last-child{border-right:0}
.label{color:#52606d;font-size:8pt;font-weight:700;text-transform:uppercase}
.value{display:block;margin-top:1.5pt;font-size:10.5pt;font-weight:600}
.section{width:806pt;margin-top:7pt}
.section td{padding:4.5pt 6pt;border:.35pt solid #9aa8b6;border-top:.65pt solid #6f8499;background:#edf3f8;color:#17365d;font-size:9.5pt;font-weight:700;text-transform:uppercase;letter-spacing:.35pt}
.academic{width:806pt;table-layout:fixed;border:.45pt solid #8fa0b2;font-size:11pt}
.academic th,.academic td{border:.35pt solid #cbd5df;text-align:center;vertical-align:middle}
.academic th{padding:5pt 3pt;font-weight:700}
.academic td{padding:5pt 3pt}
.subject-head{background:#edf3f8;color:#17365d;text-align:left!important;padding-left:7pt!important;font-size:10pt;text-transform:uppercase}
.session-head{background:#17365d;color:#fff;font-size:10.5pt}
.class-head{background:#22456e;color:#cfdcec;font-size:9pt;font-weight:400!important;font-style:italic}
.term-head{background:#f3f6f9;color:#243b53;font-size:9pt;text-transform:uppercase}
.subject{text-align:left!important;padding-left:7pt!important;font-weight:700;background:#fbfcfe;color:#243b53}
.academic tbody tr:nth-child(even) td:not(.subject){background:#fafbfd}
.score{font-weight:700}
.nil{color:#b9c3cd}
.total td{background:#edf3f8;color:#17365d;font-weight:700;border-top:.8pt solid #8fa0b2}
.total td:first-child{text-align:left;padding-left:7pt;color:#52606d;font-size:9pt;text-transform:uppercase}
.footer{width:806pt;margin-top:8pt;border-top:.35pt solid #9aa8b6}
.footer td{width:33.333%;padding-top:4pt;color:#52606d;font-size:8pt}
.center{text-align:center}.right{text-align:right}
</style>
</head>
<body>
@php
$logo = null;
if (!empty($tenant->logo_path)) {
    $clean = preg_replace('#^storage/#', '', ltrim($tenant->logo_path, '/'));
    $path = storage_path('app/public/'.$clean);
    if (file_exists($path)) $logo = $path;
}
$chunks = $bySession->chunk(3)->values();
$subjectWidth = 22;
$scoreWidth = round((100 - $subjectWidth) / 9, 3);
@endphp

@forelse($chunks as $chunkIndex => $chunk)
@php
$sessions = [];
foreach ($chunk as $sessionSummaries) {
    $slots = $sessionSummaries->sortBy('term_id')->values()->all();
    while (count($slots) < 3) $slots[] = null;
    $sessions[] = [
        'name' => optional($sessionSummaries->first()?->session)->name ?? 'Session',
        'class' => trim(optional(optional($sessionSummaries->first()?->classArm)->classLevel)->name.' '.optional($sessionSummaries->first()?->classArm)->name),
        'slots' => array_slice($slots, 0, 3),
    ];
}
while (count($sessions) < 3) {
    $sessions[] = ['name' => '-', 'class' => '', 'slots' => [null, null, null]];
}
$subjects = $allSubjects->filter(function ($name, $subjectId) use ($sessions, $scoresByTerm) {
    foreach ($sessions as $session) {
        foreach ($session['slots'] as $summary) {
            if ($summary && isset($scoresByTerm[$summary->term_id][$subjectId])) return true;
        }
    }
    return false;
});
@endphp

<div class="page">
<table class="header">
<tr>
<td class="logo">
@if($logo)
<img src="{{ $logo }}" alt="">
@else
<div class="fallback">{{ strtoupper(mb_substr($tenant->name ?? 'S', 0, 1)) }}</div>
@endif
</td>
<td class="brand">
<div class="school">{{ $tenant->name }}</div>
<div class="contact">{{ $tenant->address ?? '' }}@if(!empty($tenant->phone)) - {{ $tenant->phone }}@endif</div>
<div class="title">Academic Transcript</div>
</td>
</tr>
</table>

<table class="identity">
<tr>
<td><span class="label">Full Name</span><span class="value">{{ strtoupper($student->full_name) }}</span></td>
<td><span class="label">Admission No.</span><span class="value">{{ $student->admission_number }}</span></td>
<td><span class="label">Date of Birth</span><span class="value">{{ optional($student->date_of_birth)->format('d M Y') ?? '-' }}</span></td>
<td><span class="label">Gender</span><span class="value">{{ ucfirst($student->gender ?? '-') }}</span></td>
<td><span class="label">Current Class</span><span class="value">{{ trim(optional(optional($student->currentClassArm)->classLevel)->name.' '.optional($student->currentClassArm)->name) ?: '-' }}</span></td>
<td><span class="label">Status</span><span class="value">{{ ucfirst($student->status ?? '-') }}</span></td>
</tr>
</table>

<table class="section"><tr><td>Academic Performance by Subject @if($chunks->count() > 1) - Page {{ $chunkIndex + 1 }} of {{ $chunks->count() }} @endif</td></tr></table>

<table class="academic">
<colgroup>
<col style="width:{{ $subjectWidth }}%">
@for($i = 0; $i < 9; $i++)<col style="width:{{ $scoreWidth }}%">@endfor
</colgroup>
<thead>
<tr>
<th class="subject-head" rowspan="3">Subject</th>
@foreach($sessions as $session)<th class="session-head" colspan="3">{{ $session['name'] }}</th>@endforeach
</tr>
<tr>
@foreach($sessions as $session)<th class="class-head" colspan="3">{{ $session['class'] ?: '-' }}</th>@endforeach
</tr>
<tr>
@foreach($sessions as $session)
@foreach($session['slots'] as $i => $summary)
<th class="term-head">{{ $summary ? optional($summary->term)->name : ['1st Term','2nd Term','3rd Term'][$i] }}</th>
@endforeach
@endforeach
</tr>
</thead>
<tbody>
@foreach($subjects as $subjectId => $subjectName)
<tr>
<td class="subject">{{ $subjectName }}</td>
@foreach($sessions as $session)
@foreach($session['slots'] as $summary)
@php
$score = $summary ? data_get($scoresByTerm, $summary->term_id.'.'.$subjectId.'.total') : null;
@endphp
<td class="{{ $score === null ? 'nil' : 'score' }}">{{ $score === null ? '-' : number_format((float) $score, 0) }}</td>
@endforeach
@endforeach
</tr>
@endforeach
<tr class="total">
<td>Term Total Score</td>
@foreach($sessions as $session)
@foreach($session['slots'] as $summary)
<td>{{ $summary && ($summary->total_score ?? null) !== null ? number_format((float) $summary->total_score, 0) : '-' }}</td>
@endforeach
@endforeach
</tr>
</tbody>
</table>

<table class="footer">
<tr>
<td>{{ $tenant->name }} - Official Academic Transcript</td>
<td class="center">Generated {{ now()->format('d M Y, H:i') }}</td>
<td class="right">Computer-generated document</td>
</tr>
</table>
</div>
@empty
<p style="padding:20pt;text-align:center;color:#52606d">No academic records found for this student.</p>
@endforelse
</body>
</html>