<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transcript — {{ $student->full_name }}</title>
<style>
@page {
    size: A4 landscape;
    margin: 12.5mm;
}

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
}

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 7.5pt;
    line-height: 1.25;
    color: #0F172A;
    background: #FFFFFF;
}

.no-print {
    display: none !important;
}

/* DOMPDF-safe document header */
table.header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8pt;
    border-bottom: 2pt solid #D79A21;
}

.header-table td {
    padding: 0 0 7pt 0;
    vertical-align: middle;
}

.header-logo-cell {
    width: 50pt;
}

.header-school-cell {
    text-align: left;
}

.header-document-cell {
    width: 190pt;
    text-align: right;
}

.logo-img {
    display: block;
    width: 42pt;
    height: 42pt;
    object-fit: contain;
}

.school-name {
    font-size: 13pt;
    line-height: 1.15;
    font-weight: 700;
    color: #0F172A;
}

.school-sub {
    margin-top: 2pt;
    font-size: 7pt;
    line-height: 1.35;
    color: #64748B;
}

.doc-label {
    font-size: 11pt;
    line-height: 1.2;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #D79A21;
}

.doc-sub {
    margin-top: 2pt;
    font-size: 6.5pt;
    color: #64748B;
}

/* DOMPDF-safe student information panel */
.bio {
    margin-bottom: 8pt;
    border: 0.75pt solid #D9E0E8;
}

.bio-head {
    padding: 4pt 7pt;
    border-bottom: 0.75pt solid #D9E0E8;
    background: #F1F5F9;
    color: #475569;
    font-size: 6.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

table.bio-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
}

.bio-table td {
    width: 25%;
    padding: 5pt 7pt;
    vertical-align: top;
    border-right: 0.5pt solid #D9E0E8;
    border-bottom: 0.5pt solid #D9E0E8;
}

.bio-table tr:last-child td {
    border-bottom: 0;
}

.bio-table td:last-child {
    border-right: 0;
}

.bio-label {
    display: block;
    margin-bottom: 1.5pt;
    color: #94A3B8;
    font-size: 5.8pt;
    font-weight: 700;
    text-transform: uppercase;
}

.bio-value {
    display: block;
    color: #0F172A;
    font-size: 8pt;
    font-weight: 700;
    overflow-wrap: break-word;
}

/* Pages */
.chunk {
    page-break-inside: avoid;
}

.chunk.break-after {
    page-break-after: always;
}

.chunk.last-chunk {
    page-break-after: auto;
}

/* Continuation header */
table.cont-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 7pt;
    border-bottom: 1pt solid #CBD5E1;
}

.cont-table td {
    padding: 0 0 5pt 0;
    vertical-align: bottom;
}

.cont-right {
    text-align: right;
    color: #94A3B8;
    font-size: 6.5pt;
}

.cont-name {
    color: #071E45;
    font-size: 9pt;
    font-weight: 700;
}

.cont-sub {
    margin-top: 1pt;
    color: #64748B;
    font-size: 6.5pt;
}

.sec-lbl {
    margin: 0 0 3pt 0;
    color: #D79A21;
    font-size: 6.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Transcript score table */
table.sc {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    margin-bottom: 6pt;
    font-size: 6.8pt;
}

table.sc thead {
    display: table-header-group;
}

table.sc tr {
    page-break-inside: avoid;
}

table.sc th,
table.sc td {
    border: 0.45pt solid #CBD5E1;
    vertical-align: middle;
}

table.sc th {
    padding: 3pt 2pt;
    background: #F8FAFC;
    color: #64748B;
    text-align: center;
    font-size: 5.6pt;
    line-height: 1.2;
    font-weight: 700;
    text-transform: uppercase;
    overflow-wrap: break-word;
}

table.sc th.sh-subj {
    width: 122pt;
    padding-left: 6pt;
    text-align: left;
    color: #475569;
}

table.sc th.sh-sess {
    background: #FFF8E8;
    color: #B7791F;
    font-size: 6.2pt;
    font-weight: 800;
}

table.sc th.sh-class {
    background: #F1F5F9;
    color: #475569;
    font-size: 5.5pt;
    font-style: italic;
    font-weight: 600;
    text-transform: none;
}

table.sc th.sh-term {
    background: #F8FAFC;
    color: #334155;
    font-size: 5.7pt;
}

table.sc th.sh-metric {
    padding: 2.5pt 1pt;
    background: #FFFFFF;
    color: #64748B;
    font-size: 5.1pt;
}

table.sc td {
    padding: 3pt 2pt;
    background: #FFFFFF;
    text-align: center;
}

table.sc td.td-subj {
    padding-left: 6pt;
    background: #FAFCFF;
    color: #0F172A;
    text-align: left;
    font-weight: 600;
    overflow-wrap: break-word;
}

table.sc tbody tr:nth-child(even) td:not(.td-subj) {
    background: #FCFDFE;
}

table.sc td.td-score {
    font-weight: 700;
}

table.sc td.td-pass {
    color: #047857;
}

table.sc td.td-fail {
    color: #B91C1C;
}

table.sc td.td-nil {
    color: #CBD5E1;
    font-weight: 400;
}

table.sc tr.tr-total td {
    padding-top: 4pt;
    padding-bottom: 4pt;
    border-top: 1pt solid #94A3B8;
    background: #F1F5F9;
    color: #0F172A;
    font-weight: 800;
}

table.sc tr.tr-total td.td-subj {
    color: #475569;
    font-size: 6pt;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.position-text {
    color: #1D4ED8;
    font-size: 5.7pt;
    font-weight: 700;
}

/* Footer stays with the final transcript page */
table.footer-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8pt;
    border-top: 0.75pt dashed #CBD5E1;
}

.footer-table td {
    width: 33.333%;
    padding-top: 5pt;
    color: #94A3B8;
    font-size: 5.8pt;
    vertical-align: top;
}

.footer-center {
    text-align: center;
}

.footer-right {
    text-align: right;
}
</style>
</head>
<body>

@php
    /* ── Logo ────────────────────────────────────── */
    $logoSrc = null;
    if ($tenant && !empty($tenant->logo_path)) {
        $lp = storage_path('app/public/' . ltrim(str_replace('storage/', '', $tenant->logo_path), '/'));
        if (file_exists($lp)) {
            $logoSrc = 'data:' . mime_content_type($lp) . ';base64,' . base64_encode(file_get_contents($lp));
        }
    }

    /* ── Chunk sessions into groups of 3 ─────────── */
    $chunks = $bySession->chunk(3);
@endphp

@foreach($chunks as $chunkIdx => $chunk)
@php
    $isFirstChunk = $loop->first;
    $isLastChunk  = $loop->last;
@endphp
<div class="chunk {{ $isLastChunk ? 'last-chunk' : 'break-after' }}">

    @if($isFirstChunk)
        {{-- ══ PAGE 1 HEADER ════════════════════════════════ --}}
        <table class="header-table">
            <tr>
                @if($logoSrc)
                    <td class="header-logo-cell">
                        <img src="{{ $logoSrc }}" class="logo-img" alt="School logo">
                    </td>
                @endif
                <td class="header-school-cell">
                    <div class="school-name">{{ optional($tenant)->name }}</div>
                    @if(optional($tenant)->address)
                        <div class="school-sub">{{ $tenant->address }}</div>
                    @endif
                    @if(optional($tenant)->phone)
                        <div class="school-sub">{{ $tenant->phone }}</div>
                    @endif
                </td>
                <td class="header-document-cell">
                    <div class="doc-label">Academic Transcript</div>
                    <div class="doc-sub">Official Student Record</div>
                    <div class="doc-sub">Printed: {{ now()->format('d M Y') }}</div>
                </td>
            </tr>
        </table>

        {{-- ══ STUDENT BIO ═══════════════════════════════════ --}}
        <div class="bio">
            <div class="bio-head">Student Information</div>
            <table class="bio-table">
                <tr>
                    <td>
                        <span class="bio-label">Full Name</span>
                        <span class="bio-value">{{ $student->full_name }}</span>
                    </td>
                    <td>
                        <span class="bio-label">Admission No.</span>
                        <span class="bio-value">{{ $student->admission_number }}</span>
                    </td>
                    <td>
                        <span class="bio-label">Current Class</span>
                        <span class="bio-value">
                            {{ trim(optional(optional($student->currentClassArm)->classLevel)->name . ' ' . optional($student->currentClassArm)->name) ?: '—' }}
                        </span>
                    </td>
                    <td>
                        <span class="bio-label">Status</span>
                        <span class="bio-value">{{ ucfirst($student->status ?? '—') }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="bio-label">Date of Birth</span>
                        <span class="bio-value">
                            {{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d M Y') : '—' }}
                        </span>
                    </td>
                    <td>
                        <span class="bio-label">Gender</span>
                        <span class="bio-value">{{ ucfirst($student->gender ?? '—') }}</span>
                    </td>
                    <td>
                        <span class="bio-label">Overall Average</span>
                        <span class="bio-value" style="color: {{ $summaries->avg('final_average') >= 40 ? '#047857' : '#B91C1C' }};">
                            {{ number_format($summaries->avg('final_average'), 1) }}%
                        </span>
                    </td>
                    <td>
                        <span class="bio-label">Terms on Record</span>
                        <span class="bio-value">{{ $summaries->count() }}</span>
                    </td>
                </tr>
            </table>
        </div>
    @else
        {{-- ══ CONTINUATION HEADER ═══════════════════════════ --}}
        <table class="cont-table">
            <tr>
                <td>
                    <div class="cont-name">{{ $student->full_name }}</div>
                    <div class="cont-sub">{{ $student->admission_number }} · Academic Transcript (continued)</div>
                </td>
                <td class="cont-right">{{ optional($tenant)->name }}</td>
            </tr>
        </table>
    @endif

    {{-- ══ SCORES TABLE ═════════════════════════════════════ --}}
    <div class="sec-lbl">Academic Performance by Subject</div>
    <table class="sc">
        <thead>
            {{-- Row 1: session names --}}
            <tr>
                <th class="sh-subj" rowspan="4">Subject</th>
                @foreach($chunk as $sessionId => $sessionSummaries)
                    @php
                        $termCount   = $sessionSummaries->count();
                        $sessionName = optional($sessionSummaries->first()?->session)->name ?? 'Session';
                    @endphp
                    <th class="sh-sess" colspan="{{ $termCount * 2 }}">{{ $sessionName }}</th>
                @endforeach
            </tr>

            {{-- Row 2: class labels --}}
            <tr>
                @foreach($chunk as $sessionId => $sessionSummaries)
                    @php
                        $termCount = $sessionSummaries->count();
                        $classArm  = $sessionSummaries->first()?->classArm;
                        $cls = trim(optional(optional($classArm)->classLevel)->name . ' ' . optional($classArm)->name);
                    @endphp
                    <th class="sh-class" colspan="{{ $termCount * 2 }}">{{ $cls ?: '—' }}</th>
                @endforeach
            </tr>

            {{-- Row 3: term names --}}
            <tr>
                @foreach($chunk as $sessionId => $sessionSummaries)
                    @foreach($sessionSummaries->sortBy('term_id') as $s)
                        <th class="sh-term" colspan="2">{{ optional($s->term)->name }}</th>
                    @endforeach
                @endforeach
            </tr>

            {{-- Row 4: Score / Grade --}}
            <tr>
                @foreach($chunk as $sessionId => $sessionSummaries)
                    @foreach($sessionSummaries->sortBy('term_id') as $s)
                        <th class="sh-metric">Score</th>
                        <th class="sh-metric">Grade</th>
                    @endforeach
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($allSubjects as $sid => $subjectName)
                @php
                    $hasAny = false;
                    foreach ($chunk as $sessionSummaries) {
                        foreach ($sessionSummaries->sortBy('term_id') as $s) {
                            if (isset($scoresByTerm[$s->term_id][$sid])) {
                                $hasAny = true;
                                break 2;
                            }
                        }
                    }
                @endphp

                @if($hasAny)
                    <tr>
                        <td class="td-subj">{{ $subjectName }}</td>
                        @foreach($chunk as $sessionId => $sessionSummaries)
                            @foreach($sessionSummaries->sortBy('term_id') as $s)
                                @php
                                    $entry  = $scoresByTerm[$s->term_id][$sid] ?? null;
                                    $sc     = $entry['total'] ?? null;
                                    $isPass = $entry['is_pass'] ?? false;
                                    $grade  = $entry['grade'] ?? '—';
                                @endphp

                                @if($sc !== null)
                                    <td class="td-score {{ $isPass ? 'td-pass' : 'td-fail' }}">{{ $sc }}</td>
                                    <td class="{{ $isPass ? 'td-pass' : 'td-fail' }}" style="font-weight: 700; font-size: 6.2pt;">
                                        {{ $grade }}
                                    </td>
                                @else
                                    <td class="td-nil">—</td>
                                    <td class="td-nil">—</td>
                                @endif
                            @endforeach
                        @endforeach
                    </tr>
                @endif
            @endforeach

            {{-- Totals, average and position --}}
            <tr class="tr-total">
                <td class="td-subj">Total / Avg / Position</td>
                @foreach($chunk as $sessionId => $sessionSummaries)
                    @foreach($sessionSummaries->sortBy('term_id') as $s)
                        @php
                            $ts      = $s->total_score ?? 0;
                            $avg     = $s->final_average ?? 0;
                            $pos     = $s->position_in_class;
                            $tot     = $s->total_students_in_class;
                            $hasData = $ts > 0 || $avg > 0;
                        @endphp
                        <td colspan="2" style="color: {{ $avg >= 40 ? '#047857' : ($hasData ? '#B91C1C' : '#94A3B8') }};">
                            @if($hasData)
                                {{ $ts }} / {{ number_format($avg, 1) }}%
                                @if($pos)
                                    <br><span class="position-text">Pos {{ $pos }}/{{ $tot }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    @endforeach
                @endforeach
            </tr>
        </tbody>
    </table>

    @if($isLastChunk)
        <table class="footer-table">
            <tr>
                <td>{{ optional($tenant)->name }} · Official Academic Transcript</td>
                <td class="footer-center">Generated: {{ now()->format('d M Y, H:i') }}</td>
                <td class="footer-right">This is a computer-generated document.</td>
            </tr>
        </table>
    @endif
</div>
@endforeach

</body>
</html>
