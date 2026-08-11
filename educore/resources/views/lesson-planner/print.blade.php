<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Lesson Plan — {{ $lessonPlan->topic }}</title>
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family: Arial, Helvetica, sans-serif; font-size:12pt; color:#000; background:#fff; padding:20mm; }
.header { text-align:center; margin-bottom:16pt; }
.header h2 { font-size:14pt; font-weight:bold; text-transform:uppercase; letter-spacing:.05em; }
.header p { font-size:10pt; color:#333; margin-top:4pt; }
.meta-table { width:100%; border-collapse:collapse; margin-bottom:14pt; }
.meta-table td { border:1px solid #000; padding:5pt 8pt; font-size:11pt; vertical-align:top; }
.meta-table td:first-child { font-weight:bold; width:35%; background:#f5f5f5; }
.section { margin-bottom:12pt; }
.section-title { font-size:11pt; font-weight:bold; text-transform:uppercase; border-bottom:2px solid #000; padding-bottom:4pt; margin-bottom:6pt; }
.section-body { font-size:11pt; line-height:1.7; white-space:pre-wrap; }
.badge { display:inline-block; border:1px solid #000; padding:2pt 8pt; font-size:10pt; font-weight:bold; }
.footer { margin-top:20pt; border-top:1px solid #000; padding-top:10pt; display:flex; justify-content:space-between; font-size:10pt; }
@media print {
    body { padding:15mm; }
    @page { margin:15mm; }
}
</style>
</head>
<body>

<div class="header">
    <h2>{{ $lessonPlan->isNerdc() ? 'LESSON PLAN (NERDC / TRCN FORMAT)' : 'LESSON PLAN (BRITISH CURRICULUM)' }}</h2>
    @if($lessonPlan->teacher?->tenant)
    <p>{{ $lessonPlan->teacher->tenant->name }}</p>
    @endif
</div>

<table class="meta-table">
    <tr>
        <td>Subject</td>
        <td>{{ $lessonPlan->subject->name }}</td>
        <td><b>Class</b></td>
        <td>{{ $lessonPlan->classLevel->name }}{{ $lessonPlan->classArm ? ' ' . $lessonPlan->classArm->name : '' }}</td>
    </tr>
    <tr>
        <td>Topic</td>
        <td>{{ $lessonPlan->topic }}</td>
        <td><b>Subtopic</b></td>
        <td>{{ $lessonPlan->subtopic ?? '—' }}</td>
    </tr>
    <tr>
        <td>Teacher</td>
        <td>{{ $lessonPlan->teacher->name ?? '—' }}</td>
        <td><b>Duration</b></td>
        <td>{{ $lessonPlan->duration_minutes }} minutes</td>
    </tr>
    <tr>
        <td>Term</td>
        <td>{{ $lessonPlan->term->name ?? '—' }}</td>
        <td><b>Week / Date</b></td>
        <td>{{ $lessonPlan->week_number ? 'Week ' . $lessonPlan->week_number : '' }}{{ $lessonPlan->plan_date ? ($lessonPlan->week_number ? ' — ' : '') . $lessonPlan->plan_date->format('d M Y') : '' }}</td>
    </tr>
</table>

@php $sections = $lessonPlan->sections(); $i = 1; @endphp
@foreach($sections as $field => $label)
@if($lessonPlan->$field)
<div class="section">
    <div class="section-title">{{ $i++ }}. {{ $label }}</div>
    <div class="section-body">{{ $lessonPlan->$field }}</div>
</div>
@endif
@endforeach

<div class="footer">
    <span>Teacher's Signature: ____________________________</span>
    <span>H.O.D. Signature: ____________________________</span>
    <span>Date: ____________________</span>
</div>

<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
