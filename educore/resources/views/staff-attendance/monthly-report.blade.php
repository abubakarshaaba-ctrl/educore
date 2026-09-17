@extends('layouts.app')
@section('title','Monthly Attendance Report')
@section('page-title','Staff Attendance')

@push('styles')
<style>
.top-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;min-width:0}
.month-nav{display:flex;align-items:center;gap:8px;flex-wrap:wrap;min-width:0}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:7px 14px;font-size:12.5px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms;min-width:0}
.btn-p{background:var(--indigo);color:white}.btn-g{background:#F1F5F9;color:var(--midnight);border:1px solid var(--border)}.btn-sm{padding:4px 10px;font-size:11px}
.summary-cards{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:18px;min-width:0}
.sc{background:white;border:1px solid var(--border);border-radius:10px;padding:12px;text-align:center;min-width:0}
.sv{font-size:22px;font-weight:800}.sl{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:2px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px;min-width:0;max-width:100%}
.ch{padding:12px 16px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;min-width:0}
/* Compact report table */
.report-table{width:max-content;min-width:900px;border-collapse:collapse;font-size:12px}
.report-table th{padding:8px 6px;background:#F8FAFC;border:1px solid var(--border);font-size:9px;font-weight:700;color:var(--slate-light);text-transform:uppercase;text-align:center;white-space:nowrap}
.report-table td{padding:7px 8px;border:1px solid var(--border);text-align:center;vertical-align:middle}
.report-table .name-col{text-align:left;font-weight:600;color:var(--midnight);white-space:nowrap;background:white;position:sticky;left:0;z-index:2;border-right:2px solid var(--border)}
.report-table thead .name-col{z-index:3}
.day-cell{width:28px;min-width:28px;cursor:default}
.d-E{background:#E0F2FE;color:#0284C7;font-weight:700;font-size:10px}
.d-P{background:#DCFCE7;color:#15803D;font-weight:700;font-size:10px}
.d-L{background:#FEF9C3;color:#A16207;font-weight:700;font-size:10px}
.d-A{background:#FEE2E2;color:#B91C1C;font-weight:700;font-size:10px}
.d-WE{background:#F8FAFC;color:#CBD5E1;font-size:9px}
.count-cell{font-weight:700;min-width:32px}
.c-E{color:#0284C7}.c-P{color:var(--emerald)}.c-L{color:var(--amber)}.c-A{color:var(--crimson)}
.pct{font-size:11px;padding:2px 6px;border-radius:10px}
.pct-good{background:#ECFDF5;color:var(--emerald)}.pct-ok{background:#FFFBEB;color:var(--amber)}.pct-bad{background:#FEF2F2;color:var(--crimson)}
.table-wrap{width:100%;max-width:100%;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;overscroll-behavior-inline:contain}
.tbl{width:max-content;min-width:100%}
.legend{display:flex;gap:12px;flex-wrap:wrap;font-size:11px;padding:8px 14px;border-top:1px solid var(--border);background:#FAFBFF}
.leg-item{display:flex;align-items:center;gap:5px}
.leg-dot{width:16px;height:16px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700}
.attendance-nav{display:flex;gap:4px;margin-bottom:20px;flex-wrap:wrap;max-width:100%}
.nav-tab{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;font-size:12.5px;font-weight:600;border:1.5px solid var(--border);background:white;color:var(--slate);text-decoration:none;transition:all 150ms}
.nav-tab:hover{background:#F1F5F9;color:var(--midnight)}
.nav-tab.active{background:var(--indigo);border-color:var(--indigo);color:white}
@media(max-width:768px){
    .attendance-nav{flex-wrap:nowrap;overflow-x:auto;overflow-y:hidden;padding-bottom:6px;margin-inline:-14px;padding-inline:14px;scrollbar-width:thin;-webkit-overflow-scrolling:touch}
    .attendance-nav .nav-tab{flex:0 0 auto;white-space:nowrap;padding:8px 11px;font-size:11.5px}
    .top-bar{display:block;margin-bottom:14px}
    .top-bar>div:first-child{font-size:15px!important;line-height:1.35;margin-bottom:10px}
    .month-nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));width:100%;gap:8px}
    .month-nav .btn{width:100%;min-height:42px;padding:8px 9px;white-space:normal;text-align:center}
    .summary-cards{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
    .summary-cards .sc:first-child{grid-column:1/-1}
    .sc{padding:11px 8px!important}
    .sv{font-size:21px}.sl{font-size:9.5px}
    .ch{align-items:flex-start;padding:12px 14px;line-height:1.35}
    .ch>span{min-width:0}
    .ch>span:last-child{width:100%;font-size:11px!important}
    .report-table{min-width:820px}
    .report-table .name-col{max-width:150px;min-width:150px!important;white-space:normal;line-height:1.25}
    .legend{display:grid;grid-template-columns:1fr 1fr;gap:8px 10px;padding:10px 12px;font-size:10.5px}
}
@media(max-width:420px){
    .month-nav{grid-template-columns:1fr 1fr}
    .summary-cards{gap:7px}
    .legend{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
{{-- Staff Attendance Nav --}}
<div class="attendance-nav">
    <a href="{{ route('staff-attendance.my') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.my') ? 'active':'' }}">
        👤 My Attendance
    </a>
    @if(auth()->user()->canManage('staff-attendance'))
    <a href="{{ route('staff-attendance.index') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.index') ? 'active':'' }}">
        📋 Today's Dashboard
    </a>
    <a href="{{ route('staff-attendance.report') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.report') ? 'active':'' }}">
        📊 Monthly Report
    </a>
    <a href="{{ route('staff-attendance.qr') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.qr') ? 'active':'' }}">
        📱 QR Display
    </a>
    <a href="{{ route('staff-attendance.settings') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.settings*') ? 'active':'' }}">
        ⚙️ Settings
    </a>
    @if($hasPendingOffline)
    <a href="{{ route('staff-attendance.offline-queue') }}"
       class="nav-tab" style="color:var(--amber)">
        📡 Offline Queue
    </a>
    @endif
    @endif
</div>

<div class="top-bar">
    <div style="font-size:16px;font-weight:800;color:var(--midnight)">
        Staff Attendance — {{ $startDate->format('F Y') }}
    </div>
    <div class="month-nav">
        @php $prev = \Carbon\Carbon::createFromDate($year,$month,1)->subMonth(); $next = \Carbon\Carbon::createFromDate($year,$month,1)->addMonth(); @endphp
        <a href="?month={{ $prev->month }}&year={{ $prev->year }}" class="btn btn-g">← {{ $prev->format('M') }}</a>
        <a href="?month={{ now()->month }}&year={{ now()->year }}" class="btn btn-g">This Month</a>
        @if($next->lte(now()))<a href="?month={{ $next->month }}&year={{ $next->year }}" class="btn btn-g">{{ $next->format('M') }} →</a>@endif
        <a href="{{ route('staff-attendance.index') }}" class="btn btn-p">← Dashboard</a>
        <button onclick="window.print()" class="btn btn-g">🖨 Print</button>
    </div>
</div>

{{-- Summary --}}
@php
$totals = ['early'=>0,'present'=>0,'late'=>0,'absent'=>0,'staff'=>$report->count()];
foreach($report as $r) { foreach(['early','present','late','absent'] as $s) $totals[$s] += $r['counts'][$s]; }
$avgPunctuality = $report->count() ? round($report->avg('punctuality')) : 0;
@endphp
<div class="summary-cards">
    <div class="sc"><div class="sv">{{ $totals['staff'] }}</div><div class="sl">Staff</div></div>
    <div class="sc"><div class="sv" style="color:#0284C7">{{ $totals['early'] }}</div><div class="sl">🔵 Early</div></div>
    <div class="sc"><div class="sv" style="color:var(--emerald)">{{ $totals['present'] }}</div><div class="sl">🟢 Present</div></div>
    <div class="sc"><div class="sv" style="color:var(--amber)">{{ $totals['late'] }}</div><div class="sl">🟡 Late</div></div>
    <div class="sc"><div class="sv" style="color:var(--crimson)">{{ $totals['absent'] }}</div><div class="sl">🔴 Absent</div></div>
</div>

<div class="card">
    <div class="ch">
        <span>{{ $startDate->format('F Y') }} — {{ count($workingDays) }} working days</span>
        <span style="font-size:12px;color:var(--slate-light)">Avg punctuality: <strong style="color:{{ $avgPunctuality>=80?'var(--emerald)':($avgPunctuality>=60?'var(--amber)':'var(--crimson)') }}">{{ $avgPunctuality }}%</strong></span>
    </div>
    <div class="table-wrap">
    <div class="tbl"><table class="report-table">
        <thead>
            <tr>
                <th class="name-col" style="min-width:160px;text-align:left">Staff Member</th>
                @foreach($workingDays as $day)
                <th class="day-cell">{{ \Carbon\Carbon::parse($day)->format('d') }}<br><span style="font-size:8px">{{ \Carbon\Carbon::parse($day)->format('D') }}</span></th>
                @endforeach
                <th class="count-cell" style="color:#0284C7">E</th>
                <th class="count-cell" style="color:var(--emerald)">P</th>
                <th class="count-cell" style="color:var(--amber)">L</th>
                <th class="count-cell" style="color:var(--crimson)">A</th>
                <th>Punctuality</th>
            </tr>
        </thead>
        <tbody>
        @foreach($report as $row)
        <tr>
            <td class="name-col">
                <div>{{ $row['staff']->name }}</div>
                <div style="font-size:10px;color:var(--slate-light);font-weight:400">{{ $row['staff']->roleLabel() }}</div>
            </td>
            @foreach($workingDays as $day)
            @php
                $d = $row['detail'][$day] ?? null;
                $s = $d['status'] ?? 'absent';
                $letter = match($s) { 'early'=>'E','present'=>'P','late'=>'L','absent'=>'A', default=>'—' };
                $cls = 'd-'.strtoupper($letter);
                $tip = ($d['clock_in'] ?? null) ? \Carbon\Carbon::parse($d['clock_in'])->format('H:i') : 'Absent';
            @endphp
            <td class="day-cell {{ $cls }}" title="{{ \Carbon\Carbon::parse($day)->format('d M') }}: {{ $tip }}">{{ $letter }}</td>
            @endforeach
            <td class="count-cell c-E">{{ $row['counts']['early'] }}</td>
            <td class="count-cell c-P">{{ $row['counts']['present'] }}</td>
            <td class="count-cell c-L">{{ $row['counts']['late'] }}</td>
            <td class="count-cell c-A">{{ $row['counts']['absent'] }}</td>
            <td>
                @php $p = $row['punctuality'] @endphp
                <span class="pct {{ $p>=80?'pct-good':($p>=60?'pct-ok':'pct-bad') }}">{{ $p }}%</span>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table></div>
    </div>
    <div class="legend">
        <div class="leg-item"><div class="leg-dot d-E">E</div> Early (before resumption)</div>
        <div class="leg-item"><div class="leg-dot d-P">P</div> Present (within grace)</div>
        <div class="leg-item"><div class="leg-dot d-L">L</div> Late (after grace)</div>
        <div class="leg-item"><div class="leg-dot d-A">A</div> Absent (no clock-in)</div>
    </div>
</div>
@endsection
