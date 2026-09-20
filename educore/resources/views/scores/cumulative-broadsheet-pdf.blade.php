<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cumulative Broadsheet</title>
<style>
@page { size:A4 landscape; margin:18mm 16mm; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans','Arial',sans-serif; font-size:7.2pt; color:#1E293B; background:#fff; }
.page-safe { padding:0 3mm; }

.hdr { display:table; width:100%; border-bottom:2pt solid #D79A21; padding-bottom:7pt; margin-bottom:7pt; }
.hdr-logo-cell { display:table-cell; width:54pt; vertical-align:middle; padding-right:8pt; }
.hdr-logo { width:44pt; height:44pt; object-fit:contain; }
.hdr-logo-fallback {
    width:42pt; height:42pt; line-height:42pt; text-align:center;
    border:0.8pt solid #071E45; border-radius:21pt;
    color:#071E45; font-size:18pt; font-weight:700;
}
.hdr-info { display:table-cell; vertical-align:middle; }
.school-name { font-size:12.5pt; font-weight:700; color:#0F172A; text-transform:uppercase; line-height:1.1; }
.school-contact { font-size:6.5pt; color:#475569; margin-top:2pt; line-height:1.35; }
.hdr-right { display:table-cell; vertical-align:middle; text-align:right; white-space:nowrap; }
.doc-label { font-size:12pt; font-weight:700; color:#D79A21; }
.doc-sub { font-size:7pt; color:#475569; margin-top:3pt; }

.ctx { display:table; width:100%; background:#F8FAFC; border:0.5pt solid #E2E8F0; padding:5pt 8pt; margin-bottom:7pt; }
.ctx-left { display:table-cell; vertical-align:middle; }
.ctx-title { font-size:9pt; font-weight:700; color:#071E45; }
.ctx-sub { font-size:6.5pt; color:#64748B; margin-top:2pt; }
.ctx-right { display:table-cell; vertical-align:middle; text-align:right; font-size:6.5pt; color:#94A3B8; }

table { width:100%; border-collapse:collapse; table-layout:fixed; }
thead th {
    background:#071E45; color:#D79A21;
    padding:4pt 3pt; font-size:6.2pt; font-weight:700;
    text-align:center; border-right:0.5pt solid #334E75;
}
thead th.th-student { text-align:left; padding-left:5pt; }
tbody td {
    padding:4pt 3pt; border-bottom:0.5pt solid #E2E8F0;
    border-right:0.5pt solid #F1F5F9;
    text-align:center; font-size:6.6pt; color:#334155;
}
tbody td.td-student { text-align:left; padding-left:5pt; font-weight:600; color:#0F172A; }
tbody td.td-student small { display:block; margin-top:1pt; font-size:5.6pt; color:#94A3B8; font-weight:400; }
tbody td.td-summary { font-weight:700; background:#F8FAFC; color:#0F172A; }
tbody td.td-pos { font-weight:800; color:#B7791F; background:#FEF9EC; }
tbody tr:nth-child(even) td { background:#FAFAFA; }
tbody tr:nth-child(even) td.td-summary { background:#F1F5F9; }
tbody tr:nth-child(even) td.td-pos { background:#FEF9EC; }
.stats-row td { background:#EFF6FF; border-top:1pt solid #D79A21; font-size:5.8pt; line-height:1.35; }

.subject-key { margin-top:6pt; padding-top:4pt; border-top:0.5pt solid #E2E8F0; font-size:5.8pt; color:#64748B; }
.footer { margin-top:6pt; font-size:6pt; color:#94A3B8; text-align:right; }
</style>
</head>
<body>
<div class="page-safe">

<div class="hdr">
    <div class="hdr-logo-cell">
        @if(!empty($logoAbsPath))
            <img src="{{ $logoAbsPath }}" class="hdr-logo" alt="School Logo">
        @else
            <div class="hdr-logo-fallback">{{ strtoupper(substr((string)($tenant->name ?? 'E'),0,1)) }}</div>
        @endif
    </div>
    <div class="hdr-info">
        <div class="school-name">{{ $tenant->name ?? 'School' }}</div>
        <div class="school-contact">
            {{ $tenant->address ?? '' }}
            @if(!empty($tenant->phone)) &nbsp;|&nbsp; {{ $tenant->phone }} @endif
            @if(!empty($tenant->email)) &nbsp;|&nbsp; {{ $tenant->email }} @endif
        </div>
    </div>
    <div class="hdr-right">
        <div class="doc-label">CUMULATIVE CLASS BROADSHEET</div>
        <div class="doc-sub">{{ $session->name ?? '' }}</div>
    </div>
</div>

<div class="ctx">
    <div class="ctx-left">
        <div class="ctx-title">{{ $classArm->classLevel->name ?? 'Class' }} {{ $classArm->name ?? '' }}</div>
        <div class="ctx-sub">{{ $matrix->count() }} learner(s) &nbsp;·&nbsp; {{ $subjects->count() }} subject(s) &nbsp;·&nbsp; {{ $terms->count() }} term(s)</div>
    </div>
    <div class="ctx-right">Generated {{ now()->format('d M Y, g:i A') }}</div>
</div>

@php
    $studentPct = 15;
    $termCount = max($terms->count(), 1);
    $subjectCount = max($subjects->count(), 1);
    $summaryPct = 5;
    $fixedPct = $studentPct + ($summaryPct * 3);
    $dataCount = $termCount + $subjectCount;
    $dataPct = $dataCount > 0 ? round((100 - $fixedPct) / $dataCount, 3) : 0;
@endphp

<table>
    <colgroup>
        <col style="width:{{ $studentPct }}%">
        @for($i=0; $i<$termCount; $i++)<col style="width:{{ $dataPct }}%">@endfor
        @for($i=0; $i<$subjectCount; $i++)<col style="width:{{ $dataPct }}%">@endfor
        <col style="width:{{ $summaryPct }}%">
        <col style="width:{{ $summaryPct }}%">
        <col style="width:{{ $summaryPct }}%">
    </colgroup>
    <thead>
        <tr>
            <th class="th-student">Learner</th>
            @foreach($terms as $index=>$term)
                <th>{{ $index+1 }}T Avg</th>
            @endforeach
            @foreach($subjects as $subject)
                @php
                    $code = trim((string)($subject->code ?? ''));
                    if ($code === '') {
                        $parts = preg_split('/\s+/', trim((string)$subject->name)) ?: [];
                        $code = '';
                        foreach ($parts as $part) {
                            if ($part !== '') $code .= substr($part,0,1);
                        }
                    }
                    $code = strtoupper(substr($code,0,6));
                @endphp
                <th>{{ $code }}</th>
            @endforeach
            <th>Total</th>
            <th>Avg</th>
            <th>Pos</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matrix as $row)
            <tr>
                <td class="td-student">
                    {{ $row['student']->full_name }}
                    <small>{{ $row['student']->admission_number }}</small>
                </td>
                @foreach($terms as $term)
                    @php($termAverage=$row['term_averages'][(int)$term->id] ?? null)
                    <td>{{ $termAverage===null ? '—' : number_format((float)$termAverage,1) }}</td>
                @endforeach
                @foreach($subjects as $subject)
                    @php($subjectData=$row['subjects'][(int)$subject->id] ?? null)
                    <td>
                        @if(($subjectData['average'] ?? null) !== null)
                            {{ number_format((float)$subjectData['average'],1) }}
                            @if(!empty($subjectData['grade']) && $subjectData['grade'] !== '—')
                                <br><span style="font-size:5.4pt;color:#64748B">{{ $subjectData['grade'] }}</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                @endforeach
                <td class="td-summary">{{ number_format((float)$row['total'],1) }}</td>
                <td class="td-summary">{{ $row['average']===null ? '—' : number_format((float)$row['average'],1) }}</td>
                <td class="td-pos">{{ $row['position'] ?? '—' }}</td>
            </tr>
        @endforeach

        <tr class="stats-row">
            <td class="td-student"><strong>Class Stats</strong></td>
            @foreach($terms as $term)<td>—</td>@endforeach
            @foreach($subjects as $subject)
                @php($st=$subjectStats->get((int)$subject->id,[]))
                <td>
                    H {{ $st['highest'] ?? '—' }}<br>
                    L {{ $st['lowest'] ?? '—' }}<br>
                    A {{ $st['avg'] ?? '—' }}
                </td>
            @endforeach
            <td colspan="3"></td>
        </tr>
    </tbody>
</table>

<div class="subject-key">
    <strong>Subject Key:</strong>
    @foreach($subjects as $subject)
        @php
            $code = trim((string)($subject->code ?? ''));
            if ($code === '') {
                $parts = preg_split('/\s+/', trim((string)$subject->name)) ?: [];
                $code = '';
                foreach ($parts as $part) {
                    if ($part !== '') $code .= substr($part,0,1);
                }
            }
            $code = strtoupper(substr($code,0,6));
        @endphp
        {{ $code }} = {{ $subject->name }}@if(!$loop->last) &nbsp;·&nbsp; @endif
    @endforeach
</div>

<div class="footer">Generated by EduCore for {{ $tenant->name ?? 'School' }}</div>

</div>
</body>
</html>
