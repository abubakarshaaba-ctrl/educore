<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Broadsheet — {{ $classArm->classLevel->name }} {{ $classArm->name }} — {{ $term->name }}</title>
<style>
@page { size: A4 landscape; margin: 25mm 22mm 25mm 22mm; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans', 'Arial', sans-serif; font-size:8pt; color:#1E293B; background:#fff; }

/* ── Extra interior gutter so content never sits flush against the
   @page margin boundary — a safety buffer beyond the print margin ── */
.page-safe { padding: 0 5mm; }

/* ── HEADER ── */
.hdr { display:table; width:100%; border-bottom:2pt solid #D79A21; padding-bottom:8pt; margin-bottom:8pt; }
.hdr-logo-cell { display:table-cell; width:56pt; vertical-align:middle; padding-right:10pt; }
.hdr-logo { width:48pt; height:48pt; object-fit:contain; }
.hdr-logo-fallback {
    width:46pt; height:46pt; line-height:46pt; text-align:center;
    border:0.9pt solid #071E45; border-radius:23pt;
    color:#071E45; font-size:20pt; font-weight:700;
}
.hdr-info { display:table-cell; vertical-align:middle; }
.school-name { font-size:13pt; font-weight:700; color:#0F172A; text-transform:uppercase; line-height:1.1; }
.school-contact { font-size:7pt; color:#475569; margin-top:2pt; line-height:1.4; }
.hdr-right { display:table-cell; vertical-align:middle; text-align:right; white-space:nowrap; }
.doc-label { font-size:14pt; font-weight:700; color:#D79A21; letter-spacing:.02em; }
.doc-sub { font-size:8pt; color:#475569; margin-top:3pt; }

/* ── CONTEXT BAR ── */
.ctx { background:#F8FAFC; border:0.5pt solid #E2E8F0; border-radius:4pt; padding:6pt 10pt; margin-bottom:8pt; display:table; width:100%; }
.ctx-left { display:table-cell; vertical-align:middle; }
.ctx-title { font-size:10pt; font-weight:700; color:#071E45; }
.ctx-sub { font-size:7pt; color:#64748B; margin-top:2pt; }
.ctx-right { display:table-cell; vertical-align:middle; text-align:right; font-size:7pt; color:#94A3B8; }

/* ── TABLE ── */
table { width:100%; border-collapse:collapse; table-layout:fixed; }
thead th {
    background:#071E45; color:#fff;
    padding:5pt 4pt; font-size:7pt; font-weight:700;
    text-align:center; border-right:0.5pt solid rgba(255,255,255,0.15);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
thead th.th-student { text-align:left; padding-left:6pt; }
thead th.th-summary { background:#0F2942; }

tbody td {
    padding:5pt 4pt; border-bottom:0.5pt solid #E2E8F0; border-right:0.5pt solid #F1F5F9;
    font-size:8pt; text-align:center; color:#334155;
}
tbody td.td-student { text-align:left; padding-left:6pt; font-weight:600; color:#0F172A; border-right:1pt solid #E2E8F0; }
tbody td.td-student small { display:block; font-size:6.5pt; color:#94A3B8; font-weight:400; margin-top:1pt; }
tbody td.td-pass { color:#047857; font-weight:700; }
tbody td.td-fail { color:#B91C1C; font-weight:700; }
tbody td.td-nil { color:#CBD5E1; }
tbody td.td-total { font-weight:700; color:#0F172A; background:#F8FAFC; }
tbody td.td-avg { font-weight:800; }
tbody td.td-avg.high { color:#047857; }
tbody td.td-avg.mid  { color:#B45309; }
tbody td.td-avg.low  { color:#B91C1C; }
tbody td.td-pos { font-weight:800; color:#D79A21; background:#FEF9EC; }
tbody tr:nth-child(even) td { background:#FAFAFA; }
tbody tr:nth-child(even) td.td-pass { background:#F0FDF4; }
tbody tr:nth-child(even) td.td-fail { background:#FEF2F2; }
tbody tr:nth-child(even) td.td-pos  { background:#FEF9EC; }
tbody tr:nth-child(even) td.td-total { background:#F1F5F9; }

/* Stats footer */
.stats-row td { background:#EFF6FF; border-top:1pt solid #D79A21; font-size:7pt; }
.stats-row td.td-student { font-weight:700; color:#D79A21; background:#EFF6FF; }

/* ── FOOTER ── */
.footer { margin-top:10pt; font-size:7pt; color:#94A3B8; display:table; width:100%; }
.footer-left { display:table-cell; }
.footer-right { display:table-cell; text-align:right; }

/* ── GRADE KEY ── */
.grade-key { margin-top:6pt; font-size:6.5pt; color:#64748B; }
.grade-key span { margin-right:8pt; }
</style>
</head>
<body>
<div class="page-safe">

{{-- ── HEADER ── --}}
<div class="hdr">
    <div class="hdr-logo-cell">
        @if($logoAbsPath)
            <img src="{{ $logoAbsPath }}" class="hdr-logo" alt="School Logo">
        @else
            <div class="hdr-logo-fallback">{{ strtoupper(substr($tenant->name ?? 'E', 0, 1)) }}</div>
        @endif
    </div>
    <div class="hdr-info">
        <div class="school-name">{{ $tenant->name }}</div>
        <div class="school-contact">
            {{ $tenant->address ?? '' }}
            @if(!empty($tenant->phone)) &nbsp;|&nbsp; {{ $tenant->phone }} @endif
            @if(!empty($tenant->email)) &nbsp;|&nbsp; {{ $tenant->email }} @endif
        </div>
    </div>
    <div class="hdr-right">
        <div class="doc-label">CLASS BROADSHEET</div>
        <div class="doc-sub">{{ $term->name }} — {{ $term->session->name ?? '' }}</div>
    </div>
</div>

{{-- ── CONTEXT ── --}}
<div class="ctx">
    <div class="ctx-left">
        <div class="ctx-title">{{ $classArm->classLevel->name }} {{ $classArm->name }}</div>
        <div class="ctx-sub">{{ count($matrix) }} student(s) &nbsp;·&nbsp; {{ $subjects->count() }} subject(s) &nbsp;·&nbsp; Total score per subject</div>
    </div>
    <div class="ctx-right">Generated {{ now()->format('d M Y, g:i A') }}</div>
</div>

{{-- ── TABLE ── --}}
@php
    // Column-width budget: student gets more room, summary columns (Total/Avg/Pos)
    // slightly more than a bare subject column, subjects share what's left evenly —
    // scales automatically whether a class has 6 subjects or 16+.
    $studentPct   = 13;
    $summaryPct   = 8;                              // each of Total / Avg / Pos
    $summaryTotal = $summaryPct * 3;
    $subjectCount = max($subjects->count(), 1);
    $subjectPct   = round((100 - $studentPct - $summaryTotal) / $subjectCount, 3);
@endphp
<table>
    <colgroup>
        <col style="width:{{ $studentPct }}%">
        @for($i = 0; $i < $subjectCount; $i++)
            <col style="width:{{ $subjectPct }}%">
        @endfor
        <col style="width:{{ $summaryPct }}%">
        <col style="width:{{ $summaryPct }}%">
        <col style="width:{{ $summaryPct }}%">
    </colgroup>
    <thead>
        <tr>
            <th class="th-student">#&nbsp; Student</th>
            @foreach($subjects as $i => $subject)
            @php
                // Use stored code; if blank, auto-abbreviate (first letters of each word, max 5 chars)
                $code = !empty(trim($subject->code ?? ''))
                    ? strtoupper(trim($subject->code))
                    : strtoupper(implode('', array_map(fn($w) => $w[0], preg_split('/\s+/', trim($subject->name)))));
                $code = substr($code, 0, 5);
            @endphp
            <th style="{{ $i % 2 === 0 ? 'background:#1a3a5c' : '' }}" title="{{ $subject->name }}">
                {{ $code }}
            </th>
            @endforeach
            <th class="th-summary">Total</th>
            <th class="th-summary">Avg</th>
            <th class="th-summary">Pos</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matrix as $studentId => $row)
        @php
            $p   = (int) $row['position'];
            $m   = $p % 100;
            $sfx = ($m >= 11 && $m <= 13) ? 'th' : match($p % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' };
            $avgClass = $row['average'] >= 70 ? 'high' : ($row['average'] >= 50 ? 'mid' : 'low');
        @endphp
        <tr>
            <td class="td-student">
                {{ $row['student']->full_name }}
                <small>{{ $row['student']->admission_number }}</small>
            </td>
            @foreach($subjects as $subject)
            @php
                $sd  = $row['subjects'][$subject->id] ?? [];
                $hs  = $sd['has_scores'] ?? false;
                $tot = $sd['total'] ?? null;
                $grd = $sd['grade'] ?? '—';
                $pss = $sd['is_pass'] ?? false;
            @endphp
            <td class="{{ $hs ? ($pss ? 'td-pass' : 'td-fail') : 'td-nil' }}">
                @if($hs && $tot !== null)
                    {{ $tot }}<br><span style="font-size:6.5pt;font-weight:400">{{ $grd }}</span>
                @else
                    —
                @endif
            </td>
            @endforeach
            <td class="td-total">{{ number_format($row['total'], 0) }}</td>
            <td class="td-avg {{ $avgClass }}">{{ number_format($row['average'], 1) }}</td>
            <td class="td-pos">{{ $p }}<sup style="font-size:6pt">{{ $sfx }}</sup></td>
        </tr>
        @endforeach

        {{-- Class stats row --}}
        @if(isset($subjectStats))
        <tr class="stats-row">
            <td class="td-student">Class Stats</td>
            @foreach($subjects as $subject)
            @php $st = $subjectStats[$subject->id] ?? []; @endphp
            <td style="font-size:7pt;line-height:1.5">
                <span style="color:#059669;font-weight:700">▲{{ $st['highest'] ?? '—' }}</span>&nbsp;
                <span style="color:#DC2626;font-weight:700">▼{{ $st['lowest'] ?? '—' }}</span><br>
                <span style="color:#6B7280">⌀{{ $st['avg'] ?? '—' }}</span>
            </td>
            @endforeach
            <td colspan="3"></td>
        </tr>
        @endif
    </tbody>
</table>

{{-- ── SUBJECT KEY ── --}}
<div class="grade-key" style="margin-top:8pt;border-top:0.5pt solid #E2E8F0;padding-top:5pt">
    <strong>Subject Key:</strong>
    @foreach($subjects as $subject)
    @php
        $code = !empty(trim($subject->code ?? ''))
            ? strtoupper(trim($subject->code))
            : strtoupper(implode('', array_map(fn($w) => $w[0], preg_split('/\s+/', trim($subject->name)))));
        $code = substr($code, 0, 5);
    @endphp
    <span>{{ $code }} = {{ $subject->name }}</span>
    @endforeach
</div>

{{-- ── FOOTER ── --}}
<div class="footer">
    <div class="footer-left">
        <div class="grade-key">
            <strong>Grade Key:</strong>
            <span>A1 (75–100, Excellent)</span>
            <span>B2 (70–74, Very Good)</span>
            <span>B3 (65–69, Good)</span>
            <span>C4 (60–64, Credit)</span>
            <span>C5 (55–59, Credit)</span>
            <span>C6 (50–54, Credit)</span>
            <span>D7 (45–49, Pass)</span>
            <span>E8 (40–44, Pass)</span>
            <span>F9 (0–39, Fail)</span>
        </div>
    </div>
    <div class="footer-right">
        Generated by EduCore for {{ $tenant->name }}
    </div>
</div>

</div>
</body>
</html>