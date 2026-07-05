<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transcript — {{ $student->full_name }}</title>
<style>
@page { margin: 18pt; }
* { box-sizing: border-box; }
body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #172033; }
table { border-collapse: collapse; }

/* ── Brand header (same palette as reports/pdf.blade.php) ─────────────── */
.report-header { width: 100%; border: 0.35pt solid #9aa8b6; }
.report-header td { vertical-align: middle; }
.brand-logo { width: 64pt; padding: 6pt; text-align: center; border-right: 0.35pt solid #cbd5e1; }
.logo-image { width: 48pt; height: 48pt; object-fit: contain; }
.logo-fallback { width: 46pt; height: 46pt; line-height: 46pt; margin: 0 auto; border: 0.9pt solid #1f4e79; border-radius: 23pt; color: #1f4e79; font-size: 22pt; font-weight: 700; }
.brand-copy { padding: 5pt 10pt; text-align: center; }
.school-name { color: #17365d; font-size: 14pt; line-height: 1.1; font-weight: 700; text-transform: uppercase; }
.school-contact { margin-top: 2pt; color: #52606d; font-size: 7.2pt; line-height: 1.35; }
.document-title { margin-top: 4pt; padding: 3pt 4pt; border-top: 0.35pt solid #7f93a8; border-bottom: 0.35pt solid #7f93a8; color: #17365d; font-size: 8.6pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.55pt; }

/* ── Student identity ─────────────────────────────────────────────────── */
.identity { width: 100%; border: 0.35pt solid #9aa8b6; border-top: 0; table-layout: fixed; }
.identity td { padding: 3.2pt 5pt; border-right: 0.2pt solid #e1e6eb; font-size: 7.7pt; }
.identity td:last-child { border-right: 0; }
.label { color: #52606d; font-size: 6.5pt; font-weight: 700; text-transform: uppercase; }
.value { color: #172033; font-weight: 600; }

/* ── Section bar ──────────────────────────────────────────────────────── */
.section-bar { width: 100%; margin-top: 6pt; table-layout: fixed; }
.section-bar td {
    padding: 3.6pt 5pt;
    border: 0.35pt solid #9aa8b6;
    border-top: 0.65pt solid #6f8499;
    background: #edf3f8;
    color: #17365d;
    font-size: 7.4pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.35pt;
}

/* ── Transcript table ─────────────────────────────────────────────────── */
.academic {
    width: 100%;
    table-layout: fixed;
    border: 0.45pt solid #8fa0b2;
    font-size: 7.5pt;
}
.academic th {
    padding: 3.4pt 2.2pt;
    border: 0.35pt solid #aebbc9;
    background: #f3f6f9;
    color: #243b53;
    text-align: center;
    font-size: 6.6pt;
    text-transform: uppercase;
    line-height: 1.2;
}
.academic th.session-head { background: #17365d; color: #ffffff; font-size: 7.4pt; letter-spacing: 0.4pt; }
.academic th.class-head { background: #22456e; color: #cfdcec; font-weight: 400; font-style: italic; font-size: 6.6pt; text-transform: none; }
.academic th.term-head { background: #edf3f8; color: #17365d; font-size: 6.8pt; }
.academic th.subject-head { text-align: left; padding-left: 5pt; background: #edf3f8; color: #17365d; font-size: 7pt; }
.academic td {
    padding: 3.2pt 2.2pt;
    border: 0.3pt solid #cbd5df;
    text-align: center;
    vertical-align: middle;
}
.academic td.subject { text-align: left; padding-left: 5pt; font-weight: 600; color: #243b53; background: #fbfcfe; }
.academic td.grade { font-weight: 700; background: #fbfcfe; }
.good { color: #16794b; }
.risk { color: #b42318; }
.nil  { color: #b9c3cd; }

.academic tr.summary-row td { background: #edf3f8; font-weight: 700; color: #17365d; border-top: 0.6pt solid #8fa0b2; }
.academic tr.summary-row td.summary-label { text-align: left; padding-left: 5pt; font-size: 6.6pt; text-transform: uppercase; color: #52606d; }
.academic tr.summary-sub td { background: #f6f9fc; font-weight: 600; color: #243b53; }
.academic tr.summary-sub td.summary-label { text-align: left; padding-left: 5pt; font-size: 6.6pt; text-transform: uppercase; color: #52606d; font-weight: 700; }

/* ── Page footer ──────────────────────────────────────────────────────── */
.doc-footer { width: 100%; margin-top: 8pt; border-top: 0.35pt solid #9aa8b6; }
.doc-footer td { padding-top: 3.5pt; color: #52606d; font-size: 6.4pt; }

.page-chunk { page-break-after: always; }
.page-chunk.last { page-break-after: avoid; }
</style>
</head>
<body>
@php
    $logoAbsPath = null;
    if (!empty($tenant->logo_path)) {
        $cleanLogoPath = preg_replace('#^storage/#', '', ltrim($tenant->logo_path, '/'));
        $candidateLogoPath = storage_path('app/public/' . $cleanLogoPath);
        if (file_exists($candidateLogoPath)) $logoAbsPath = $candidateLogoPath;
    }

    $pass = 40;

    // Overall grade for a termly summary, using the grading system of its class level
    $termGrade = function ($summary) use ($gradingSystems) {
        $avg  = (float) ($summary->final_average ?? 0);
        $clId = optional($summary->classArm)->class_level_id;
        $gs   = $clId ? ($gradingSystems[$clId] ?? collect()) : collect();
        $rec  = $gs->sortByDesc('min_score')->first(fn ($g) => $avg >= (float) $g->min_score && $avg <= (float) $g->max_score);
        if ($rec) return $rec->grade_letter;
        return match (true) {
            $avg >= 70 => 'A', $avg >= 60 => 'B', $avg >= 50 => 'C',
            $avg >= 45 => 'D', $avg >= 40 => 'E', default => 'F',
        };
    };

    // 3 sessions per landscape page; each session padded to exactly 3 term slots
    $chunks = $bySession->chunk(3)->values();

    // Column plan: subject 16%, then 3 sessions x 3 terms x 2 cols = 18 equal cols
    $subjectW = 16;
    $colW = round((100 - $subjectW) / 18, 3);
@endphp

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
    // Pad the chunk itself to 3 sessions so every page has identical geometry
    while (count($sessions) < 3) $sessions[] = ['name' => '—', 'class' => '', 'slots' => [null, null, null]];

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
<div class="page-chunk {{ $chunkIndex === $chunks->count() - 1 ? 'last' : '' }}">

    {{-- School header --}}
    <table class="report-header">
        <tr>
            <td class="brand-logo">
                @if($logoAbsPath)
                    <img class="logo-image" src="{{ $logoAbsPath }}" alt="">
                @else
                    <div class="logo-fallback">{{ strtoupper(mb_substr($tenant->name ?? 'S', 0, 1)) }}</div>
                @endif
            </td>
            <td class="brand-copy">
                <div class="school-name">{{ $tenant->name }}</div>
                <div class="school-contact">
                    {{ $tenant->address ?? '' }}@if(!empty($tenant->phone)) · {{ $tenant->phone }}@endif
                </div>
                <div class="document-title">Academic Transcript — Official Student Record</div>
            </td>
        </tr>
    </table>

    {{-- Student identity --}}
    <table class="identity">
        <tr>
            <td><span class="label">Full Name</span><br><span class="value">{{ strtoupper($student->full_name) }}</span></td>
            <td><span class="label">Admission No.</span><br><span class="value">{{ $student->admission_number }}</span></td>
            <td><span class="label">Date of Birth</span><br><span class="value">{{ optional($student->date_of_birth)->format('d M Y') ?? '—' }}</span></td>
            <td><span class="label">Gender</span><br><span class="value">{{ ucfirst($student->gender ?? '—') }}</span></td>
            <td><span class="label">Current Class</span><br><span class="value">{{ trim(optional(optional($student->currentClassArm)->classLevel)->name . ' ' . optional($student->currentClassArm)->name) ?: '—' }}</span></td>
            <td><span class="label">Status</span><br><span class="value">{{ ucfirst($student->status) }}</span></td>
            <td><span class="label">Printed</span><br><span class="value">{{ now()->format('d M Y') }}</span></td>
        </tr>
    </table>

    <table class="section-bar"><tr><td>Academic Performance by Subject @if($chunks->count() > 1) — Page {{ $chunkIndex + 1 }} of {{ $chunks->count() }} @endif</td></tr></table>

    {{-- Transcript table --}}
    <table class="academic">
        <colgroup>
            <col style="width: {{ $subjectW }}%">
            @for($i = 0; $i < 18; $i++)<col style="width: {{ $colW }}%">@endfor
        </colgroup>
        <thead>
            <tr>
                <th class="subject-head" rowspan="4">Subject</th>
                @foreach($sessions as $sess)
                    <th class="session-head" colspan="6">{{ $sess['name'] }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach($sessions as $sess)
                    <th class="class-head" colspan="6">{{ $sess['class'] ?: '—' }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach($sessions as $sess)
                    @foreach($sess['slots'] as $i => $s)
                        <th class="term-head" colspan="2">{{ $s ? optional($s->term)->name : ['1st Term','2nd Term','3rd Term'][$i] }}</th>
                    @endforeach
                @endforeach
            </tr>
            <tr>
                @foreach($sessions as $sess)
                    @foreach($sess['slots'] as $s)
                        <th>Score</th>
                        <th>Grade</th>
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
                            $grade  = $entry['grade'] ?? null;
                            $isPass = $entry['is_pass'] ?? ($sc !== null && $sc >= $pass);
                        @endphp
                        @if($sc !== null)
                            <td class="{{ $isPass ? 'good' : 'risk' }}">{{ $sc }}</td>
                            <td class="grade {{ $isPass ? 'good' : 'risk' }}">{{ $grade }}</td>
                        @else
                            <td class="nil">—</td>
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
                            <td colspan="2">{{ number_format((float) $s->total_score, 1) }}</td>
                        @else
                            <td colspan="2" class="nil">—</td>
                        @endif
                    @endforeach
                @endforeach
            </tr>
            <tr class="summary-sub">
                <td class="summary-label">Term Average (%)</td>
                @foreach($sessions as $sess)
                    @foreach($sess['slots'] as $s)
                        @if($s && ($s->final_average ?? 0) > 0)
                            <td colspan="2" class="{{ ($s->final_average ?? 0) >= $pass ? 'good' : 'risk' }}">{{ number_format((float) $s->final_average, 1) }}%</td>
                        @else
                            <td colspan="2" class="nil">—</td>
                        @endif
                    @endforeach
                @endforeach
            </tr>
            <tr class="summary-sub">
                <td class="summary-label">Term Grade</td>
                @foreach($sessions as $sess)
                    @foreach($sess['slots'] as $s)
                        @if($s && ($s->final_average ?? 0) > 0)
                            <td colspan="2">{{ $termGrade($s) }}</td>
                        @else
                            <td colspan="2" class="nil">—</td>
                        @endif
                    @endforeach
                @endforeach
            </tr>
            <tr class="summary-sub">
                <td class="summary-label">Position in Class</td>
                @foreach($sessions as $sess)
                    @foreach($sess['slots'] as $s)
                        @if($s && ($s->position_in_class ?? 0) > 0)
                            <td colspan="2">{{ $s->position_in_class }} of {{ $s->total_students_in_class }}</td>
                        @else
                            <td colspan="2" class="nil">—</td>
                        @endif
                    @endforeach
                @endforeach
            </tr>
        </tbody>
    </table>

    <table class="doc-footer">
        <tr>
            <td style="text-align:left">{{ $tenant->name }} · Official Academic Transcript</td>
            <td style="text-align:center">Generated {{ now()->format('d M Y, H:i') }}</td>
            <td style="text-align:right">Computer-generated document — no signature required</td>
        </tr>
    </table>
</div>
@empty
<p style="padding:20pt;text-align:center;color:#52606d">No academic records found for this student.</p>
@endforelse
</body>
</html>
