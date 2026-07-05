@extends('layouts.app')
@section('title', 'Academic Transcript — '.$student->full_name)
@section('page-title', 'Student Transcript')

@push('styles')
<style>
.profile-row{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.pstat{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px;flex:1;min-width:120px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.pstat .val{font-size:22px;font-weight:800;color:var(--midnight)}
.pstat .lbl{font-size:10px;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:3px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-primary{background:var(--indigo);color:white}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.tcard{background:white;border:1px solid #9aa8b6;border-radius:8px;overflow:hidden;margin-bottom:24px;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.tcard-head{padding:12px 18px;border-bottom:1px solid #9aa8b6;background:#edf3f8;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.tcard-title{font-size:13px;font-weight:700;color:#17365d;text-transform:uppercase;letter-spacing:.04em}
.trx{overflow-x:auto}

/* Transcript table — same shades as the report card PDF */
table.sc{width:100%;border-collapse:collapse;font-size:13px;min-width:900px;table-layout:fixed}
table.sc col.c-subject{width:19%}
table.sc th{padding:7px 6px;border:1px solid #aebbc9;background:#f3f6f9;color:#243b53;font-size:10.5px;font-weight:700;text-align:center;text-transform:uppercase;line-height:1.25}
table.sc th.session-head{background:#17365d;color:#fff;font-size:12px;letter-spacing:.04em}
table.sc th.class-head{background:#22456e;color:#cfdcec;font-weight:400;font-style:italic;font-size:11px;text-transform:none}
table.sc th.term-head{background:#edf3f8;color:#17365d;font-size:11px}
table.sc th.subject-head{text-align:left;padding-left:10px;background:#edf3f8;color:#17365d;font-size:11.5px}
table.sc td{padding:7px 6px;border:1px solid #cbd5df;color:#172033;text-align:center;vertical-align:middle}
table.sc td.subject{text-align:left;padding-left:10px;font-weight:600;color:#243b53;background:#fbfcfe}
.good{color:#16794b}
.risk{color:#b42318}
.nil{color:#b9c3cd}
table.sc tr.summary-row td{background:#edf3f8;font-weight:700;color:#17365d;border-top:2px solid #8fa0b2}
table.sc tr.summary-row td.summary-label,
table.sc tr.summary-sub td.summary-label{text-align:left;padding-left:10px;font-size:10.5px;text-transform:uppercase;color:#52606d;font-weight:700}
table.sc tr.summary-sub td{background:#f6f9fc;font-weight:600;color:#243b53}
</style>
@endpush

@section('content')
{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <div style="display:flex;align-items:center;gap:10px">
        <a href="{{ route('students.transcript.index') }}" class="btn btn-ghost">← Transcripts</a>
        <div>
            <div style="font-size:16px;font-weight:800;color:var(--midnight)">{{ $student->full_name }}</div>
            <div style="font-size:11px;color:var(--slate-light)">{{ $student->admission_number }} · {{ optional(optional($student->currentClassArm)->classLevel)->name ?? '—' }}</div>
        </div>
    </div>
    <a href="{{ route('students.transcript.pdf', $student) }}" class="btn btn-primary">
        🖨 Download Transcript PDF
    </a>
</div>

{{-- Summary stats --}}
@php
    $totalTerms = $summaries->count();
    $avgOfAvgs  = $totalTerms ? round($summaries->avg('final_average'), 1) : 0;
    $bestTerm   = $summaries->sortByDesc('final_average')->first();
    $pass = 40;
@endphp

<div class="profile-row">
    <div class="pstat"><div class="val">{{ $totalTerms }}</div><div class="lbl">Terms on Record</div></div>
    <div class="pstat"><div class="val">{{ $avgOfAvgs }}%</div><div class="lbl">Overall Average</div></div>
    <div class="pstat"><div class="val">{{ optional($bestTerm)->final_average ?? '—' }}%</div><div class="lbl">Best Term Avg</div></div>
    <div class="pstat">
        <div class="val" style="color:{{ $student->status === 'active' ? '#16794b':'' }}">{{ ucfirst($student->status) }}</div>
        <div class="lbl">Status</div>
    </div>
    <div class="pstat"><div class="val">{{ $allSubjects->count() }}</div><div class="lbl">Subjects Taken</div></div>
</div>

{{-- Three sessions per card; each session = 3 terms x Score --}}
@php $chunks = $bySession->chunk(3)->values(); @endphp

@forelse($chunks as $chunkIndex => $chunk)
@php
    // Pad every session in the chunk to 3 term slots (null = no record)
    $sessions = [];
    foreach ($chunk as $sessionId => $sessionSummaries) {
        $slots = $sessionSummaries->sortBy('term_id')->values()->all();
        while (count($slots) < 3) $slots[] = null;
        $slots = array_slice($slots, 0, 3);
        $sessions[] = [
            'name'  => optional($sessionSummaries->first()?->session)->name ?? 'Session',
            'class' => trim(optional(optional($sessionSummaries->first()?->classArm)->classLevel)->name . ' ' . optional($sessionSummaries->first()?->classArm)->name),
            'slots' => $slots,
        ];
    }

    // Subjects that actually have a score in this chunk
    $chunkSubjects = $allSubjects->filter(function ($name, $sid) use ($sessions, $scoresByTerm) {
        foreach ($sessions as $sess) {
            foreach ($sess['slots'] as $s) {
                if ($s && isset($scoresByTerm[$s->term_id][$sid])) return true;
            }
        }
        return false;
    });
@endphp
<div class="tcard">
    <div class="tcard-head">
        <div class="tcard-title">Academic Performance by Subject @if($chunks->count() > 1) — {{ $chunkIndex + 1 }}/{{ $chunks->count() }} @endif</div>
        <div style="font-size:11px;color:#52606d">Score per term · pass mark {{ $pass }}</div>
    </div>
    <div class="trx">
    <table class="sc">
        <colgroup>
            <col class="c-subject">
        </colgroup>
        <thead>
            {{-- Row 1: session names --}}
            <tr>
                <th class="subject-head" rowspan="3">Subject</th>
                @foreach($sessions as $sess)
                    <th class="session-head" colspan="3">{{ $sess['name'] }}</th>
                @endforeach
            </tr>
            {{-- Row 2: class per session --}}
            <tr>
                @foreach($sessions as $sess)
                    <th class="class-head" colspan="3">{{ $sess['class'] ?: '—' }}</th>
                @endforeach
            </tr>
            {{-- Row 3: term names --}}
            <tr>
                @foreach($sessions as $sess)
                    @foreach($sess['slots'] as $i => $s)
                        <th class="term-head">{{ $s ? optional($s->term)->name : ['1st Term','2nd Term','3rd Term'][$i] }}</th>
                    @endforeach
                @endforeach
            </tr>
        </thead>
        <tbody>
        @foreach($chunkSubjects as $sid => $subjectName)
        <tr>
            <td class="subject">{{ $subjectName }}</td>
            @foreach($sessions as $sess)
                @foreach($sess['slots'] as $s)
                    @php
                        $entry  = $s ? ($scoresByTerm[$s->term_id][$sid] ?? null) : null;
                        $sc     = $entry['total'] ?? null;
                        $isPass = $entry['is_pass'] ?? ($sc !== null && $sc >= $pass);
                    @endphp
                    @if($sc !== null)
                        <td class="{{ $isPass ? 'good' : 'risk' }}">{{ $sc }}</td>
                    @else
                        <td class="nil">—</td>
                    @endif
                @endforeach
            @endforeach
        </tr>
        @endforeach

        {{-- Term summaries pulled from the termly report --}}
        <tr class="summary-row">
            <td class="summary-label">Term Total Score</td>
            @foreach($sessions as $sess)
                @foreach($sess['slots'] as $s)
                    @if($s && (($s->total_score ?? 0) > 0 || ($s->final_average ?? 0) > 0))
                        <td>{{ number_format((float) $s->total_score, 1) }}</td>
                    @else
                        <td class="nil">—</td>
                    @endif
                @endforeach
            @endforeach
        </tr>
        <tr class="summary-sub">
            <td class="summary-label">Term Average (%)</td>
            @foreach($sessions as $sess)
                @foreach($sess['slots'] as $s)
                    @if($s && ($s->final_average ?? 0) > 0)
                        <td class="{{ ($s->final_average ?? 0) >= $pass ? 'good' : 'risk' }}">{{ number_format((float) $s->final_average, 1) }}%</td>
                    @else
                        <td class="nil">—</td>
                    @endif
                @endforeach
            @endforeach
        </tr>
        <tr class="summary-sub">
            <td class="summary-label">Position in Class</td>
            @foreach($sessions as $sess)
                @foreach($sess['slots'] as $s)
                    @if($s && ($s->position_in_class ?? 0) > 0)
                        <td>{{ $s->position_in_class }}/{{ $s->total_students_in_class }}</td>
                    @else
                        <td class="nil">—</td>
                    @endif
                @endforeach
            @endforeach
        </tr>
        </tbody>
    </table>
    </div>
</div>
@empty
<div style="padding:40px;text-align:center;color:var(--slate-light);background:white;border-radius:12px;border:1px solid var(--border)">
    No academic records found for this student.
</div>
@endforelse

@endsection
