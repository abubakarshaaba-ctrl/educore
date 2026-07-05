@extends('layouts.app')
@section('title', 'Academic Transcript — '.$student->full_name)
@section('page-title', 'Student Transcript')

@push('styles')
<style>
.transcript-shell{background:#fff;border:1px solid #9aa8b6;box-shadow:0 2px 8px rgba(23,54,93,.06)}
.transcript-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.transcript-toolbar__left{display:flex;align-items:center;gap:10px}
.transcript-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 15px;border-radius:7px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid #9aa8b6}
.transcript-btn--ghost{background:#fff;color:#17365d}
.transcript-btn--primary{background:#17365d;color:#fff;border-color:#17365d}
.transcript-name{font-size:17px;font-weight:800;color:#17365d}
.transcript-meta{margin-top:2px;font-size:11px;color:#52606d}
.transcript-head{display:grid;grid-template-columns:86px 1fr;border-bottom:1px solid #9aa8b6}
.transcript-logo{display:flex;align-items:center;justify-content:center;padding:12px;border-right:1px solid #cbd5e1;background:#fbfcfe}
.transcript-logo img{max-width:58px;max-height:58px;object-fit:contain}
.transcript-logo__fallback{width:52px;height:52px;border:2px solid #1f4e79;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#1f4e79;font-size:22px;font-weight:800}
.transcript-brand{text-align:center;padding:12px 18px}
.transcript-school{font-size:20px;line-height:1.1;font-weight:800;color:#17365d;text-transform:uppercase}
.transcript-contact{margin-top:4px;font-size:11px;color:#52606d}
.transcript-title{margin-top:7px;padding:5px;border-top:1px solid #7f93a8;border-bottom:1px solid #7f93a8;color:#17365d;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}
.transcript-identity{display:grid;grid-template-columns:repeat(5,1fr);border-bottom:1px solid #9aa8b6}
.transcript-identity__item{padding:8px 10px;border-right:1px solid #e1e6eb;min-width:0}
.transcript-identity__item:last-child{border-right:0}
.transcript-label{display:block;font-size:10px;font-weight:800;color:#52606d;text-transform:uppercase;letter-spacing:.04em}
.transcript-value{display:block;margin-top:2px;font-size:12px;font-weight:700;color:#172033;overflow-wrap:anywhere}
.transcript-section{padding:7px 10px;border-bottom:1px solid #9aa8b6;background:#edf3f8;color:#17365d;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em}
.transcript-table-wrap{overflow-x:auto}
.transcript-table{width:100%;min-width:920px;border-collapse:collapse;table-layout:fixed;font-size:11px}
.transcript-table col.subject-col{width:22%}
.transcript-table th,.transcript-table td{border:1px solid #cbd5df;padding:8px 6px;text-align:center;vertical-align:middle}
.transcript-table th{font-weight:800}
.transcript-table th.subject-head{background:#edf3f8;color:#17365d;text-align:left;padding-left:10px}
.transcript-table th.session-head{background:#17365d;color:#fff;font-size:11px;letter-spacing:.03em}
.transcript-table th.class-head{background:#22456e;color:#cfdcec;font-size:10px;font-weight:500;font-style:italic;text-transform:none}
.transcript-table th.term-head{background:#f3f6f9;color:#243b53;font-size:10px;text-transform:uppercase}
.transcript-table td.subject-cell{text-align:left;padding-left:10px;font-weight:700;color:#243b53;background:#fbfcfe}
.transcript-table tbody tr:nth-child(even) td:not(.subject-cell){background:#fafbfd}
.transcript-table td.score-cell{font-weight:700;color:#172033}
.transcript-table td.nil{color:#b9c3cd;font-weight:400}
.transcript-table tr.total-row td{background:#edf3f8;color:#17365d;font-weight:800;border-top:2px solid #8fa0b2}
.transcript-table tr.total-row td:first-child{text-align:left;padding-left:10px;text-transform:uppercase;font-size:10px;color:#52606d}
.transcript-empty{padding:36px;text-align:center;color:#52606d;background:#fff;border:1px solid #9aa8b6}
@media(max-width:900px){.transcript-identity{grid-template-columns:repeat(2,1fr)}.transcript-identity__item{border-bottom:1px solid #e1e6eb}.transcript-head{grid-template-columns:72px 1fr}.transcript-school{font-size:16px}}
</style>
@endpush

@section('content')
@php
    $logoUrl = !empty($tenant->logo_path) ? asset('storage/'.ltrim(preg_replace('#^storage/#','',$tenant->logo_path),'/')) : null;
    $chunks = $bySession->chunk(3)->values();
@endphp

<div class="transcript-toolbar">
    <div class="transcript-toolbar__left">
        <a href="{{ route('students.transcript.index') }}" class="transcript-btn transcript-btn--ghost">← Transcripts</a>
        <div>
            <div class="transcript-name">{{ $student->full_name }}</div>
            <div class="transcript-meta">{{ $student->admission_number }}</div>
        </div>
    </div>
    <a href="{{ route('students.transcript.pdf', $student) }}" class="transcript-btn transcript-btn--primary" target="_blank">Download Transcript PDF</a>
</div>

@forelse($chunks as $chunkIndex => $chunk)
@php
    $sessions = [];
    foreach ($chunk as $sessionSummaries) {
        $slots = $sessionSummaries->sortBy('term_id')->values()->all();
        while (count($slots) < 3) $slots[] = null;
        $slots = array_slice($slots, 0, 3);
        $sessions[] = [
            'name' => optional($sessionSummaries->first()?->session)->name ?? 'Session',
            'class' => trim(optional(optional($sessionSummaries->first()?->classArm)->classLevel)->name.' '.optional($sessionSummaries->first()?->classArm)->name),
            'slots' => $slots,
        ];
    }
    while (count($sessions) < 3) $sessions[] = ['name' => '—', 'class' => '', 'slots' => [null,null,null]];

    $chunkSubjects = $allSubjects->filter(function ($name, $sid) use ($sessions, $scoresByTerm) {
        foreach ($sessions as $session) {
            foreach ($session['slots'] as $summary) {
                if ($summary && isset($scoresByTerm[$summary->term_id][$sid])) return true;
            }
        }
        return false;
    });
@endphp

<div class="transcript-shell" style="margin-bottom:22px">
    <div class="transcript-head">
        <div class="transcript-logo">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="School logo">
            @else
                <div class="transcript-logo__fallback">{{ strtoupper(mb_substr($tenant->name ?? 'S',0,1)) }}</div>
            @endif
        </div>
        <div class="transcript-brand">
            <div class="transcript-school">{{ $tenant->name }}</div>
            <div class="transcript-contact">{{ $tenant->address ?? '' }}@if(!empty($tenant->phone)) · {{ $tenant->phone }}@endif</div>
            <div class="transcript-title">Academic Transcript</div>
        </div>
    </div>

    <div class="transcript-identity">
        <div class="transcript-identity__item"><span class="transcript-label">Student</span><span class="transcript-value">{{ strtoupper($student->full_name) }}</span></div>
        <div class="transcript-identity__item"><span class="transcript-label">Admission No.</span><span class="transcript-value">{{ $student->admission_number }}</span></div>
        <div class="transcript-identity__item"><span class="transcript-label">Current Class</span><span class="transcript-value">{{ trim(optional(optional($student->currentClassArm)->classLevel)->name.' '.optional($student->currentClassArm)->name) ?: '—' }}</span></div>
        <div class="transcript-identity__item"><span class="transcript-label">Gender</span><span class="transcript-value">{{ ucfirst($student->gender ?? '—') }}</span></div>
        <div class="transcript-identity__item"><span class="transcript-label">Status</span><span class="transcript-value">{{ ucfirst($student->status ?? '—') }}</span></div>
    </div>

    <div class="transcript-section">Academic Performance by Subject @if($chunks->count() > 1) — Sheet {{ $chunkIndex + 1 }} of {{ $chunks->count() }} @endif</div>

    <div class="transcript-table-wrap">
        <table class="transcript-table">
            <colgroup>
                <col class="subject-col">
                @for($i=0;$i<9;$i++)<col>@endfor
            </colgroup>
            <thead>
                <tr>
                    <th class="subject-head" rowspan="3">Subject</th>
                    @foreach($sessions as $session)
                        <th class="session-head" colspan="3">{{ $session['name'] }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($sessions as $session)
                        <th class="class-head" colspan="3">{{ $session['class'] ?: '—' }}</th>
                    @endforeach
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
                @foreach($chunkSubjects as $subjectId => $subjectName)
                    <tr>
                        <td class="subject-cell">{{ $subjectName }}</td>
                        @foreach($sessions as $session)
                            @foreach($session['slots'] as $summary)
                                @php $score = $summary ? data_get($scoresByTerm, $summary->term_id.'.'.$subjectId.'.total') : null; @endphp
                                <td class="{{ $score === null ? 'nil' : 'score-cell' }}">{{ $score === null ? '—' : number_format((float)$score, 0) }}</td>
                            @endforeach
                        @endforeach
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>Term Total Score</td>
                    @foreach($sessions as $session)
                        @foreach($session['slots'] as $summary)
                            <td>{{ $summary && ($summary->total_score ?? null) !== null ? number_format((float)$summary->total_score, 0) : '—' }}</td>
                        @endforeach
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="transcript-empty">No academic records found for this student.</div>
@endforelse
@endsection
