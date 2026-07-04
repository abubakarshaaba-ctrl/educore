<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Notes — {{ $lessonPlan->topic }}</title>
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #000; background: #fff; padding: 18mm; }
.header { text-align:center; margin-bottom:14pt; padding-bottom:10pt; border-bottom:2px solid #000; }
.header h1 { font-size:14pt; font-weight:bold; text-transform:uppercase; }
.header .meta { font-size:10pt; color:#333; margin-top:4pt; }
.notes-body h1, .notes-body h2 { font-size:12pt; font-weight:bold; border-bottom:1px solid #ccc; padding-bottom:4pt; margin:14pt 0 6pt; text-transform:uppercase; letter-spacing:.04em; }
.notes-body h3 { font-size:11pt; font-weight:bold; margin:10pt 0 4pt; }
.notes-body p  { margin:5pt 0; line-height:1.6; }
.notes-body ul, .notes-body ol { padding-left:18pt; margin:5pt 0; }
.notes-body li { margin:3pt 0; }
.notes-body table { width:100%; border-collapse:collapse; margin:10pt 0; font-size:10pt; }
.notes-body th { background:#333; color:#fff; padding:5pt 8pt; text-align:left; }
.notes-body td { padding:4pt 8pt; border:1px solid #999; }
.notes-body tr:nth-child(even) td { background:#f5f5f5; }
.notes-body svg { max-width:100%; display:block; margin:10pt auto; }
.notes-body figure { margin:12pt 0; text-align:center; }
.notes-body figcaption { font-size:9pt; font-style:italic; margin-top:4pt; color:#555; }
.notes-body blockquote { border-left:3px solid #333; padding-left:10pt; color:#444; margin:8pt 0; }
.footer { margin-top:20pt; border-top:1px solid #000; padding-top:8pt; font-size:9pt; display:flex; justify-content:space-between; }
@media print {
    body { padding:12mm; }
    @page { margin:12mm; }
}
</style>
</head>
<body>

<div class="header">
    @if($lessonPlan->teacher?->tenant)
    <div style="font-size:13pt;font-weight:bold">{{ $lessonPlan->teacher->tenant->name }}</div>
    @endif
    <h1>Student Study Notes</h1>
    <div class="meta">
        <strong>Subject:</strong> {{ $lessonPlan->subject->name }} &nbsp;|&nbsp;
        <strong>Class:</strong> {{ $lessonPlan->classLevel->name }}{{ $lessonPlan->classArm ? ' ' . $lessonPlan->classArm->name : '' }} &nbsp;|&nbsp;
        <strong>Topic:</strong> {{ $lessonPlan->topic }}
        @if($lessonPlan->term) &nbsp;|&nbsp; <strong>Term:</strong> {{ $lessonPlan->term->name }} @endif
        @if($lessonPlan->week_number) &nbsp;|&nbsp; <strong>Week:</strong> {{ $lessonPlan->week_number }} @endif
    </div>
</div>

<div class="notes-body">
    @if($lessonPlan->lesson_notes)
        {!! $lessonPlan->lesson_notes !!}
    @else
        <p style="text-align:center;color:#666;padding:40pt">No notes have been generated yet.</p>
    @endif
</div>

<div class="footer">
    <span>{{ $lessonPlan->subject->name }} — {{ $lessonPlan->topic }}</span>
    <span>{{ $lessonPlan->teacher?->tenant?->name }}</span>
    <span>Printed: {{ now()->format('d M Y') }}</span>
</div>

<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
