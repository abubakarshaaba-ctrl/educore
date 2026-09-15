@extends('layouts.app')
@section('title', 'Attendance')
@section('page-title', 'Attendance')

@push('styles')
<style>
    .attendance-analytics {
        display:grid;
        grid-template-columns:minmax(0,2fr) minmax(240px,1fr);
        gap:16px;
        margin-bottom:20px;
    }

    .attendance-trend {
        display:flex;
        align-items:flex-end;
        gap:8px;
        min-height:96px;
    }

    .attendance-trend-day {
        display:flex;
        flex:1 1 0;
        min-width:0;
        flex-direction:column;
        align-items:center;
        justify-content:flex-end;
        gap:4px;
    }

    .attendance-trend-rate {
        font-size:10px;
        font-weight:800;
    }

    .attendance-trend-bar-wrap {
        width:100%;
        height:58px;
        display:flex;
        align-items:flex-end;
    }

    .attendance-trend-bar {
        width:100%;
        min-height:4px;
        border-radius:5px 5px 2px 2px;
    }

    .attendance-trend-label {
        font-size:10px;
        color:var(--brand-gray, var(--slate-light));
        white-space:nowrap;
    }

    .attendance-class-list {
        display:flex;
        flex-direction:column;
        gap:9px;
        max-height:180px;
        overflow-y:auto;
    }

    .attendance-class-row {
        display:grid;
        grid-template-columns:minmax(72px,110px) 1fr 38px;
        gap:8px;
        align-items:center;
        font-size:11px;
    }

    .attendance-class-name {
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
        color:var(--brand-gray, var(--slate));
    }

    .attendance-progress {
        height:6px;
        overflow:hidden;
        border-radius:999px;
        background:#E7EBF0;
    }

    .attendance-progress > span {
        display:block;
        height:100%;
        border-radius:inherit;
    }

    .attendance-class-rate { font-weight:800; text-align:right; }

    .attendance-stats {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:14px;
        margin-bottom:20px;
    }

    .attendance-stat {
        background:#fff;
        border:1px solid var(--brand-border, var(--border));
        border-top:3px solid var(--brand-gold, #D79A21);
        border-radius:12px;
        padding:16px;
        text-align:center;
        box-shadow:0 4px 16px rgba(7,30,69,.05);
    }

    .attendance-stat-value {
        color:var(--brand-navy, var(--midnight));
        font-size:27px;
        font-weight:800;
        letter-spacing:-.035em;
    }

    .attendance-stat-label {
        margin-top:4px;
        color:var(--brand-gray, var(--slate-light));
        font-size:10px;
        font-weight:800;
        letter-spacing:.055em;
        text-transform:uppercase;
    }

    .attendance-launch-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:16px;
    }

    .attendance-empty {
        padding:18px 12px;
        text-align:center;
        color:var(--brand-gray, var(--slate-light));
        font-size:12px;
    }

    .attendance-rate-good { color:#15803D; }
    .attendance-rate-mid { color:#A16207; }
    .attendance-rate-low { color:#B91C1C; }
    .attendance-bg-good { background:#15803D; }
    .attendance-bg-mid { background:#D79A21; }
    .attendance-bg-low { background:#B91C1C; }

    @media(max-width:900px) {
        .attendance-analytics { grid-template-columns:1fr; }
        .attendance-stats { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }

    @media(max-width:640px) {
        .attendance-launch-grid { grid-template-columns:1fr; }
        .attendance-stats { grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .attendance-stat { padding:13px 10px; }
        .attendance-stat-value { font-size:23px; }
    }
</style>
@endpush

@section('content')
@if(!empty($weeklyTrend) && $weeklyTrend->count())
    <div class="attendance-analytics">
        <section class="ec-card" aria-labelledby="attendance-trend-title">
            <div class="ec-card__header">
                <h2 id="attendance-trend-title" class="ec-card__title">Attendance Trend — Last 7 Days</h2>
            </div>
            <div class="ec-card__body">
                @php $maxRate = $weeklyTrend->max('rate') ?: 100; @endphp
                <div class="attendance-trend" aria-label="Seven day attendance trend">
                    @foreach($weeklyTrend as $day)
                        @php
                            $rate = (float) $day['rate'];
                            $rateClass = $rate >= 75 ? 'attendance-rate-good' : ($rate >= 50 ? 'attendance-rate-mid' : 'attendance-rate-low');
                            $barClass = $rate >= 75 ? 'attendance-bg-good' : ($rate >= 50 ? 'attendance-bg-mid' : 'attendance-bg-low');
                            $barHeight = max(4, ($rate / ($maxRate ?: 1)) * 55);
                        @endphp
                        <div class="attendance-trend-day" title="{{ $day['date'] }}: {{ $day['rate'] }}%">
                            <span class="attendance-trend-rate {{ $rateClass }}">{{ $day['rate'] }}%</span>
                            <div class="attendance-trend-bar-wrap" aria-hidden="true">
                                <span class="attendance-trend-bar {{ $barClass }}" style="height:{{ $barHeight }}px"></span>
                            </div>
                            <span class="attendance-trend-label">{{ explode(' ', $day['date'])[0] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="ec-card" aria-labelledby="attendance-class-title">
            <div class="ec-card__header">
                <h2 id="attendance-class-title" class="ec-card__title">Today by Class</h2>
            </div>
            <div class="ec-card__body">
                <div class="attendance-class-list">
                    @forelse($classBreakdown ?? [] as $cls)
                        @php
                            $rate = (float) $cls['rate'];
                            $rateClass = $rate >= 75 ? 'attendance-rate-good' : ($rate >= 50 ? 'attendance-rate-mid' : 'attendance-rate-low');
                            $barClass = $rate >= 75 ? 'attendance-bg-good' : ($rate >= 50 ? 'attendance-bg-mid' : 'attendance-bg-low');
                        @endphp
                        <div class="attendance-class-row">
                            <span class="attendance-class-name" title="{{ $cls['class'] }}">{{ $cls['class'] }}</span>
                            <span class="attendance-progress" aria-hidden="true"><span class="{{ $barClass }}" style="width:{{ min(100, max(0, $rate)) }}%"></span></span>
                            <span class="attendance-class-rate {{ $rateClass }}">{{ $cls['rate'] }}%</span>
                        </div>
                    @empty
                        <div class="attendance-empty">No attendance marked today yet.</div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endif

<nav class="page-tabs" aria-label="Attendance sections">
    <a href="{{ route('attendance.index') }}" class="page-tab active" aria-current="page">Mark Attendance</a>
    <a href="{{ route('attendance.report') }}" class="page-tab">Reports</a>
</nav>

<div class="attendance-stats" aria-label="Today's attendance summary">
    <div class="attendance-stat">
        <div class="attendance-stat-value">{{ $todaySummary['present'] ?? 0 }}</div>
        <div class="attendance-stat-label">Present Today</div>
    </div>
    <div class="attendance-stat">
        <div class="attendance-stat-value">{{ $todaySummary['absent'] ?? 0 }}</div>
        <div class="attendance-stat-label">Absent Today</div>
    </div>
    <div class="attendance-stat">
        <div class="attendance-stat-value">{{ $todaySummary['late'] ?? 0 }}</div>
        <div class="attendance-stat-label">Late Today</div>
    </div>
    <div class="attendance-stat">
        <div class="attendance-stat-value">{{ $todaySummary['excused'] ?? 0 }}</div>
        <div class="attendance-stat-label">Excused Today</div>
    </div>
</div>

<section class="ec-card" aria-labelledby="mark-attendance-title">
    <div class="ec-card__header">
        <h2 id="mark-attendance-title" class="ec-card__title">Mark Attendance</h2>
    </div>
    <div class="ec-card__body">
        <p class="hint" style="margin-top:0">Select an assigned class and attendance date to open the register.</p>

        @if($classArms->isEmpty())
            <div class="alert-warning" role="status">
                You are not assigned as form tutor of any class yet. Contact your administrator for an assignment.
            </div>
        @else
            <form method="GET" action="{{ route('attendance.sheet') }}">
                <div class="attendance-launch-grid">
                    <div class="fg">
                        <label class="fl" for="attendance-class">Class <span aria-hidden="true">*</span></label>
                        <select id="attendance-class" name="class_arm_id" class="fc" required>
                            <option value="">Select class</option>
                            @foreach($classArms as $arm)
                                <option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fl" for="attendance-date">Date <span aria-hidden="true">*</span></label>
                        <input id="attendance-date" type="date" name="date" class="fc" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="ec-actions" style="margin-top:18px">
                    <button type="submit" class="btn btn-primary">Open Attendance Sheet</button>
                    <a href="{{ route('attendance.report') }}" class="btn btn-secondary">View Reports</a>
                </div>
            </form>
        @endif
    </div>
</section>
@endsection