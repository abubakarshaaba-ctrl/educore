<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Lesson Plan - {{ $lessonPlan->topic }}</title>
<style>
*{box-sizing:border-box}html,body{margin:0;padding:0;color:#111;background:#fff}body{font-family:Arial,Helvetica,sans-serif;font-size:12pt;line-height:1.38;padding:14mm 16mm}.top-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:28pt;margin-bottom:10pt}.top-item{border-top:3px solid #111;border-bottom:1px solid #111;padding:5pt 7pt 4pt;white-space:nowrap}.label{font-family:"Arial Narrow",Arial,sans-serif;font-weight:800;text-transform:uppercase}.topic-block{border-top:2px solid #111;border-bottom:2px solid #111;padding:2pt 7pt}.topic-row{margin:0 0 2pt}.topic-value{display:block;padding-left:5pt}.subtopics{margin:0;padding:0 0 0 25pt;list-style-type:square}.facts{display:grid;grid-template-columns:1.2fr 1fr 1fr .9fr;border-bottom:2px solid #111;padding:4pt 7pt 3pt;margin-bottom:20pt}.fact b{display:block;font-family:"Arial Narrow",Arial,sans-serif;text-transform:uppercase}.section{border-bottom:2px solid #aaa;padding:0 0 15pt;margin:0 0 14pt;break-inside:avoid}.section-presentation{break-inside:auto}.section:last-child{border-bottom:0}.section-title{font-family:"Arial Narrow",Arial,sans-serif;font-weight:800;text-transform:uppercase;margin-bottom:9pt}.section-body{white-space:pre-wrap}.presentation-step{border-bottom:2px solid #aaa;padding-bottom:12pt;margin-bottom:12pt;break-inside:avoid}.presentation-step:last-child{border-bottom:0;margin-bottom:0}.step-title{font-family:"Arial Narrow",Arial,sans-serif;font-weight:800;margin-bottom:7pt}.step-list{margin:0;padding-left:27pt;list-style-type:square}.step-list li{padding-left:5pt;margin:3pt 0}.plain-list{margin:0;padding-left:27pt}.plain-list li{padding-left:5pt;margin:5pt 0}@media print{body{padding:0}@page{size:A4;margin:14mm 16mm}header,footer{display:none!important}.section,.presentation-step{orphans:3;widows:3}}
</style></head><body>
@if($lessonPlan->isNerdc())
<div class="top-grid"><div class="top-item"><span class="label">Class:</span> {{ $lessonPlan->classLevel->name }}{{ $lessonPlan->classArm ? ' '.$lessonPlan->classArm->name : '' }}</div><div class="top-item"><span class="label">Subject:</span> {{ $lessonPlan->subject->name }}</div><div class="top-item"><span class="label">Week:</span> {{ $lessonPlan->week_number ?: '-' }}</div><div class="top-item"><span class="label">Lesson:</span> {{ $lessonPlan->lesson_number ?: '-' }}</div></div>
@php
    $subtopics = collect(preg_split('/[,;\r\n]+/', (string) $lessonPlan->subtopic))->map(fn ($value) => trim($value))->filter();
    $presentationSteps = [];
    foreach (preg_split('/\r?\n/', trim((string) $lessonPlan->presentation)) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if (preg_match('/^STEP\s+([IVXLC]+|\d+)\s*:\s*(.+)$/i', $line, $match)) {
            $presentationSteps[] = ['number' => strtoupper($match[1]), 'title' => $match[2], 'items' => []];
        } elseif ($presentationSteps) {
            $presentationSteps[array_key_last($presentationSteps)]['items'][] = preg_replace('/^[\-▪•]\s*/u', '', $line);
        }
    }
@endphp
<div class="topic-block"><div class="topic-row"><span class="label">Topic</span><span class="topic-value">{{ $lessonPlan->topic }}</span></div><div><span class="label">Sub-topic</span>@if($subtopics->count()>1)<ul class="subtopics">@foreach($subtopics as $subtopic)<li>{{ $subtopic }}</li>@endforeach</ul>@else<span class="topic-value">{{ $subtopics->first() ?: '-' }}</span>@endif</div></div>
<div class="facts"><div class="fact"><b>Time</b>{{ $lessonPlan->lesson_time ?: '-' }}</div><div class="fact"><b>Duration</b>{{ $lessonPlan->duration_minutes }} minutes</div><div class="fact"><b>Average Age</b>{{ $lessonPlan->average_age ?: '-' }}</div><div class="fact"><b>Sex</b>{{ $lessonPlan->sex ?: 'Mixed' }}</div></div>
@foreach($lessonPlan->sections() as $field => $label)
@if($lessonPlan->$field)
<section class="section section-{{ str_replace('_','-',$field) }}"><div class="section-title">{{ $label }}</div>
    @if($field === 'presentation')
        @forelse($presentationSteps as $step)
        <div class="presentation-step"><div class="step-title">STEP {{ $step['number'] }}: {{ $step['title'] }}</div><ul class="step-list">
            @foreach($step['items'] as $item)<li>{{ $item }}</li>@endforeach
        </ul></div>
        @empty<div class="section-body">{{ $lessonPlan->presentation }}</div>@endforelse
    @elseif(in_array($field, ['behavioural_objectives', 'evaluation'], true))
        @php
            $content = preg_replace('/^At the end of the lesson, students should be able to:\s*/i', '', $lessonPlan->$field);
            $items = collect(preg_split('/\r?\n/', $content))->map(fn ($item) => trim(preg_replace('/^\s*\d+[.)]\s*/', '', $item)))->filter();
        @endphp
        @if($field === 'behavioural_objectives')<p>At the end of the lesson, students should be able to:</p>@endif
        <ol class="plain-list">@foreach($items as $item)<li>{{ $item }}</li>@endforeach</ol>
    @else
        <div class="section-body">{{ $lessonPlan->$field }}</div>
    @endif
</section>
@endif
@endforeach
@else
<h2 style="text-align:center">Lesson Plan - British Curriculum</h2>@foreach($lessonPlan->sections() as $field=>$label)@if($lessonPlan->$field)<section class="section"><div class="section-title">{{ $label }}</div><div class="section-body">{{ $lessonPlan->$field }}</div></section>@endif @endforeach
@endif
<script>window.onload=function(){window.print()}</script></body></html>
