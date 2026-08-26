<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Notes — {{ $lessonPlan->topic }}</title>
<style>
* { box-sizing:border-box; }
body { margin:0; padding:15mm; color:#111827; background:#fff; font-family:Arial,Helvetica,sans-serif; font-size:12pt; line-height:1.5; }
.notes-body { min-height:245mm; border-left:3px solid #142d78; border-right:3px solid #142d78; padding:8mm 10mm 14mm; }
.lesson-note-heading { border-bottom:1px solid #9ca3af; padding-bottom:9pt; margin-bottom:12pt; }
.lesson-note-week { text-align:center; font-weight:800; letter-spacing:.04em; margin-bottom:10pt; }
.lesson-note-heading h1 { font-size:12pt; margin:0 0 8pt; text-transform:uppercase; }
.lesson-note-heading h2 { font-size:12pt; margin:7pt 0 2pt; }
.lesson-note-subtopics { margin:2pt 0 0 18pt; padding-left:12pt; }
.lesson-note-section { break-inside:auto; }
.lesson-note-section > h2,.lesson-note-closing h2 { font-size:12pt; font-weight:800; text-decoration:underline; text-underline-offset:2pt; margin:11pt 0 3pt; }
.lesson-note-section h3 { font-size:12pt; margin:7pt 0 2pt; text-decoration:underline; }
.notes-body p { margin:3pt 0; }
.notes-body ul,.notes-body ol { padding-left:20pt; margin:3pt 0; }
.notes-body li { margin:2pt 0; }
.lesson-note-table-wrap { width:100%; overflow:hidden; }
.notes-body table { width:100%; border-collapse:collapse; margin:8pt 0; font-size:10pt; }
.notes-body th { background:#0f2f61; color:#fff; padding:5pt 7pt; text-align:left; }
.notes-body td { padding:4pt 7pt; border:1px solid #94a3b8; }
.worked-example { border-left:3px solid #d99b13; background:#fffbeb; padding:7pt 9pt; margin:7pt 0; }
.lesson-note-diagram { break-inside:avoid; margin:10pt auto; text-align:center; border:1px solid #bfdbfe; padding:7pt; }
.lesson-note-diagram svg { width:100%; max-width:135mm; height:auto; display:block; margin:auto; }
.lesson-note-diagram figcaption { margin-top:3pt; font-size:9pt; font-style:italic; }
.lesson-note-diagram-description { text-align:left; font-size:10pt; }
.lesson-note-reading { text-align:center; margin-top:14pt; }
.document-footer { margin-top:5pt; border-top:2px solid #6b2b2b; padding-top:3pt; font-size:9pt; display:flex; justify-content:space-between; }
@media print {
    body { padding:0; }
    @page { size:A4; margin:12mm; }
}
</style>
</head>
<body>
<main class="notes-body">
    @if($lessonPlan->lesson_notes)
        {!! $lessonPlan->lesson_notes !!}
    @else
        <p style="text-align:center;color:#64748b;padding:40pt">No notes have been generated yet.</p>
    @endif
</main>
<footer class="document-footer">
    <span>{{ mb_strtoupper($lessonPlan->term->name ?? 'TERM') }}/{{ mb_strtoupper($lessonPlan->subject->name) }}/{{ mb_strtoupper($lessonPlan->classLevel->name) }}</span>
    <span>{{ $lessonPlan->topic }}</span>
</footer>
<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
