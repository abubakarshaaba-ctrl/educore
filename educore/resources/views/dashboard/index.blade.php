@extends('layouts.app')
@section('title','Dashboard')
@section('page-title','Dashboard')

@push('styles')
<style>
.admin-dashboard{--dash-navy:#071e45;--dash-gold:#d79a21;--dash-ink:#101828;--dash-muted:#667085;--dash-line:#e4e9f0;--dash-bg:#f4f7fb;--dash-green:#16794b;--dash-red:#c9362b;--dash-amber:#c87512;color:var(--dash-ink)}
.admin-dashboard *{box-sizing:border-box}
.dash-welcome{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:20px}
.dash-greeting{font-size:28px;line-height:1.2;font-weight:800;letter-spacing:-.035em;color:var(--dash-ink);margin:0}
.dash-intro{font-size:13px;color:var(--dash-muted);margin:6px 0 0}
.term-pill{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid #cfd9e8;background:#fff;border-radius:999px;color:#344054;font-size:12px;font-weight:700;white-space:nowrap}
.term-pill svg{color:var(--dash-gold)}
.admissions-alert{display:flex;align-items:center;gap:10px;padding:10px 14px;border:1px solid #f3d38a;background:#fff9eb;border-radius:11px;color:#805500;font-size:12px;box-shadow:0 5px 18px rgba(133,89,0,.06)}
.admissions-alert svg{color:var(--dash-amber);flex:none}.admissions-alert strong{font-size:13px}.admissions-alert a{color:#996300;font-weight:800;text-decoration:none;margin-left:8px}.admissions-alert a:hover{text-decoration:underline}
.stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:16px}
.stat-card{position:relative;display:flex;align-items:center;gap:15px;min-height:126px;padding:20px;background:#fff;border:1px solid var(--dash-line);border-radius:16px;box-shadow:0 4px 14px rgba(16,24,40,.035);overflow:hidden;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}
.stat-card:hover{transform:translateY(-2px);border-color:#ccd6e4;box-shadow:0 12px 28px rgba(16,24,40,.08)}
.stat-card::after{content:"";position:absolute;inset:auto 0 0;height:3px;background:var(--tone)}
.stat-icon{width:54px;height:54px;border-radius:50%;display:grid;place-items:center;flex:none;background:color-mix(in srgb,var(--tone) 9%,white);border:1px solid color-mix(in srgb,var(--tone) 22%,white);color:var(--tone)}
.stat-copy{min-width:0}.stat-val{font-size:27px;line-height:1;font-weight:850;letter-spacing:-.04em;color:var(--tone)}
.stat-label{font-size:12px;font-weight:750;color:#344054;margin-top:7px}.stat-sub{font-size:11px;color:var(--dash-muted);margin-top:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.trend-good{color:var(--dash-green);font-weight:800}.trend-risk{color:var(--dash-red);font-weight:800}
.dash-grid{display:grid;grid-template-columns:minmax(0,1.85fr) minmax(300px,1fr);gap:16px;margin-bottom:16px}
.dash-lower{display:grid;grid-template-columns:minmax(250px,1fr) minmax(220px,.78fr) minmax(260px,1.05fr);gap:16px;margin-bottom:16px}
.stack{display:grid;gap:16px;align-content:start}
.dash-card{background:#fff;border:1px solid var(--dash-line);border-radius:16px;overflow:hidden;box-shadow:0 4px 14px rgba(16,24,40,.03)}
.card-head{min-height:54px;padding:14px 18px;border-bottom:1px solid #edf0f5;display:flex;align-items:center;justify-content:space-between;gap:12px}
.card-title-wrap{display:flex;align-items:center;gap:9px}.card-icon{width:29px;height:29px;border-radius:8px;display:grid;place-items:center;background:#f1f5fb;color:var(--dash-navy)}
.card-title{font-size:14px;font-weight:800;color:var(--dash-ink);letter-spacing:-.01em}.card-meta{font-size:10px;color:var(--dash-muted);margin-top:2px}
.card-link{font-size:11px;color:#1f5cb8;text-decoration:none;font-weight:750}.card-link:hover{text-decoration:underline}.card-body{padding:18px}
.chart-filter{padding:6px 9px;border:1px solid var(--dash-line);background:#fff;border-radius:8px;color:var(--dash-muted);font-size:10px;font-weight:700}
.attendance-chart{position:relative;display:flex;align-items:flex-end;gap:10px;height:190px;padding:18px 4px 2px;border-bottom:1px solid #dfe5ee;background:repeating-linear-gradient(to bottom,transparent 0,transparent 43px,#edf1f6 44px)}
.bar-wrap{position:relative;z-index:1;display:flex;flex:1;height:100%;min-width:0;flex-direction:column;justify-content:flex-end;align-items:center;gap:5px}
.bar-val{font-size:10px;font-weight:800;color:#344054}.bar{width:min(30px,62%);min-height:5px;border-radius:8px 8px 2px 2px;background:linear-gradient(180deg,#2e67cb,#17489e);box-shadow:0 4px 10px rgba(31,92,184,.16);transition:height .5s ease}.bar.good{background:linear-gradient(180deg,#2c9a69,#16794b)}.bar.warn{background:linear-gradient(180deg,#e5a72d,#c87512)}.bar.risk{background:linear-gradient(180deg,#dc665c,#c9362b)}.bar-label{font-size:10px;color:var(--dash-muted);font-weight:650;white-space:nowrap}
.chart-foot{display:flex;align-items:center;justify-content:space-between;margin-top:12px;color:var(--dash-muted);font-size:11px}.chart-key{display:flex;align-items:center;gap:6px}.chart-key::before{content:"";width:7px;height:7px;border-radius:50%;background:var(--dash-green)}
.fee-layout{display:flex;align-items:center;justify-content:center;gap:24px;min-height:190px}.donut-shell{position:relative;width:128px;height:128px;flex:none}.donut-shell svg{width:100%;height:100%;transform:rotate(-90deg)}.donut-center{position:absolute;inset:0;display:grid;place-content:center;text-align:center}.donut-center strong{font-size:19px;color:var(--dash-ink)}.donut-center span{font-size:10px;color:var(--dash-muted);margin-top:2px}.fee-legend{display:grid;gap:14px;min-width:145px}.fee-line{padding-bottom:12px;border-bottom:1px solid var(--dash-line)}.fee-line:last-child{padding:0;border:0}.fee-line small{display:flex;align-items:center;gap:7px;color:var(--dash-muted);font-size:10px}.fee-line small::before{content:"";width:8px;height:8px;border-radius:50%;background:var(--dot)}.fee-line strong{display:block;font-size:16px;margin-top:4px;color:var(--tone)}
.class-list{display:grid;gap:12px}.class-row{display:grid;grid-template-columns:82px 1fr 34px;align-items:center;gap:10px}.class-label{font-size:11px;color:#475467;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.class-track{height:7px;background:#edf1f6;border-radius:999px;overflow:hidden}.class-fill{height:100%;background:linear-gradient(90deg,#194f9f,#2e6bcf);border-radius:999px}.class-count{font-size:11px;text-align:right;font-weight:800;color:#315486}
.class-total{display:flex;justify-content:space-between;padding-top:13px;margin-top:13px;border-top:1px solid var(--dash-line);font-size:11px;font-weight:800;color:#1d58ac}
.risk-list{display:grid;gap:8px}.risk-row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px 12px;border:1px solid var(--dash-line);border-radius:11px;background:#fff}.risk-name{display:flex;align-items:center;gap:9px;font-size:12px;font-weight:750}.risk-icon{width:27px;height:27px;border-radius:8px;display:grid;place-items:center;background:color-mix(in srgb,var(--risk) 9%,white);color:var(--risk)}.risk-count{font-size:17px;font-weight:850;color:var(--risk)}.risk-empty{text-align:center;padding:28px 10px;color:var(--dash-muted);font-size:12px}.risk-empty svg{display:block;margin:0 auto 8px;color:var(--dash-green)}
.gender-layout{display:flex;align-items:center;justify-content:center;gap:18px;padding:4px}.gender-ring{width:105px;height:105px;border-radius:50%;position:relative;background:conic-gradient(#2d69d5 0 var(--male),#e95087 var(--male) 100%)}.gender-ring::after{content:"";position:absolute;inset:14px;background:#fff;border-radius:50%}.gender-center{position:absolute;inset:0;z-index:1;display:grid;place-content:center;text-align:center}.gender-center strong{font-size:18px}.gender-center span{font-size:9px;color:var(--dash-muted)}.gender-legend{display:grid;gap:12px}.gender-item{display:grid;grid-template-columns:8px 1fr;gap:8px;align-items:center}.gender-dot{width:8px;height:8px;border-radius:50%;background:var(--gender)}.gender-copy strong{display:block;font-size:14px;color:var(--gender)}.gender-copy small{color:var(--dash-muted);font-size:10px}
.quick-actions{display:grid;grid-template-columns:1fr 1fr;gap:9px}.action-btn{display:flex;align-items:center;gap:9px;min-height:52px;padding:10px;border:1px solid var(--dash-line);border-radius:11px;background:#fff;text-decoration:none;color:#344054;font-size:11px;font-weight:750;transition:.16s ease}.action-btn:hover{border-color:#b6c8e1;background:#f8fbff;color:#174c96;transform:translateY(-1px)}.action-icon{width:31px;height:31px;border-radius:9px;display:grid;place-items:center;background:color-mix(in srgb,var(--action) 9%,white);color:var(--action);flex:none}
.ann-list{display:grid}.ann-item{display:grid;grid-template-columns:31px 1fr auto;align-items:center;gap:10px;padding:11px 0;border-bottom:1px solid var(--dash-line)}.ann-item:first-child{padding-top:0}.ann-item:last-child{border:0;padding-bottom:0}.ann-icon{width:31px;height:31px;border-radius:9px;display:grid;place-items:center;background:#eef4fd;color:#1d58ac}.ann-title{font-size:11px;font-weight:750;color:#344054;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.ann-meta{font-size:9px;color:var(--dash-muted);margin-top:3px}.ann-arrow{color:#98a2b3}
.fee-trend{display:flex;align-items:flex-end;gap:15px;height:130px;padding:8px 8px 0;border-bottom:1px solid #dfe5ee;background:repeating-linear-gradient(to bottom,transparent 0,transparent 39px,#edf1f6 40px)}.fee-month{flex:1;display:flex;height:100%;min-width:0;flex-direction:column;justify-content:flex-end;align-items:center;gap:5px}.fee-month strong{font-size:10px;color:#344054}.fee-bar{width:min(38px,58%);border-radius:8px 8px 2px 2px;background:linear-gradient(180deg,#dba63b,#c07e0a);min-height:5px}.fee-month span{font-size:10px;color:var(--dash-muted)}
.empty-state{text-align:center;padding:48px 16px;color:var(--dash-muted);font-size:12px}.empty-state svg{display:block;margin:0 auto 10px;color:#98a2b3}
.icon{width:18px;height:18px;stroke:currentColor;stroke-width:1.8;fill:none;stroke-linecap:round;stroke-linejoin:round}.icon-sm{width:15px;height:15px}
@media(max-width:1200px){.stats-grid{grid-template-columns:repeat(2,1fr)}.dash-lower{grid-template-columns:1fr 1fr}.dash-lower>.stack:last-child{grid-column:1/-1;grid-template-columns:1fr 1fr}.fee-layout{gap:14px}}
@media(max-width:900px){.dash-grid,.dash-lower{grid-template-columns:1fr}.dash-lower>.stack:last-child{grid-column:auto;grid-template-columns:1fr}.dash-welcome{flex-direction:column}.dash-welcome>div:last-child{width:100%;display:flex;flex-wrap:wrap}.admissions-alert{flex:1}}
@media(max-width:600px){.stats-grid{grid-template-columns:1fr}.stat-card{min-height:110px}.dash-greeting{font-size:24px}.fee-layout{flex-direction:column}.quick-actions{grid-template-columns:1fr}.attendance-chart{gap:4px}.bar{width:70%}.bar-label{font-size:8px}.card-body{padding:14px}}
</style>
@endpush

@section('content')
@php
    $male = $genderBreakdown->get('male', 0);
    $female = $genderBreakdown->get('female', 0);
    $malePct = $totalStudents > 0 ? round(($male / $totalStudents) * 100) : 0;
    $paid = $totalCollected;
    $pending = max(0, $totalOutstanding);
    $feeTotal = $paid + $pending;
    $paidCircumference = $feeTotal > 0 ? ($paid / $feeTotal) * 314 : 0;
@endphp

<div class="admin-dashboard">
    <div class="dash-welcome">
        <div>
            <h1 class="dash-greeting">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name ? explode(' ', auth()->user()->name)[0] : 'Administrator' }}</h1>
            <p class="dash-intro">Here is what is happening across your school today.</p>
        </div>
        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap">
            @if($currentTerm)
            <div class="term-pill">
                <svg class="icon icon-sm"><use href="#i-calendar"/></svg>
                {{ $currentTerm->name }} · {{ optional($currentTerm->session)->name }}
            </div>
            @endif
            @if($pendingAdmissions > 0)
            <div class="admissions-alert">
                <svg class="icon icon-sm"><use href="#i-bell"/></svg>
                <span><strong>{{ $pendingAdmissions }}</strong> pending admissions</span>
                <a href="{{ route('admissions.index') }}">Review →</a>
            </div>
            @endif
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card" style="--tone:#1d58ac">
            <div class="stat-icon"><svg class="icon"><use href="#i-users"/></svg></div>
            <div class="stat-copy"><div class="stat-val">{{ number_format($totalStudents) }}</div><div class="stat-label">Active Students</div><div class="stat-sub">{{ $male }} male · {{ $female }} female</div></div>
        </div>
        <div class="stat-card" style="--tone:#16794b">
            <div class="stat-icon"><svg class="icon"><use href="#i-staff"/></svg></div>
            <div class="stat-copy"><div class="stat-val">{{ number_format($totalStaff) }}</div><div class="stat-label">Active Staff</div><div class="stat-sub">Across {{ number_format($totalClasses) }} class arms</div></div>
        </div>
        <div class="stat-card" style="--tone:#bd7a0b">
            <div class="stat-icon"><svg class="icon"><use href="#i-wallet"/></svg></div>
            <div class="stat-copy"><div class="stat-val">₦{{ $totalCollected >= 1000000 ? number_format($totalCollected/1000000,1).'M' : number_format($totalCollected/1000,0).'K' }}</div><div class="stat-label">Fees Collected</div><div class="stat-sub"><span class="{{ $collectionRate >= 70 ? 'trend-good' : 'trend-risk' }}">{{ $collectionRate }}%</span> of ₦{{ number_format($totalInvoiced) }} invoiced</div></div>
        </div>
        <div class="stat-card" style="--tone:{{ is_null($attendanceRate) ? '#1d58ac' : ($attendanceRate >= 75 ? '#16794b' : '#c9362b') }}">
            <div class="stat-icon"><svg class="icon"><use href="#i-check-calendar"/></svg></div>
            <div class="stat-copy"><div class="stat-val">{{ is_null($attendanceRate) ? '—' : $attendanceRate.'%' }}</div><div class="stat-label">Today's Attendance</div><div class="stat-sub">{{ $presentToday }} present · {{ $absentToday }} absent</div></div>
        </div>
    </div>

    <div class="dash-grid">
        <section class="dash-card">
            <div class="card-head"><div class="card-title-wrap"><span class="card-icon"><svg class="icon icon-sm"><use href="#i-chart"/></svg></span><div><div class="card-title">Attendance Overview</div><div class="card-meta">Daily presence rate</div></div></div><span class="chart-filter">Last 7 Days</span></div>
            <div class="card-body">
                @if($attendanceTrend->count())
                @php $maxRate = max(100, $attendanceTrend->max('rate') ?: 100); @endphp
                <div class="attendance-chart">
                    @foreach($attendanceTrend as $day)
                    <div class="bar-wrap"><div class="bar-val">{{ $day['rate'] }}%</div><div class="bar {{ $day['rate'] >= 75 ? 'good' : ($day['rate'] >= 50 ? 'warn' : 'risk') }}" style="height:{{ max(5, ($day['rate']/$maxRate)*135) }}px"></div><div class="bar-label">{{ $day['date'] }}</div></div>
                    @endforeach
                </div>
                <div class="chart-foot"><span class="chart-key">Healthy attendance target: 75%+</span><span>{{ $attendanceTrend->count() }} school days</span></div>
                @else
                <div class="empty-state"><svg class="icon" style="width:30px;height:30px"><use href="#i-chart"/></svg>No attendance data for the past seven days.</div>
                @endif
            </div>
        </section>

        <section class="dash-card">
            <div class="card-head"><div class="card-title-wrap"><span class="card-icon"><svg class="icon icon-sm"><use href="#i-card"/></svg></span><div><div class="card-title">Fee Collection</div><div class="card-meta">Current billing position</div></div></div><span class="chart-filter">This Term</span></div>
            <div class="card-body">
                <div class="fee-layout">
                    <div class="donut-shell"><svg viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="#edf1f6" stroke-width="13"/><circle cx="50" cy="50" r="40" fill="none" stroke="#16794b" stroke-width="13" stroke-dasharray="{{ $paidCircumference }} 314" stroke-linecap="round"/></svg><div class="donut-center"><strong>{{ $collectionRate }}%</strong><span>collected</span></div></div>
                    <div class="fee-legend"><div class="fee-line" style="--dot:#16794b;--tone:#12663f"><small>Collected</small><strong>₦{{ number_format($totalCollected) }}</strong></div><div class="fee-line" style="--dot:#d79a21;--tone:#aa6a05"><small>Outstanding</small><strong>₦{{ number_format($totalOutstanding) }}</strong></div></div>
                </div>
            </div>
        </section>
    </div>

    <div class="dash-lower">
        <section class="dash-card">
            <div class="card-head"><div class="card-title-wrap"><span class="card-icon"><svg class="icon icon-sm"><use href="#i-school"/></svg></span><div class="card-title">Students by Class</div></div></div>
            <div class="card-body">
                @if($studentsByClass->count())
                @php $maxCount = $studentsByClass->max('count') ?: 1; @endphp
                <div class="class-list">@foreach($studentsByClass->take(8) as $cls)<div class="class-row"><span class="class-label" title="{{ $cls['label'] }}">{{ $cls['label'] }}</span><div class="class-track"><div class="class-fill" style="width:{{ ($cls['count']/$maxCount)*100 }}%"></div></div><span class="class-count">{{ $cls['count'] }}</span></div>@endforeach</div>
                <div class="class-total"><span>Total active students</span><span>{{ number_format($totalStudents) }}</span></div>
                @else<div class="empty-state">No students enrolled.</div>@endif
            </div>
        </section>

        <div class="stack">
            <section class="dash-card">
                <div class="card-head"><div class="card-title-wrap"><span class="card-icon"><svg class="icon icon-sm"><use href="#i-flag"/></svg></span><div class="card-title">Risk Flags</div></div>@if($openRiskFlags && $openRiskFlags->sum() > 0)<a class="card-link" href="{{ route('risk.index') }}">View all →</a>@endif</div>
                <div class="card-body">
                    @if($openRiskFlags && $openRiskFlags->sum() > 0)
                    <div class="risk-list">@foreach(['critical'=>'#c9362b','high'=>'#e76516','medium'=>'#c98a13'] as $level=>$color)@if($openRiskFlags->get($level,0) > 0)<div class="risk-row" style="--risk:{{$color}}"><span class="risk-name"><span class="risk-icon"><svg class="icon icon-sm"><use href="#i-alert"/></svg></span>{{ ucfirst($level) }}</span><span class="risk-count">{{ $openRiskFlags->get($level,0) }}</span></div>@endif @endforeach</div>
                    @else
                    <div class="risk-empty">
                        <svg class="icon" style="width:30px;height:30px"><use href="#i-shield-check"/></svg>
                        No open risk flags
                        @if(!$currentTerm)
                        <br><small>Run analysis after configuring the term.</small>
                        @endif
                    </div>
                    @endif
                </div>
            </section>
            <section class="dash-card">
                <div class="card-head"><div class="card-title-wrap"><span class="card-icon"><svg class="icon icon-sm"><use href="#i-pie"/></svg></span><div class="card-title">Gender Split</div></div></div>
                <div class="card-body"><div class="gender-layout"><div class="gender-ring" style="--male:{{$malePct}}%"><div class="gender-center"><strong>{{ number_format($totalStudents) }}</strong><span>Students</span></div></div><div class="gender-legend"><div class="gender-item" style="--gender:#2d69d5"><span class="gender-dot"></span><div class="gender-copy"><strong>{{ $malePct }}%</strong><small>{{ $male }} male</small></div></div><div class="gender-item" style="--gender:#e95087"><span class="gender-dot"></span><div class="gender-copy"><strong>{{ 100-$malePct }}%</strong><small>{{ $female }} female</small></div></div></div></div></div>
            </section>
        </div>

        <div class="stack">
            <section class="dash-card">
                <div class="card-head"><div class="card-title-wrap"><span class="card-icon"><svg class="icon icon-sm"><use href="#i-bolt"/></svg></span><div class="card-title">Quick Actions</div></div></div>
                <div class="card-body"><div class="quick-actions">
                    <a href="{{ route('students.create') }}" class="action-btn" style="--action:#1d58ac"><span class="action-icon"><svg class="icon icon-sm"><use href="#i-user-plus"/></svg></span>Add Student</a>
                    <a href="{{ route('fees.generate.index') }}" class="action-btn" style="--action:#16794b"><span class="action-icon"><svg class="icon icon-sm"><use href="#i-receipt"/></svg></span>Generate Fees</a>
                    <a href="{{ route('attendance.index') }}" class="action-btn" style="--action:#bd7a0b"><span class="action-icon"><svg class="icon icon-sm"><use href="#i-check-calendar"/></svg></span>Attendance</a>
                    <a href="{{ route('scores.index') }}" class="action-btn" style="--action:#365cad"><span class="action-icon"><svg class="icon icon-sm"><use href="#i-edit"/></svg></span>Enter Scores</a>
                    <a href="{{ route('students.bulk-upload.index') }}" class="action-btn" style="--action:#16794b"><span class="action-icon"><svg class="icon icon-sm"><use href="#i-upload"/></svg></span>Bulk Upload</a>
                    <a href="{{ route('risk.index') }}" class="action-btn" style="--action:#c9362b"><span class="action-icon"><svg class="icon icon-sm"><use href="#i-flag"/></svg></span>Risk Flags</a>
                </div></div>
            </section>
            @if(count($announcements) > 0)
            <section class="dash-card">
                <div class="card-head"><div class="card-title-wrap"><span class="card-icon"><svg class="icon icon-sm"><use href="#i-megaphone"/></svg></span><div class="card-title">Announcements</div></div><a class="card-link" href="{{ route('announcements.index') }}">View all</a></div>
                <div class="card-body"><div class="ann-list">@foreach($announcements as $ann)<div class="ann-item"><span class="ann-icon"><svg class="icon icon-sm"><use href="#i-megaphone"/></svg></span><div style="min-width:0"><div class="ann-title">{{ $ann->title }}</div><div class="ann-meta">{{ optional($ann->created_at)->diffForHumans() }}</div></div><span class="ann-arrow">›</span></div>@endforeach</div></div>
            </section>
            @endif
        </div>
    </div>

    @if($feesTrend->count() > 1)
    <section class="dash-card">
        <div class="card-head"><div class="card-title-wrap"><span class="card-icon"><svg class="icon icon-sm"><use href="#i-trending"/></svg></span><div><div class="card-title">Fee Collection Trend</div><div class="card-meta">Revenue performance over the last six months</div></div></div><span class="chart-filter">Last 6 Months</span></div>
        <div class="card-body">@php $maxFee = $feesTrend->max('collected') ?: 1; @endphp<div class="fee-trend">@foreach($feesTrend as $month)<div class="fee-month"><strong>₦{{ number_format($month['collected']/1000,0) }}K</strong><div class="fee-bar" style="height:{{ max(5,($month['collected']/$maxFee)*82) }}px;opacity:{{ .62+($loop->index/max(1,$feesTrend->count()-1))*.38 }}"></div><span>{{ $month['label'] }}</span></div>@endforeach</div></div>
    </section>
    @endif

    <svg aria-hidden="true" style="position:absolute;width:0;height:0;overflow:hidden">
        <symbol id="i-calendar" viewBox="0 0 24 24"><path d="M6 3v3m12-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></symbol>
        <symbol id="i-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Zm-8 12h4"/></symbol>
        <symbol id="i-users" viewBox="0 0 24 24"><path d="M16 20v-1.5a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4V20M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-2a3 3 0 0 1 0 6m2 6v-1.5a4 4 0 0 0-3-3.87"/></symbol>
        <symbol id="i-staff" viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M4 21v-2a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v2M9 17l3 3 3-3"/></symbol>
        <symbol id="i-wallet" viewBox="0 0 24 24"><path d="M3 6h16a2 2 0 0 1 2 2v11H3V6Zm0 0 3-3h12v3m-3 6h6"/><circle cx="16" cy="12" r="1"/></symbol>
        <symbol id="i-check-calendar" viewBox="0 0 24 24"><path d="M6 3v3m12-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/><path d="m8 15 2 2 5-5"/></symbol>
        <symbol id="i-chart" viewBox="0 0 24 24"><path d="M3 20V5m0 15h18M6 16l4-5 4 3 6-8"/></symbol>
        <symbol id="i-card" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></symbol>
        <symbol id="i-school" viewBox="0 0 24 24"><path d="m3 10 9-6 9 6M5 9v11m14-11v11M3 20h18M9 13h6m-6 4h6"/></symbol>
        <symbol id="i-flag" viewBox="0 0 24 24"><path d="M5 22V4m0 0c5-3 7 3 14 0v10c-7 3-9-3-14 0"/></symbol>
        <symbol id="i-alert" viewBox="0 0 24 24"><path d="M12 3 2 21h20L12 3Z"/><path d="M12 9v5m0 3h.01"/></symbol>
        <symbol id="i-shield-check" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></symbol>
        <symbol id="i-pie" viewBox="0 0 24 24"><path d="M12 2v10h10A10 10 0 1 1 12 2Z"/><path d="M15 2.5A10 10 0 0 1 21.5 9H15V2.5Z"/></symbol>
        <symbol id="i-bolt" viewBox="0 0 24 24"><path d="m13 2-9 12h8l-1 8 9-12h-8l1-8Z"/></symbol>
        <symbol id="i-user-plus" viewBox="0 0 24 24"><path d="M15 20v-1a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v1M8 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm11-6v6m-3-3h6"/></symbol>
        <symbol id="i-receipt" viewBox="0 0 24 24"><path d="M5 3h14v19l-3-2-4 2-4-2-3 2V3Zm4 5h6m-6 4h6m-6 4h4"/></symbol>
        <symbol id="i-edit" viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5l4 4L8 20l-5 1 1-5L16.5 3.5Z"/></symbol>
        <symbol id="i-upload" viewBox="0 0 24 24"><path d="M12 16V3m0 0L7 8m5-5 5 5M4 14v7h16v-7"/></symbol>
        <symbol id="i-megaphone" viewBox="0 0 24 24"><path d="m3 11 15-6v14L3 13v-2Zm15-2 3 1v4l-3 1M6 14l2 6h4l-2-5"/></symbol>
        <symbol id="i-trending" viewBox="0 0 24 24"><path d="m3 17 6-6 4 4 8-9M15 6h6v6"/></symbol>
    </svg>
</div>
@endsection
