@extends('layouts.app')
@section('title', 'Risk Detail — ' . optional($flag->student)->full_name)
@section('page-title', 'Student Risk Detail')

@push('styles')
<style>
.detail-grid { display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start; }
.card { background:white; border:1px solid var(--border); border-radius:12px; overflow:hidden; margin-bottom:16px; }
.card-head { padding:13px 18px; border-bottom:1px solid var(--border); background:#F8FAFC;
    display:flex; align-items:center; justify-content:space-between; }
.card-title { font-size:13px; font-weight:700; }
.card-body  { padding:20px; }
.section-label { font-size:11px; font-weight:700; text-transform:uppercase;
    letter-spacing:.06em; color:var(--slate-light); margin-bottom:10px; }
.risk-badge { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:700;
    padding:4px 12px; border-radius:20px; }
.risk-critical { background:#FEF2F2; color:#DC2626; }
.risk-high     { background:#FFF7ED; color:#EA580C; }
.risk-medium   { background:#FFFBEB; color:#D97706; }
.risk-low      { background:#F0FDF4; color:#16A34A; }
.score-table { width:100%; border-collapse:collapse; font-size:13px; }
.score-table th { padding:8px 12px; text-align:left; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.05em; color:var(--slate-light);
    border-bottom:1px solid var(--border); background:#F8FAFC; }
.score-table td { padding:9px 12px; border-bottom:1px solid var(--border); }
.score-table tr:last-child td { border:none; }
.score-fail { color:#DC2626; font-weight:700; }
.metric-row { display:flex; justify-content:space-between; align-items:center;
    padding:10px 0; border-bottom:1px solid var(--border); }
.metric-row:last-child { border:none; }
.metric-label { font-size:13px; color:#334155; }
.metric-value { font-weight:700; font-size:14px; color:var(--midnight); }
.risk-bar-wrap { display:flex; align-items:center; gap:8px; }
.risk-bar { flex:1; height:8px; background:#E2E8F0; border-radius:4px; overflow:hidden; min-width:80px; }
.risk-bar-fill { height:100%; border-radius:4px; }
.flag-item { display:flex; align-items:flex-start; gap:8px; padding:8px 0;
    border-bottom:1px solid var(--border); font-size:13px; color:#334155; }
.flag-item:last-child { border:none; }
.flag-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:4px; }
.btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; font-size:13px;
    font-weight:600; font-family:inherit; border:none; border-radius:8px; cursor:pointer;
    transition:all 150ms; text-decoration:none; }
.btn-primary { background:var(--indigo); color:white; }
.btn-success { background:#059669; color:white; }
.btn-ghost   { background:#F1F5F9; color:var(--slate); border:1px solid var(--border); }
.form-control { width:100%; padding:9px 12px; font-size:13px; font-family:inherit;
    border:1.5px solid var(--border); border-radius:8px; background:#F8FAFC;
    outline:none; margin-top:6px; }
.form-control:focus { border-color:var(--indigo); background:white; }
.alert-success { background:#ECFDF5; border:1px solid #A7F3D0; border-radius:10px;
    padding:12px 16px; font-size:13px; color:#059669; margin-bottom:16px; }
@media(max-width:900px) { .detail-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

<div style="margin-bottom:16px">
    <a href="{{ route('risk.index') }}" style="font-size:13px;color:var(--indigo);text-decoration:none">
        ← Back to Risk Dashboard
    </a>
</div>

@if(session('success'))<div class="alert-success">✓ {{ session('success') }}</div>@endif

{{-- Student header --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
        <div style="width:56px;height:56px;border-radius:14px;background:var(--indigo);
            color:white;font-size:22px;font-weight:800;display:flex;align-items:center;
            justify-content:center;flex-shrink:0">
            {{ strtoupper(substr(optional($flag->student)->first_name ?? '?', 0, 1)) }}
        </div>
        <div style="flex:1">
            <div style="font-size:18px;font-weight:800;color:var(--midnight)">
                {{ optional($flag->student)->full_name ?? '—' }}
            </div>
            <div style="font-size:13px;color:var(--slate-light);margin-top:2px">
                {{ optional($flag->student)->admission_number }}
                &nbsp;·&nbsp;
                {{ optional(optional(optional($flag->student)->currentClassArm)->classLevel)->name }}
                {{ optional(optional($flag->student)->currentClassArm)->name }}
                &nbsp;·&nbsp;
                {{ optional($flag->term)->name }}
            </div>
        </div>
        <span class="risk-badge risk-{{ $flag->risk_level }}" style="font-size:14px;padding:6px 16px">
            {{ $flag->composite_risk }}% — {{ ucfirst($flag->risk_level) }} Risk
        </span>
    </div>
</div>

<div class="detail-grid">

    {{-- ── LEFT ──────────────────────────────────────────────────────── --}}
    <div>

        {{-- Risk breakdown --}}
        <div class="card">
            <div class="card-head"><span class="card-title">📊 Risk Score Breakdown</span></div>
            <div class="card-body">
                <div class="metric-row">
                    <span class="metric-label">Academic Risk</span>
                    <div class="risk-bar-wrap">
                        <div class="risk-bar">
                            <div class="risk-bar-fill" style="width:{{ $flag->academic_risk }}%;background:{{ $flag->academic_risk >= 70 ? '#DC2626' : ($flag->academic_risk >= 40 ? '#D97706' : '#16A34A') }}"></div>
                        </div>
                        <span class="metric-value">{{ $flag->academic_risk }}/100</span>
                    </div>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Attendance Risk</span>
                    <div class="risk-bar-wrap">
                        <div class="risk-bar">
                            <div class="risk-bar-fill" style="width:{{ $flag->attendance_risk }}%;background:{{ $flag->attendance_risk >= 70 ? '#DC2626' : ($flag->attendance_risk >= 40 ? '#D97706' : '#16A34A') }}"></div>
                        </div>
                        <span class="metric-value">{{ $flag->attendance_risk }}/100</span>
                    </div>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Fee Risk</span>
                    <div class="risk-bar-wrap">
                        <div class="risk-bar">
                            <div class="risk-bar-fill" style="width:{{ $flag->fee_risk }}%;background:{{ $flag->fee_risk >= 70 ? '#DC2626' : ($flag->fee_risk >= 40 ? '#D97706' : '#16A34A') }}"></div>
                        </div>
                        <span class="metric-value">{{ $flag->fee_risk }}/100</span>
                    </div>
                </div>
                <div class="metric-row" style="border-top:2px solid var(--border);margin-top:4px;padding-top:12px">
                    <span class="metric-label" style="font-weight:700">Composite Risk Score</span>
                    <span class="metric-value" style="font-size:20px">{{ $flag->composite_risk }}/100</span>
                </div>
            </div>
        </div>

        {{-- Risk flags --}}
        @if($flag->flags)
        <div class="card">
            <div class="card-head"><span class="card-title">🚩 Risk Flags ({{ count($flag->flags) }})</span></div>
            <div class="card-body">
                @foreach($flag->flagLabels() as $label)
                <div class="flag-item">
                    <div class="flag-dot" style="background:#DC2626"></div>
                    {{ $label }}
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Scores --}}
        @if($scores->count())
        <div class="card">
            <div class="card-head"><span class="card-title">📝 Score Breakdown (This Term)</span></div>
            <div style="overflow-x:auto">
                <table class="score-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($scores as $subjectId => $subjectScores)
                        @php
                            $subjectName = optional(optional($subjectScores->first())->subject)->name ?? 'Unknown';
                            $subjectTotal = $subjectScores->sum('score');
                            $isFail = $subjectTotal < 40;
                        @endphp
                        @foreach($subjectScores as $score)
                        <tr>
                            <td>
                                @if($loop->first)
                                <span style="font-weight:600{{ $isFail ? ';color:#DC2626' : '' }}">
                                    {{ $subjectName }}
                                    @if($isFail) <span style="font-size:11px">(FAIL)</span>@endif
                                </span>
                                @endif
                            </td>
                            <td style="font-size:12px;color:var(--slate-light)">
                                {{ optional(optional($score)->assessmentType)->name ?? '—' }}
                            </td>
                            <td class="{{ $score->score < 40 ? 'score-fail' : '' }}">
                                {{ number_format($score->score, 1) }}
                            </td>
                        </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Attendance --}}
        <div class="card">
            <div class="card-head"><span class="card-title">📅 Attendance (This Term)</span></div>
            <div class="card-body">
                <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:16px">
                    <div style="text-align:center">
                        <div style="font-size:32px;font-weight:800;color:var(--midnight)">{{ $attendanceRate }}%</div>
                        <div style="font-size:11px;color:var(--slate-light)">Presence Rate</div>
                    </div>
                    <div style="text-align:center">
                        <div style="font-size:32px;font-weight:800;color:#059669">{{ $presentDays }}</div>
                        <div style="font-size:11px;color:var(--slate-light)">Days Present</div>
                    </div>
                    <div style="text-align:center">
                        <div style="font-size:32px;font-weight:800;color:#DC2626">{{ $totalDays - $presentDays }}</div>
                        <div style="font-size:11px;color:var(--slate-light)">Days Absent</div>
                    </div>
                    <div style="text-align:center">
                        <div style="font-size:32px;font-weight:800">{{ $totalDays }}</div>
                        <div style="font-size:11px;color:var(--slate-light)">Total Days</div>
                    </div>
                </div>
                @if($totalDays > 0)
                <div style="background:#E2E8F0;border-radius:8px;height:12px;overflow:hidden">
                    <div style="height:100%;width:{{ $attendanceRate }}%;background:{{ $attendanceRate < 60 ? '#DC2626' : ($attendanceRate < 75 ? '#D97706' : '#059669') }};border-radius:8px;transition:width 600ms"></div>
                </div>
                @endif
            </div>
        </div>

        {{-- Outstanding fees --}}
        @if($outstanding->count())
        <div class="card">
            <div class="card-head"><span class="card-title">💳 Outstanding Fees</span></div>
            <div class="card-body">
                @foreach($outstanding as $inv)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
                    <div>
                        <div style="font-size:13px;font-weight:600">{{ $inv->invoice_number }}</div>
                        <div style="font-size:11px;color:var(--slate-light)">Due {{ $inv->due_date }}</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-weight:700;color:#DC2626">₦{{ number_format($inv->total_amount - $inv->amount_paid) }}</div>
                        <div style="font-size:11px;color:var(--slate-light)">outstanding</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    {{-- ── RIGHT: Actions ─────────────────────────────────────────────── --}}
    <div>

        {{-- Trend --}}
        @if($prevSummary)
        <div class="card">
            <div class="card-head"><span class="card-title">📈 Previous Term</span></div>
            <div class="card-body">
                <div class="metric-row">
                    <span class="metric-label">Average</span>
                    <span class="metric-value">{{ number_format($prevSummary->final_average, 1) }}%</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Subjects Failed</span>
                    <span class="metric-value {{ $prevSummary->subjects_failed > 0 ? 'score-fail' : '' }}">
                        {{ $prevSummary->subjects_failed }}
                    </span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Position</span>
                    <span class="metric-value">
                        {{ $prevSummary->position_in_class }}/{{ $prevSummary->total_students_in_class }}
                    </span>
                </div>
            </div>
        </div>
        @endif

        {{-- Acknowledge --}}
        @if($flag->status === 'open')
        <div class="card">
            <div class="card-head"><span class="card-title">✅ Acknowledge Flag</span></div>
            <div class="card-body">
                <p style="font-size:13px;color:#475569;margin-bottom:12px">
                    Acknowledging means you have seen this flag and are planning an intervention.
                </p>
                <form method="POST" action="{{ route('risk.acknowledge', $flag) }}">
                    @csrf
                    <textarea name="intervention_note" class="form-control" rows="3"
                              placeholder="Note what action you plan to take..."></textarea>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:10px">
                        ✅ Acknowledge
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Resolve --}}
        @if(in_array($flag->status, ['open', 'acknowledged']))
        <div class="card">
            <div class="card-head"><span class="card-title">🎯 Mark Resolved</span></div>
            <div class="card-body">
                <p style="font-size:13px;color:#475569;margin-bottom:12px">
                    Mark as resolved once the student's situation has improved.
                </p>
                <form method="POST" action="{{ route('risk.resolve', $flag) }}">
                    @csrf
                    <textarea name="intervention_note" class="form-control" rows="3"
                              placeholder="Describe what intervention worked...">{{ $flag->intervention_note }}</textarea>
                    <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;margin-top:10px">
                        🎯 Mark as Resolved
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- History --}}
        @if($flag->acknowledged_at || $flag->resolved_at)
        <div class="card">
            <div class="card-head"><span class="card-title">📋 Intervention Log</span></div>
            <div class="card-body">
                @if($flag->acknowledged_at)
                <div style="margin-bottom:12px">
                    <div style="font-size:11px;color:var(--slate-light)">Acknowledged</div>
                    <div style="font-size:13px;font-weight:600">{{ optional($flag->acknowledgedBy)->name }}</div>
                    <div style="font-size:12px;color:var(--slate-light)">{{ $flag->acknowledged_at->format('d M Y H:i') }}</div>
                </div>
                @endif
                @if($flag->intervention_note)
                <div style="background:#F8FAFC;border-radius:8px;padding:10px 12px;font-size:13px;color:#334155;margin-bottom:12px">
                    {{ $flag->intervention_note }}
                </div>
                @endif
                @if($flag->resolved_at)
                <div>
                    <div style="font-size:11px;color:var(--slate-light)">Resolved</div>
                    <div style="font-size:13px;font-weight:600;color:#059669">{{ optional($flag->resolvedBy)->name }}</div>
                    <div style="font-size:12px;color:var(--slate-light)">{{ $flag->resolved_at->format('d M Y H:i') }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <a href="{{ route('students.show', optional($flag->student)->id ?? 0) }}"
           class="btn btn-ghost" style="width:100%;justify-content:center">
            View Full Student Profile →
        </a>
    </div>

</div>
@endsection
