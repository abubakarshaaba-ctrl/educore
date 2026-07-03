@extends('layouts.app')
@section('title', 'Broadsheet')
@section('page-title', 'Score Entry')

@push('styles')
<style>
.page-tabs{display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content}
.page-tab{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms}
.page-tab.active{background:var(--indigo);color:white}
.page-tab:hover:not(.active){background:#F1F5F9}
.filter-card{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 20px;margin-bottom:16px;display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap}
.filter-group{display:flex;flex-direction:column;gap:5px}
.filter-label{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em}
.filter-control{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;min-width:200px}
.filter-control:focus{border-color:var(--indigo)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms}
.btn-primary{background:var(--indigo);color:white}
.btn-primary:hover{background:#1D4ED8}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.context-bar{background:white;border:1px solid var(--border);border-radius:10px;padding:12px 20px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.context-bar h2{font-size:14px;font-weight:700;color:var(--midnight)}
.context-bar p{font-size:12px;color:var(--slate);margin-top:2px}
.sheet-outer{overflow-x:auto;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
table{width:100%;border-collapse:collapse;white-space:nowrap;background:white}
thead th{background:var(--midnight);color:white;padding:9px 12px;font-size:11px;font-weight:700;text-align:center;border-right:1px solid rgba(255,255,255,0.1)}
thead th.left{text-align:left}
tbody td{padding:8px 10px;border-bottom:1px solid var(--border);border-right:1px solid #F1F5F9;font-size:12px;text-align:center;color:var(--midnight)}
tbody td.student-cell{text-align:left;padding-left:12px;min-width:160px;background:#FAFBFF;position:sticky;left:0;z-index:1;border-right:2px solid var(--border)}
tbody tr:hover td{background:#FAFBFF}
tbody tr:hover td.student-cell{background:#EFF6FF}
.student-name{font-weight:600;color:var(--midnight)}
.student-adm{font-size:10px;color:var(--slate-light)}
.total-col{font-weight:700}
.grade-pass{color:var(--emerald);font-weight:700}
.grade-fail{color:var(--crimson);font-weight:700}
.avg-high{color:var(--emerald);font-weight:800;background:#F0FDF4}
.avg-mid{color:var(--amber);font-weight:800;background:#FFFBEB}
.avg-low{color:var(--crimson);font-weight:800;background:#FEF2F2}
.pos-col{font-weight:800;color:var(--indigo);background:var(--indigo-bg)}
.sep{border-right:2px solid var(--border) !important}
.empty-state{background:white;border:1px solid var(--border);border-radius:12px;text-align:center;padding:50px 20px;color:var(--slate-light)}
.empty-state h3{font-size:15px;font-weight:600;color:var(--slate);margin-bottom:6px}
.num-col{min-width:36px}
/* Subject header alternating colour */
.sh-even{background:#1a3a5c}
.sh-odd{background:var(--midnight)}
@media print{
    @page{size:A4 landscape;margin:12mm}
    .page-tabs,.filter-card,.btn-primary,.btn-ghost,.context-bar .btn{display:none!important}
    .sheet-outer{border:none;box-shadow:none;overflow:visible}
    body{font-size:9pt;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    table{width:100%;table-layout:auto}
    thead th{font-size:8pt;padding:6pt 5pt;background:#071E45!important;color:#fff!important;-webkit-print-color-adjust:exact}
    tbody td{font-size:9pt;padding:5pt 5pt}
    tbody td.student-cell{position:static;font-size:9pt}
    .grade-pass{color:#047857!important}
    .grade-fail{color:#B91C1C!important}
    .pos-col{color:#D79A21!important}
    .print-header{display:block!important}
    .context-bar{display:none!important}
}
.print-header{display:none;text-align:center;margin-bottom:10px;border-bottom:2px solid #071E45;padding-bottom:8px}
.print-header .ph-school{font-size:15pt;font-weight:800;color:#071E45}
.print-header .ph-sub{font-size:10pt;color:#475569;margin-top:3px}
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('scores.index') }}"            class="page-tab">Score Entry</a>
    <a href="{{ route('scores.broadsheet') }}"       class="page-tab active">Broadsheet</a>
    @if(auth()->user()->canAccessExactModule('scores'))
    <a href="{{ route('scores.assessment-types') }}" class="page-tab">Assessment Types</a>
    @endif
</div>

<form method="GET">
    <div class="filter-card">
        <div class="filter-group">
            <span class="filter-label">Class</span>
            <select name="class_arm_id" class="filter-control" required>
                <option value="">Select class</option>
                @foreach($classArms as $arm)
                <option value="{{ $arm->id }}" {{ request('class_arm_id') == $arm->id ? 'selected' : '' }}>
                    {{ $arm->classLevel->name }} {{ $arm->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <span class="filter-label">Term</span>
            <select name="term_id" class="filter-control" required>
                <option value="">Select term</option>
                @foreach($terms as $termOpt)
                <option value="{{ $termOpt->id }}" {{ request('term_id') ? (request('term_id') == $termOpt->id ? 'selected' : '') : ($termOpt->is_current ? 'selected' : '') }}>
                    {{ $termOpt->name }} — {{ $termOpt->session->name ?? '' }}
                </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            {{ auth()->user()->canAccessExactModule('scores') ? 'Generate Broadsheet' : 'View Broadsheet' }}
        </button>
        @if(isset($matrix) && count($matrix))
        <a href="{{ route('scores.broadsheet.pdf', ['class_arm_id' => request('class_arm_id'), 'term_id' => request('term_id')]) }}"
           class="btn btn-ghost" target="_blank">↓ PDF</a>
        <button type="button" onclick="window.print()" class="btn btn-ghost">🖨 Print</button>
        @endif
    </div>
</form>

@if(isset($matrix))
    @if(count($matrix) === 0)
    <div class="empty-state">
        <h3>No score data found</h3>
        <p>Enter scores for this class and term first, then return here.</p>
    </div>
    @else
    <div class="context-bar">
        <div>
            <h2>{{ $classArm->classLevel->name }} {{ $classArm->name }} — {{ $term->name }}</h2>
            <p>{{ count($matrix) }} students &nbsp;·&nbsp; {{ $subjects->count() }} subjects</p>
        </div>
        <div style="font-size:12px;color:var(--slate-light)">
            Broadsheet shows total score per subject only
        </div>
    </div>
    {{-- Print-only header --}}
    @if(isset($classArm) && isset($term))
    <div class="print-header">
        <div class="ph-school">{{ optional(auth()->user()->tenant)->name }}</div>
        <div class="ph-sub">
            Class Broadsheet — {{ $classArm->classLevel->name }} {{ $classArm->name }}
            &nbsp;|&nbsp; {{ $term->name }} — {{ optional($term->session)->name }}
            &nbsp;|&nbsp; Generated: {{ now()->format('d M Y') }}
        </div>
    </div>
    @endif
    <div class="sheet-outer">
        <div class="tbl"><table>
            <thead>
                <tr>
                    {{-- Student column --}}
                    <th class="left" style="min-width:180px;position:sticky;left:0;z-index:2">#&nbsp; Student</th>
                    {{-- One column per subject (total only) --}}
                    @foreach($subjects as $i => $subject)
                    <th class="{{ $i % 2 === 0 ? 'sh-even':'sh-odd' }}" style="min-width:72px" title="{{ $subject->name }}">
                        {{ $subject->code ?: \Str::limit($subject->name, 8, '') }}
                    </th>
                    @endforeach
                    {{-- Summary columns --}}
                    <th style="background:#0F2942;min-width:54px">Total</th>
                    <th style="background:#0F2942;min-width:54px">Avg</th>
                    <th style="background:#0F2942;min-width:44px">Pos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matrix as $studentId => $row)
                <tr>
                    <td class="student-cell">
                        <div class="student-name">{{ $row['student']->full_name }}</div>
                        <div class="student-adm">{{ $row['student']->admission_number }}</div>
                    </td>
                    @foreach($subjects as $subject)
                    @php
                        $subData   = $row['subjects'][$subject->id] ?? null;
                        $hasScores = $subData['has_scores'] ?? false;
                        $tot       = $subData['total'] ?? null;
                        $pass      = $subData['is_pass'] ?? false;
                        $grade     = $subData['grade'] ?? '—';
                    @endphp
                    <td>
                        @if($hasScores && $tot !== null)
                            <div class="{{ $pass ? 'grade-pass':'grade-fail' }}" style="font-size:13px">{{ $tot }}</div>
                            <div style="font-size:10px;color:var(--slate-light)">{{ $grade }}</div>
                        @else
                            <span style="color:#CBD5E1">—</span>
                        @endif
                    </td>
                    @endforeach
                    <td class="total-col">{{ number_format($row['total'], 0) }}</td>
                    <td class="{{ $row['average'] >= 70 ? 'avg-high' : ($row['average'] >= 50 ? 'avg-mid' : 'avg-low') }}">
                        {{ number_format($row['average'], 1) }}
                    </td>
                    @php
                        $p = (int)$row['position'];
                        $sfx = match(true) { ($p%100>=11&&$p%100<=13)=>'th', ($p%10===1)=>'st', ($p%10===2)=>'nd', ($p%10===3)=>'rd', default=>'th' };
                    @endphp
                    <td class="pos-col">{{ $p }}<sup style="font-size:9px">{{ $sfx }}</sup></td>
                </tr>
                @endforeach
                {{-- Class stats footer --}}
                @if(isset($subjectStats))
                <tr style="background:#F0F9FF;border-top:2px solid var(--border)">
                    <td class="student-cell" style="font-weight:700;color:var(--indigo)">Class Stats</td>
                    @foreach($subjects as $subject)
                    @php $st = $subjectStats[$subject->id] ?? []; @endphp
                    <td style="font-size:11px;line-height:1.5">
                        <div style="color:#059669;font-weight:700">▲ {{ $st['highest'] ?? '—' }}</div>
                        <div style="color:#DC2626;font-weight:700">▼ {{ $st['lowest'] ?? '—' }}</div>
                        <div style="color:#6B7280">⌀ {{ $st['avg'] ?? '—' }}</div>
                    </td>
                    @endforeach
                    <td colspan="3"></td>
                </tr>
                @endif
            </tbody>
        </table></div>
    </div>
    @endif
@else
<div class="empty-state">
    <h3>Select a class and term</h3>
    <p>Choose a class and term above to generate the broadsheet.</p>
</div>
@endif
@endsection
