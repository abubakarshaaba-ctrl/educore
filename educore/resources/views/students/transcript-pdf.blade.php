<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transcript — {{ $student->full_name }}</title>
<style>
@page {
    size: A4 landscape;
    margin: 30mm 20mm 30mm 20mm;
}

* {
    box-sizing: border-box;
}

html, body {
    margin: 0;
    padding: 0;
}

body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 8pt;
    line-height: 1.3;
    color: #1E2733;
    background: #FFFFFF;
}

.no-print { display: none !important; }

/* ── Extra interior gutter so content never sits flush against the
   @page margin boundary — a safety buffer beyond the print margin ── */
.page-safe {
    padding: 0 5mm;
}

/* ── Repeating security watermark (renders on every page in dompdf) ── */
.watermark {
    position: fixed;
    top: 42%;
    left: 22%;
    width: 60%;
    text-align: center;
    transform: rotate(-28deg);
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 46pt;
    font-weight: 700;
    letter-spacing: 0.15em;
    color: #1B2A4A;
    opacity: 0.045;
    z-index: -1;
}

/* ══ HEADER ══════════════════════════════════════════════ */
table.header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10pt;
}

.header-table td { vertical-align: middle; padding: 0; }

.seal-cell { width: 58pt; }

.seal-img {
    display: block;
    width: 56pt;
    height: 56pt;
    object-fit: contain;
}

.header-school-cell { text-align: left; padding-left: 11pt; }

.school-name {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 15pt;
    font-weight: 700;
    color: #1B2A4A;
    letter-spacing: 0.01em;
}

.school-sub {
    margin-top: 2pt;
    font-size: 7pt;
    color: #64748B;
}

.header-document-cell { width: 220pt; text-align: right; vertical-align: bottom; }

.doc-badge {
    font-size: 11.5pt;
    font-weight: 700;
    color: #1B2A4A;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.doc-sub {
    margin-top: 2pt;
    font-size: 6.5pt;
    color: #94A3B8;
}

.header-rule-fallback {
    border-bottom: 2pt solid #1B2A4A;
    margin-bottom: 11pt;
}

/* ══ STUDENT BIO PANEL ═══════════════════════════════════ */
.bio {
    margin-bottom: 11pt;
    border: 0.75pt solid #D9E0E8;
}

.bio-head {
    padding: 5pt 10pt;
    border-bottom: 0.75pt solid #D9E0E8;
    background: #F6F8FA;
    color: #1B2A4A;
    font-size: 7pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

table.bio-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
}

.bio-table td {
    width: 25%;
    padding: 7pt 10pt;
    vertical-align: top;
    border-right: 0.5pt solid #E7ECF1;
    border-bottom: 0.5pt solid #E7ECF1;
}

.bio-table tr:last-child td { border-bottom: 0; }
.bio-table td:last-child { border-right: 0; }

.bio-label {
    display: block;
    margin-bottom: 2.5pt;
    color: #9AA5B1;
    font-size: 6pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.bio-value {
    display: block;
    color: #1E2733;
    font-size: 8pt;
    font-weight: 700;
    overflow-wrap: break-word;
}

.avg-pill {
    display: inline-block;
    padding: 2pt 8pt;
    border-radius: 9pt;
    font-size: 7.6pt;
    font-weight: 800;
}
.avg-pass { background: #E6F4EC; color: #06724E; }
.avg-fail { background: #FBEAEA; color: #A3241C; }

/* ══ PAGE CHUNKS ═════════════════════════════════════════ */
.chunk { page-break-inside: avoid; }
.chunk.break-after { page-break-after: always; }
.chunk.last-chunk { page-break-after: auto; }

table.cont-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10pt;
    border-bottom: 1pt solid #D9E0E8;
}
.cont-table td { padding: 0 0 6pt 0; vertical-align: bottom; }
.cont-right { text-align: right; color: #9AA5B1; font-size: 7pt; }
.cont-name { color: #1B2A4A; font-size: 10pt; font-weight: 700; }
.cont-sub { margin-top: 1pt; color: #64748B; font-size: 7pt; }

.sec-lbl {
    margin: 0 0 6pt 0;
    color: #1B2A4A;
    font-size: 7.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

/* ══ SCORE TABLE ═════════════════════════════════════════ */
table.sc {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    margin-bottom: 8pt;
    font-size: 7.6pt;
}
table.sc thead { display: table-header-group; }
table.sc tr { page-break-inside: avoid; }
table.sc th, table.sc td { border: 0.45pt solid #DDE3EA; vertical-align: middle; }

table.sc th {
    padding: 2pt 3pt;
    background: #F6F8FA;
    color: #64748B;
    text-align: center;
    font-size: inherit;
    font-family: inherit;
    line-height: 1.2;
    font-weight: 700;
    text-transform: uppercase;
    overflow-wrap: break-word;
}

table.sc th.sh-subj {
    width: 130pt;
    padding-left: 8pt;
    text-align: left;
    color: #1B2A4A;
    background: #EFF2F6;
}

table.sc th.sh-sess {
    background: #E9ECF0;
    color: #475560;
    font-weight: 800;
    letter-spacing: 0.03em;
    border-bottom: 1pt solid #B9C1CB;
}

table.sc th.sh-class {
    background: #F1F3F5;
    color: #64748B;
    font-style: italic;
    font-weight: 600;
    text-transform: none;
}

table.sc th.sh-term {
    background: #F6F8FA;
    color: #334155;
}

table.sc td {
    padding: 2pt 3pt;
    background: #FFFFFF;
    text-align: center;
    font-weight: 400;
}

table.sc td.td-subj {
    padding-left: 8pt;
    background: #FAFBFD;
    color: #1E2733;
    text-align: left;
    font-weight: 400;
    overflow-wrap: break-word;
}

table.sc tbody tr:nth-child(even) td:not(.td-subj) { background: #FBFCFD; }

table.sc td.td-score { font-weight: 400; color: #1E2733; }
table.sc td.td-pass { color: #1E2733; }
table.sc td.td-fail { color: #1E2733; }
table.sc td.td-nil { color: #C7CDD5; font-weight: 400; }

table.sc tr.tr-total td {
    padding-top: 3pt;
    padding-bottom: 3pt;
    border-top: 1.5pt solid #1B2A4A;
    background: #EFF2F6;
    color: #1E2733;
    font-weight: 700;
}
table.sc tr.tr-total td.td-subj {
    color: #1B2A4A;
    font-size: 6.8pt;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.position-badge {
    display: inline-block;
    margin-top: 1.5pt;
    padding: 1pt 6pt;
    border-radius: 7pt;
    background: #E9ECF0;
    color: #475560;
    font-size: 6.4pt;
    font-weight: 700;
}

/* ══ FOOTER / SIGNATURES ═════════════════════════════════ */
.sign-block {
    width: 100%;
    margin-top: 20pt;
    border-collapse: collapse;
}
.sign-block td {
    text-align: center;
    vertical-align: bottom;
}
.sign-line {
    display: inline-block;
    width: 220pt;
    border-top: 0.75pt solid #94A3B8;
    padding-top: 4pt;
    font-size: 6.8pt;
    color: #64748B;
}

table.footer-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12pt;
    border-top: 0.75pt dashed #D9E0E8;
}
.footer-table td {
    width: 33.333%;
    padding-top: 6pt;
    color: #9AA5B1;
    font-size: 6pt;
    vertical-align: top;
}
.footer-center { text-align: center; }
.footer-right { text-align: right; }
</style>
</head>
<body>

<div class="watermark">OFFICIAL</div>
<div class="page-safe">

@php
    /* ── Logo ────────────────────────────────────── */
    $logoSrc = null;
    if ($tenant && !empty($tenant->logo_path)) {
        $lp = storage_path('app/public/' . ltrim(str_replace('storage/', '', $tenant->logo_path), '/'));
        if (file_exists($lp)) {
            $logoSrc = 'data:' . mime_content_type($lp) . ';base64,' . base64_encode(file_get_contents($lp));
        }
    }

    /* ── Chunk sessions into column-groups of 3 ─────────── */
    $chunks = $bySession->chunk(3);

    /* ── Chunk subjects into row-groups of at most 13 per page ─── */
    $subjectPages = collect($allSubjects)->chunk(13);
@endphp

@foreach($chunks as $chunkIdx => $chunk)
    @foreach($subjectPages as $pageIdx => $subjectPage)
    @php
        $isVeryFirstPage = $loop->parent->first && $loop->first;
        $isVeryLastPage  = $loop->parent->last && $loop->last;
        $isLastSubjectPageForColumnGroup = $loop->last;
    @endphp
    <div class="chunk {{ $isVeryLastPage ? 'last-chunk' : 'break-after' }}">

    @if($isVeryFirstPage)
        {{-- ══ PAGE 1 HEADER ════════════════════════════════ --}}
        <table class="header-table">
            <tr>
                @if($logoSrc)
                    <td class="seal-cell">
                        <img src="{{ $logoSrc }}" class="seal-img" alt="School logo">
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
                    <div class="doc-badge">Academic Transcript</div>
                    <div class="doc-sub">Official Student Record · Printed {{ now()->format('d M Y') }}</div>
                </td>
            </tr>
        </table>
        <div class="header-rule-fallback"></div>

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
                        <span class="bio-value">
                            <span class="avg-pill {{ $summaries->avg('final_average') >= 40 ? 'avg-pass' : 'avg-fail' }}">
                                {{ number_format($summaries->avg('final_average'), 1) }}%
                            </span>
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
                <th class="sh-subj" rowspan="3">Subject</th>
                @foreach($chunk as $sessionId => $sessionSummaries)
                    @php
                        $termCount   = $sessionSummaries->count();
                        $sessionName = optional($sessionSummaries->first()?->session)->name ?? 'Session';
                    @endphp
                    <th class="sh-sess" colspan="{{ $termCount }}">{{ $sessionName }}</th>
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
            @foreach($subjectPage as $sid => $subjectName)
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
                                @endphp

                                @if($sc !== null)
                                    <td class="td-score {{ $isPass ? 'td-pass' : 'td-fail' }}">{{ $sc }}</td>
                                @else
                                    <td class="td-nil">—</td>
                                @endif
                            @endforeach
                        @endforeach
                    </tr>
                @endif
            @endforeach

            {{-- Totals, average and position (only once, after the last subject page for this column group) --}}
            @if($isLastSubjectPageForColumnGroup)
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
                        <td style="color: {{ $hasData ? '#1E2733' : '#94A3B8' }};">
                            @if($hasData)
                                {{ $ts }} / {{ number_format($avg, 1) }}%
                                @if($pos)
                                    <br><span class="position-badge">Pos {{ $pos }}/{{ $tot }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    @endforeach
                @endforeach
            </tr>
            @endif
        </tbody>
    </table>

    @if($isVeryLastPage)
        <table class="sign-block">
            <tr>
                <td><div class="sign-line">Principal</div></td>
            </tr>
        </table>

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
@endforeach

</div>
</body>
</html>
