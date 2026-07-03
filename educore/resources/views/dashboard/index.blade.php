@extends('layouts.app')
@section('title','Dashboard')
@section('page-title','Dashboard')

@push('styles')
<style>
/* ── Stat Cards ──────────────────────────────────────────────────── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.stat-card{background:white;border:1px solid var(--border);border-radius:12px;padding:18px;position:relative;overflow:hidden;transition:box-shadow 200ms}
.stat-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.07)}
.stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:12px}
.stat-val{font-size:28px;font-weight:800;letter-spacing:-.03em;color:var(--midnight);line-height:1}
.stat-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);margin-top:4px}
.stat-sub{font-size:12px;color:var(--slate-light);margin-top:6px}
.stat-trend-up{color:#059669;font-weight:600}
.stat-trend-dn{color:#DC2626;font-weight:600}
.stat-card::after{content:'';position:absolute;top:0;right:0;width:3px;height:100%;border-radius:0 12px 12px 0}
.stat-blue::after{background:var(--indigo)}.stat-green::after{background:#059669}
.stat-amber::after{background:#D97706}.stat-red::after{background:#DC2626}
.stat-purple::after{background:#7C3AED}

/* ── Dashboard grid ──────────────────────────────────────────────── */
.dash-grid{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px}
.dash-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.card-head{padding:12px 16px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}
.card-title{font-size:13px;font-weight:700;color:var(--midnight)}
.card-body{padding:16px}

/* ── Charts ─────────────────────────────────────────────────────── */
.bar-chart{display:flex;align-items:flex-end;gap:6px;height:80px;padding-bottom:4px}
.bar-wrap{display:flex;flex-direction:column;align-items:center;flex:1;gap:3px}
.bar{width:100%;border-radius:4px 4px 0 0;transition:height 600ms;min-height:4px}
.bar-label{font-size:10px;color:var(--slate-light);font-weight:600;white-space:nowrap}
.bar-val{font-size:10px;font-weight:700;color:var(--slate)}

/* ── Fee donut ───────────────────────────────────────────────────── */
.donut-wrap{display:flex;align-items:center;gap:20px}
.donut-svg{flex-shrink:0}
.donut-legend{display:flex;flex-direction:column;gap:8px}
.legend-item{display:flex;align-items:center;gap:8px;font-size:12px}
.legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}

/* ── Quick links ─────────────────────────────────────────────────── */
.quick-links{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.ql-btn{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--border);border-radius:9px;text-decoration:none;color:var(--midnight);font-size:13px;font-weight:600;transition:all 150ms}
.ql-btn:hover{background:#F8FAFC;border-color:var(--indigo)}
.ql-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}

/* ── Risk flags ──────────────────────────────────────────────────── */
.risk-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)}
.risk-row:last-child{border:none}
.risk-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}

/* ── Attendance progress ─────────────────────────────────────────── */
.att-bar-wrap{display:flex;align-items:center;gap:10px;margin-bottom:6px}
.att-bar{flex:1;height:8px;background:#E2E8F0;border-radius:4px;overflow:hidden}
.att-fill{height:100%;border-radius:4px;transition:width 600ms}
.att-pct{font-size:12px;font-weight:700;min-width:36px;text-align:right}

/* ── Gender chart ────────────────────────────────────────────────── */
.gender-wrap{display:flex;gap:16px;align-items:center;justify-content:center;padding:8px 0}
.gender-item{text-align:center}
.gender-val{font-size:22px;font-weight:800}
.gender-lbl{font-size:11px;color:var(--slate-light);margin-top:2px}

/* ── Announcements ───────────────────────────────────────────────── */
.ann-item{padding:10px 0;border-bottom:1px solid var(--border)}
.ann-item:last-child{border:none}
.ann-title{font-size:13px;font-weight:600;color:var(--midnight)}
.ann-meta{font-size:11px;color:var(--slate-light);margin-top:2px}

@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:900px){.dash-grid,.dash-grid-3{grid-template-columns:1fr}}
@media(max-width:600px){.stats-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

@if($currentTerm)
<div style="font-size:12px;color:var(--slate-light);margin-bottom:16px;background:white;border:1px solid var(--border);border-radius:8px;padding:8px 14px;display:inline-flex;align-items:center;gap:8px">
    📅 <strong>Current Term:</strong> {{ $currentTerm->name }} &nbsp;·&nbsp; {{ optional($currentTerm->session)->name }}
</div>
@endif

{{-- ── Top Stats Row ─────────────────────────────────────────────── --}}
<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-icon" style="background:#EFF6FF">👥</div>
        <div class="stat-val">{{ number_format($totalStudents) }}</div>
        <div class="stat-label">Active Students</div>
        @php
            $male   = $genderBreakdown->get('male', 0);
            $female = $genderBreakdown->get('female', 0);
        @endphp
        <div class="stat-sub">♂ {{ $male }} &nbsp;·&nbsp; ♀ {{ $female }}</div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon" style="background:#ECFDF5">👨‍🏫</div>
        <div class="stat-val">{{ number_format($totalStaff) }}</div>
        <div class="stat-label">Active Staff</div>
        <div class="stat-sub">{{ $totalClasses }} class arms</div>
    </div>
    <div class="stat-card stat-amber">
        <div class="stat-icon" style="background:#FFFBEB">💰</div>
        <div class="stat-val">{{ number_format($totalCollected/1000,0) }}k</div>
        <div class="stat-label">Fees Collected</div>
        <div class="stat-sub">
            <span class="{{ $collectionRate >= 70 ? 'stat-trend-up' : 'stat-trend-dn' }}">
                {{ $collectionRate }}%
            </span> of ₦{{ number_format($totalInvoiced/1000,0) }}k invoiced
        </div>
    </div>
    <div class="stat-card {{ is_null($attendanceRate) ? 'stat-blue' : ($attendanceRate >= 75 ? 'stat-green' : 'stat-red') }}">
        <div class="stat-icon" style="background:#{{ is_null($attendanceRate) ? 'EFF6FF' : ($attendanceRate >= 75 ? 'ECFDF5' : 'FEF2F2') }}">📅</div>
        <div class="stat-val">{{ is_null($attendanceRate) ? '—' : $attendanceRate.'%' }}</div>
        <div class="stat-label">Today's Attendance</div>
        <div class="stat-sub">{{ $presentToday }} present · {{ $absentToday }} absent</div>
    </div>
</div>

{{-- ── Charts Row ────────────────────────────────────────────────── --}}
<div class="dash-grid">

    {{-- Weekly Attendance Bar Chart --}}
    <div class="card">
        <div class="card-head">
            <span class="card-title">📊 Attendance — Last 7 Days</span>
        </div>
        <div class="card-body">
            @if($attendanceTrend->count())
            @php $maxRate = $attendanceTrend->max('rate') ?: 100; @endphp
            <div class="bar-chart">
                @foreach($attendanceTrend as $day)
                <div class="bar-wrap">
                    <div class="bar-val">{{ $day['rate'] }}%</div>
                    <div class="bar" style="height:{{ max(4, ($day['rate']/$maxRate)*60) }}px;background:{{ $day['rate']>=75?'#059669':($day['rate']>=50?'#D97706':'#DC2626') }}"></div>
                    <div class="bar-label">{{ $day['date'] }}</div>
                </div>
                @endforeach
            </div>
            <div style="margin-top:12px;font-size:12px;color:var(--slate-light)">
                Showing presence rate per day
            </div>
            @else
            <div style="text-align:center;padding:40px;color:var(--slate-light);font-size:13px">
                No attendance data for the past 7 days
            </div>
            @endif
        </div>
    </div>

    {{-- Fee Collection Donut --}}
    <div class="card">
        <div class="card-head"><span class="card-title">💳 Fee Collection</span></div>
        <div class="card-body">
            @php
                $paid    = $totalCollected;
                $pending = max(0, $totalOutstanding);
                $total   = $paid + $pending;
                $paidPct = $total > 0 ? ($paid / $total) * 314 : 0;
                $paidDeg = $total > 0 ? ($paid / $total) * 360 : 0;
            @endphp
            <div class="donut-wrap">
                <svg width="100" height="100" viewBox="0 0 100 100" class="donut-svg">
                    <circle cx="50" cy="50" r="40" fill="none" stroke="#E2E8F0" stroke-width="14"/>
                    <circle cx="50" cy="50" r="40" fill="none"
                        stroke="#059669" stroke-width="14"
                        stroke-dasharray="{{ $paidPct }} 314"
                        stroke-dashoffset="78.5"
                        stroke-linecap="round"
                        transform="rotate(-90 50 50)"/>
                    <text x="50" y="46" text-anchor="middle" font-size="14" font-weight="800" fill="#1E293B">{{ $collectionRate }}%</text>
                    <text x="50" y="60" text-anchor="middle" font-size="8" fill="#94A3B8">collected</text>
                </svg>
                <div class="donut-legend">
                    <div class="legend-item"><div class="legend-dot" style="background:#059669"></div><div><div style="font-weight:700">₦{{ number_format($totalCollected) }}</div><div style="color:var(--slate-light);font-size:11px">Collected</div></div></div>
                    <div class="legend-item"><div class="legend-dot" style="background:#E2E8F0"></div><div><div style="font-weight:700">₦{{ number_format($totalOutstanding) }}</div><div style="color:var(--slate-light);font-size:11px">Outstanding</div></div></div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="dash-grid-3">

    {{-- Students by Class --}}
    <div class="card">
        <div class="card-head"><span class="card-title">🏫 Students by Class</span></div>
        <div class="card-body">
            @if($studentsByClass->count())
            @php $maxCount = $studentsByClass->max('count') ?: 1; @endphp
            @foreach($studentsByClass->take(8) as $cls)
            <div class="att-bar-wrap">
                <div style="font-size:11px;min-width:80px;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $cls['label'] }}</div>
                <div class="att-bar">
                    <div class="att-fill" style="width:{{ ($cls['count']/$maxCount)*100 }}%;background:var(--indigo)"></div>
                </div>
                <div class="att-pct" style="color:var(--indigo)">{{ $cls['count'] }}</div>
            </div>
            @endforeach
            @else
            <div style="text-align:center;padding:30px;color:var(--slate-light);font-size:13px">No students enrolled</div>
            @endif
        </div>
    </div>

    {{-- Risk Flags + Gender --}}
    <div>
        <div class="card" style="margin-bottom:14px">
            <div class="card-head">
                <span class="card-title">🚩 Risk Flags</span>
                @if($openRiskFlags && $openRiskFlags->sum() > 0)
                <a href="{{ route('risk.index') }}" style="font-size:11px;color:var(--indigo);text-decoration:none;font-weight:600">View All →</a>
                @endif
            </div>
            <div class="card-body">
                @if($openRiskFlags && $openRiskFlags->sum() > 0)
                @foreach(['critical'=>'#DC2626','high'=>'#EA580C','medium'=>'#D97706'] as $level=>$color)
                @if($openRiskFlags->get($level,0) > 0)
                <div class="risk-row">
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="risk-dot" style="background:{{$color}}"></div>
                        <span style="font-size:13px">{{ ucfirst($level) }}</span>
                    </div>
                    <span style="font-weight:700;color:{{$color}}">{{ $openRiskFlags->get($level,0) }}</span>
                </div>
                @endif
                @endforeach
                @else
                <div style="text-align:center;padding:20px;font-size:13px;color:var(--slate-light)">
                    ✅ No open risk flags
                    @if(!$currentTerm) <br><span style="font-size:11px">Run analysis first</span>@endif
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-head"><span class="card-title">⚥ Gender Split</span></div>
            <div class="card-body">
                <div class="gender-wrap">
                    <div class="gender-item">
                        <div class="gender-val" style="color:#2563EB">{{ $genderBreakdown->get('male',0) }}</div>
                        <div class="gender-lbl">♂ Male</div>
                    </div>
                    <div style="width:1px;height:40px;background:var(--border)"></div>
                    <div class="gender-item">
                        <div class="gender-val" style="color:#DB2777">{{ $genderBreakdown->get('female',0) }}</div>
                        <div class="gender-lbl">♀ Female</div>
                    </div>
                </div>
                @if($totalStudents > 0)
                @php $malePct = round(($genderBreakdown->get('male',0) / $totalStudents)*100); @endphp
                <div style="background:#E2E8F0;border-radius:6px;height:8px;overflow:hidden;margin-top:10px">
                    <div style="height:100%;width:{{$malePct}}%;background:linear-gradient(to right,#2563EB,#7C3AED)"></div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Links + Announcements --}}
    <div>
        <div class="card" style="margin-bottom:14px">
            <div class="card-head"><span class="card-title">⚡ Quick Actions</span></div>
            <div class="card-body">
                <div class="quick-links">
                    <a href="{{ route('students.create') }}" class="ql-btn"><div class="ql-icon" style="background:#EFF6FF">👤</div>Add Student</a>
                    <a href="{{ route('fees.generate.index') }}" class="ql-btn"><div class="ql-icon" style="background:#ECFDF5">🧾</div>Generate Fees</a>
                    <a href="{{ route('attendance.index') }}" class="ql-btn"><div class="ql-icon" style="background:#FFFBEB">📅</div>Attendance</a>
                    <a href="{{ route('scores.index') }}" class="ql-btn"><div class="ql-icon" style="background:#F5F3FF">✏️</div>Enter Scores</a>
                    <a href="{{ route('students.bulk-upload.index') }}" class="ql-btn"><div class="ql-icon" style="background:#ECFDF5">⬆️</div>Bulk Upload</a>
                    <a href="{{ route('risk.index') }}" class="ql-btn"><div class="ql-icon" style="background:#FEF2F2">🚩</div>Risk Flags</a>
                </div>
                @if($pendingAdmissions > 0)
                <div style="margin-top:12px;padding:10px 12px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;font-size:12px;color:#92400E;display:flex;align-items:center;justify-content:space-between">
                    <span>⏳ <strong>{{ $pendingAdmissions }}</strong> pending admissions</span>
                    <a href="{{ route('admissions.index') }}" style="color:#D97706;font-weight:600;text-decoration:none;font-size:11px">Review →</a>
                </div>
                @endif
            </div>
        </div>

        @if(count($announcements) > 0)
        <div class="card">
            <div class="card-head">
                <span class="card-title">📢 Announcements</span>
                <a href="{{ route('announcements.index') }}" style="font-size:11px;color:var(--indigo);text-decoration:none;font-weight:600">All →</a>
            </div>
            <div class="card-body">
                @foreach($announcements as $ann)
                <div class="ann-item">
                    <div class="ann-title">{{ $ann->title }}</div>
                    <div class="ann-meta">{{ optional($ann->created_at)->diffForHumans() }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

</div>

{{-- ── Monthly Fee Trend ────────────────────────────────────────── --}}
@if($feesTrend->count() > 1)
<div class="card">
    <div class="card-head"><span class="card-title">📈 Fee Collection Trend — Last 6 Months</span></div>
    <div class="card-body">
        @php $maxFee = $feesTrend->max('collected') ?: 1; @endphp
        <div class="bar-chart" style="height:100px">
            @foreach($feesTrend as $month)
            <div class="bar-wrap">
                <div class="bar-val">₦{{ number_format($month['collected']/1000,0) }}k</div>
                <div class="bar" style="height:{{ max(4, ($month['collected']/$maxFee)*80) }}px;background:var(--indigo);opacity:{{ 0.5 + ($loop->index/$feesTrend->count())*0.5 }}"></div>
                <div class="bar-label">{{ $month['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@endsection
