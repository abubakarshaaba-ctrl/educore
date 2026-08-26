<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Lesson Plan — {{ $lessonPlan->topic }}</title>
<style>
* { box-sizing:border-box; }
body { margin:0; padding:16mm; color:#111827; background:#fff; font-family:Arial,Helvetica,sans-serif; font-size:12pt; line-height:1.55; }
.identity { display:grid; grid-template-columns:repeat(4,1fr); gap:16mm; margin-bottom:10pt; }
.identity-item { border-top:2px solid #111; border-bottom:1px solid #111; padding:4pt 7pt 5pt; min-width:0; }
.identity-item b,.label { font-weight:800; text-transform:uppercase; }
.identity-item span { margin-left:3pt; }
.topic-block { border-top:1px solid #111; border-bottom:1px solid #111; padding:3pt 7pt; }
.topic-block .value { display:block; margin-left:6pt; }
.subtopics { margin:1pt 0 2pt 25pt; padding-left:12pt; }
.subtopics li { margin:0; padding-left:5pt; }
.lesson-meta { display:grid; grid-template-columns:1.2fr 1fr 1fr .9fr; border-bottom:1px solid #111; padding:3pt 7pt 5pt; margin-bottom:22pt; }
.lesson-meta div { min-width:0; }
.lesson-meta b { display:block; font-weight:800; text-transform:uppercase; }
.section { break-inside:avoid; margin:0 0 15pt; padding:0 0 13pt; border-bottom:1px solid #9ca3af; }
.section:last-of-type { border-bottom:0; }
.section-title { margin:0 0 7pt; font-size:12pt; font-weight:800; text-transform:uppercase; }
.section-body { margin:0; white-space:pre-wrap; }
@media (max-width:760px) {
    body { padding:20px; }
    .identity { grid-template-columns:repeat(2,1fr); gap:10px; }
    .lesson-meta { grid-template-columns:repeat(2,1fr); gap:8px; }
}
@media print {
    body { padding:0; }
    @page { size:A4; margin:15mm; }
}
</style>
</head>
<body>
@php
    $subtopics = collect(preg_split('/[,;\r\n]+/', (string) $lessonPlan->subtopic, -1, PREG_SPLIT_NO_EMPTY))->map(fn($item) => trim($item))->filter();
@endphp

<header class="identity">
    <div class="identity-item"><b>Class:</b><span>{{ $lessonPlan->classLevel->name }}{{ $lessonPlan->classArm ? ' '.$lessonPlan->classArm->name : '' }}</span></div>
    <div class="identity-item"><b>Subject:</b><span>{{ $lessonPlan->subject->name }}</span></div>
    <div class="identity-item"><b>Week:</b><span>{{ $lessonPlan->week_number ?: '—' }}</span></div>
    <div class="identity-item"><b>Lesson:</b><span>{{ $lessonPlan->lesson_number ?: '1' }}</span></div>
</header>

<section class="topic-block">
    <div><span class="label">Topic</span><span class="value">{{ $lessonPlan->topic }}</span></div>
    @if($subtopics->isNotEmpty())
        <div><span class="label">Sub-topic</span><ul class="subtopics">@foreach($subtopics as $subtopic)<li>{{ $subtopic }}</li>@endforeach</ul></div>
    @endif
</section>

<section class="lesson-meta">
    <div><b>Time</b><span>{{ $lessonPlan->lesson_time ?: '—' }}</span></div>
    <div><b>Duration</b><span>{{ $lessonPlan->duration_minutes }} minutes</span></div>
    <div><b>Average Age</b><span>{{ $lessonPlan->average_age ?: '—' }}</span></div>
    <div><b>Sex</b><span>{{ $lessonPlan->sex ?: 'Mixed' }}</span></div>
</section>

@foreach($lessonPlan->sections() as $field => $label)
    @php $sectionValue = $lessonPlan->sectionValue($field); @endphp
    @if($sectionValue)
        <section class="section">
            <h2 class="section-title">{{ $label }}</h2>
            <div class="section-body">{{ $sectionValue }}</div>
        </section>
    @endif
@endforeach

<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
