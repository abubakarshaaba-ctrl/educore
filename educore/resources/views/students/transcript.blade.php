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
.tcard{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:24px;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.tcard-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.tcard-title{font-size:13px;font-weight:700;color:var(--midnight)}
.trx{overflow-x:auto}
table.sc{width:100%;border-collapse:collapse;font-size:12.5px;min-width:500px}
table.sc th{padding:7px 10px;border:1px solid var(--border);font-size:10px;font-weight:700;text-align:center;white-space:nowrap}
table.sc th.sh-subj{text-align:left;background:#EFF6FF;color:var(--indigo);min-width:150px}
table.sc th.sh-sess{background:var(--midnight);color:white;font-size:11px}
table.sc th.sh-class{background:#0F2942;color:#B8C8DB;font-size:9.5px;font-weight:400;font-style:italic}
table.sc th.sh-term{background:#F8FAFC;color:var(--slate);text-transform:uppercase;letter-spacing:.04em;font-size:9.5px}
table.sc td{padding:8px 10px;border:1px solid #F1F5F9;color:var(--midnight);text-align:center}
table.sc td.td-subj{text-align:left;font-weight:600;background:#FAFBFF;border-right:1px solid var(--border)}
table.sc td.td-pass{color:#059669;font-weight:700}
table.sc td.td-fail{color:#DC2626;font-weight:700}
table.sc td.td-nil{color:#CBD5E1}
.score-grade{display:flex;align-items:baseline;justify-content:center;gap:4px;white-space:nowrap}
.score-grade .grade{font-size:9px;font-weight:800;color:var(--slate-light)}
table.sc td.td-pass .grade{color:#047857}
table.sc td.td-fail .grade{color:#B91C1C}
table.sc tr.tr-total td{background:#F1F5F9;border-top:2px solid var(--border);font-weight:800;color:var(--midnight)}
table.sc tr.tr-total td.td-subj{background:#E2E8F0;color:var(--slate)}
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
        <div class="val" style="color:{{ $student->status === 'active' ? '#059669':'' }}">{{ ucfirst($student->status) }}</div>
        <div class="lbl">Status</div>
    </div>
    <div class="pstat"><div class="val">{{ $allSubjects->count() }}</div><div class="lbl">Subjects Taken</div></div>
</div>

{{-- All sessions in one table per chunk of 3 --}}
@php $chunks = $bySession->chunk(3); @endphp

@forelse($chunks as $chunk)
<div class="tcard">
    <div class="tcard-head">
        <div class="tcard-title">Academic Performance by Subject</div>
        <div style="font-size:11px;color:var(--slate-light)">Raw term totals · pass mark {{ $pass }}</div>
    </div>
    <div class="trx">
    <table class="sc">
        <thead>
            {{-- Row 1: session names --}}
            <tr>
                <th class="sh-subj" rowspan="3">Subject</th>
                @foreach($chunk as $sessionId => $sessionSummaries)
                @php
                    $termCount   = $sessionSummaries->count();
                    $sessionName = optional($sessionSummaries->first()?->session)->name ?? 'Session';
                @endphp
                <th class="sh-sess" colspan="{{ $termCount }}">{{ $sessionName }}</th>
                @endforeach
            </tr>
            {{-- Row 2: class per session --}}
            <tr>
                @foreach($chunk as $sessionId => $sessionSummaries)
                @php
                    $termCount = $sessionSummaries->count();
                    $classArm  = $sessionSummaries->first()?->classArm;
                    $cls = trim(optional(optional($classArm)->classLevel)->name . ' ' . optional($classArm)->name);
                @endphp
                <th class="sh-class" colspan="{{ $termCount }}">{{ $cls ?: '—' }}</th>
                @endforeach
            </tr>
            {{-- Row 3: term names --}}
            <tr>
                @foreach($chunk as $sessionId => $sessionSummaries)
                @foreach($sessionSummaries->sortBy('term_id') as $s)
                <th class="sh-term">{{ optional($s->term)->name }}</th>
                @endforeach
                @endforeach
            </tr>
        </thead>
        <tbody>
        @foreach($allSubjects as $sid => $subjectName)
        @php
            $hasAny = false;
            foreach ($chunk as $sessionSummaries) {
                foreach ($sessionSummaries as $s) {
                    if (isset($scoresByTerm[$s->term_id][$sid])) { $hasAny = true; break 2; }
                }
            }
        @endphp
        @if($hasAny)
        <tr>
            <td class="td-subj">{{ $subjectName }}</td>
            @foreach($chunk as $sessionId => $sessionSummaries)
            @foreach($sessionSummaries->sortBy('term_id') as $s)
            @php
                $entry = $scoresByTerm[$s->term_id][$sid] ?? null;
                $sc = $entry['total'] ?? null;
                $grade = $entry['grade'] ?? '—';
                $isPass = $entry['is_pass'] ?? ($sc >= $pass);
            @endphp
            @if($sc !== null)
                <td class="{{ $isPass ? 'td-pass' : 'td-fail' }}">
                    <span class="score-grade"><span>{{ $sc }}</span><span class="grade">{{ $grade }}</span></span>
                </td>
            @else
                <td class="td-nil">—</td>
            @endif
            @endforeach
            @endforeach
        </tr>
        @endif
        @endforeach

        {{-- Totals row --}}
        <tr class="tr-total">
            <td class="td-subj">Total / Average</td>
            @foreach($chunk as $sessionId => $sessionSummaries)
            @foreach($sessionSummaries->sortBy('term_id') as $s)
            @php
                $ts  = $s->total_score ?? 0;
                $avg = $s->final_average ?? 0;
                $hasScores = $ts > 0 || $avg > 0;
            @endphp
            <td style="color:{{ $avg >= $pass ? '#059669' : ($hasScores ? '#DC2626' : '#94A3B8') }}">
                @if($hasScores){{ $ts }} / {{ number_format($avg, 1) }}%@else—@endif
            </td>
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
