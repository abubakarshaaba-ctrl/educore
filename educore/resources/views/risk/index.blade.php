@extends('layouts.app')
@section('title', 'Academic Risk Flags')
@section('page-title', 'Academic Risk Flagging')

@push('styles')
<style>
.risk-grid { display:grid; grid-template-columns:1fr 300px; gap:20px; align-items:start; }
.card { background:white; border:1px solid var(--border); border-radius:12px; overflow:hidden; }
.card-head { padding:13px 18px; border-bottom:1px solid var(--border); background:#F8FAFC;
    display:flex; align-items:center; justify-content:space-between; }
.card-title { font-size:13px; font-weight:700; }
.card-body  { padding:18px; }

/* Summary row */
.risk-summary { display:grid; grid-template-columns:repeat(6,1fr); gap:10px; margin-bottom:20px; }
.rs-card { background:white; border:1px solid var(--border); border-radius:10px; padding:12px 14px; text-align:center; }
.rs-val { font-size:24px; font-weight:800; letter-spacing:-0.03em; }
.rs-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
    color:var(--slate-light); margin-top:3px; }

/* Filter bar */
.filter-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; align-items:flex-end; }
.filter-group { display:flex; flex-direction:column; gap:4px; }
.filter-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--slate-light); }
.filter-control { padding:7px 11px; font-size:13px; font-family:inherit;
    border:1.5px solid var(--border); border-radius:8px; background:#F8FAFC; outline:none; }
.filter-control:focus { border-color:var(--indigo); background:white; }
.btn { display:inline-flex; align-items:center; gap:6px; padding:9px 16px; font-size:13px;
    font-weight:600; font-family:inherit; border:none; border-radius:8px; cursor:pointer; transition:all 150ms; }
.btn-primary { background:var(--indigo); color:white; }
.btn-danger  { background:#DC2626; color:white; }
.btn-ghost   { background:#F1F5F9; color:var(--slate); border:1px solid var(--border); }

/* Risk table */
.risk-table { width:100%; border-collapse:collapse; font-size:13px; }
.risk-table th { padding:9px 12px; text-align:left; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.05em; color:var(--slate-light);
    border-bottom:1px solid var(--border); background:#F8FAFC; white-space:nowrap; }
.risk-table td { padding:10px 12px; border-bottom:1px solid var(--border); vertical-align:middle; }
.risk-table tr:last-child td { border:none; }
.risk-table tr:hover td { background:#FAFBFC; }

/* Risk level badge */
.risk-badge { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:700;
    padding:3px 10px; border-radius:20px; white-space:nowrap; }
.risk-critical { background:#FEF2F2; color:#DC2626; }
.risk-high     { background:#FFF7ED; color:#EA580C; }
.risk-medium   { background:#FFFBEB; color:#D97706; }
.risk-low      { background:#F0FDF4; color:#16A34A; }

/* Risk bar */
.risk-bar-wrap { display:flex; align-items:center; gap:8px; }
.risk-bar { flex:1; height:6px; background:#E2E8F0; border-radius:3px; overflow:hidden; }
.risk-bar-fill { height:100%; border-radius:3px; transition:width 400ms; }
.risk-num { font-size:12px; font-weight:700; color:var(--midnight); min-width:28px; text-align:right; }

/* Flag chips */
.flag-chips { display:flex; flex-wrap:wrap; gap:4px; }
.flag-chip { font-size:10px; font-weight:600; padding:2px 7px; border-radius:20px;
    background:#F1F5F9; color:#475569; }

/* Status badge */
.status-open  { background:#FFFBEB; color:#D97706; }
.status-acknowledged { background:#EFF6FF; color:#2563EB; }
.status-resolved     { background:#F0FDF4; color:#16A34A; }

/* Config form */
.config-row { display:grid; grid-template-columns:1fr 80px; gap:10px; align-items:center; margin-bottom:12px; }
.config-label { font-size:13px; color:#334155; }
.config-input { padding:7px 10px; font-size:13px; font-family:inherit;
    border:1.5px solid var(--border); border-radius:8px; text-align:center; width:100%; outline:none; }
.config-input:focus { border-color:var(--indigo); }

.alert-success { background:#ECFDF5; border:1px solid #A7F3D0; border-radius:10px;
    padding:12px 16px; font-size:13px; color:#059669; margin-bottom:16px; }
.alert-error   { background:#FEF2F2; border:1px solid #FECACA; border-radius:10px;
    padding:12px 16px; font-size:13px; color:#DC2626; margin-bottom:16px; }

.empty-state { text-align:center; padding:48px 20px; color:var(--slate-light); }
.empty-icon  { font-size:40px; margin-bottom:12px; }

@media(max-width:960px){ .risk-grid { grid-template-columns:1fr; } .risk-summary { grid-template-columns:repeat(3,1fr); } }
</style>
@endpush

@section('content')

@if(session('success'))<div class="alert-success">&#10003; {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

{{-- Summary row --}}
<div class="risk-summary">
    <div class="rs-card">
        <div class="rs-val" style="color:#DC2626">{{ optional($summary)->critical ?? 0 }}</div>
        <div class="rs-label">Critical</div>
    </div>
    <div class="rs-card">
        <div class="rs-val" style="color:#EA580C">{{ optional($summary)->high ?? 0 }}</div>
        <div class="rs-label">High</div>
    </div>
    <div class="rs-card">
        <div class="rs-val" style="color:#D97706">{{ optional($summary)->medium ?? 0 }}</div>
        <div class="rs-label">Medium</div>
    </div>
    <div class="rs-card">
        <div class="rs-val" style="color:#D97706">{{ optional($summary)->open ?? 0 }}</div>
        <div class="rs-label">Open</div>
    </div>
    <div class="rs-card">
        <div class="rs-val" style="color:#2563EB">{{ optional($summary)->acknowledged ?? 0 }}</div>
        <div class="rs-label">Acknowledged</div>
    </div>
    <div class="rs-card">
        <div class="rs-val" style="color:#16A34A">{{ optional($summary)->resolved ?? 0 }}</div>
        <div class="rs-label">Resolved</div>
    </div>
</div>

<div class="risk-grid">

    {{-- ── LEFT: Flags Table ──────────────────────────────────────────── --}}
    <div>
        {{-- Compute trigger --}}
        <form method="POST" action="{{ route('risk.compute') }}" style="margin-bottom:16px">
            @csrf
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                <div class="filter-group">
                    <span class="filter-label">Term</span>
                    <select name="term_id" class="filter-control">
                        @foreach($terms as $t)
                        <option value="{{ $t->id }}" {{ $t->id == $selectedTermId ? 'selected' : '' }}>
                            {{ $t->name }} — {{ optional($t->session)->name }}{{ $t->is_current ? ' (Current)' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <span class="filter-label">Class Level</span>
                    <select name="class_level_id" class="filter-control">
                        <option value="">All Levels</option>
                        @foreach($classLevels as $l)
                        <option value="{{ $l->id }}" {{ $l->id == $selectedLevel ? 'selected' : '' }}>{{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-danger"
                        onclick="return confirm('Run risk analysis? This will compute risk scores for all active students in the selected scope.')">
                    ⚡ Run Risk Analysis
                </button>
            </div>
        </form>

        {{-- Filter flags --}}
        <form method="GET" action="{{ route('risk.index') }}">
            <input type="hidden" name="term_id" value="{{ $selectedTermId }}">
            <div class="filter-bar">
                <div class="filter-group">
                    <span class="filter-label">Class Level</span>
                    <select name="class_level_id" class="filter-control" onchange="this.form.submit()">
                        <option value="">All Levels</option>
                        @foreach($classLevels as $l)
                        <option value="{{ $l->id }}" {{ $l->id == $selectedLevel ? 'selected' : '' }}>{{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <span class="filter-label">Risk Level</span>
                    <select name="risk_level" class="filter-control" onchange="this.form.submit()">
                        <option value="">All Levels</option>
                        <option value="critical" {{ $selectedLevel_ === 'critical' ? 'selected' : '' }}>🔴 Critical</option>
                        <option value="high"     {{ $selectedLevel_ === 'high'     ? 'selected' : '' }}>🟠 High</option>
                        <option value="medium"   {{ $selectedLevel_ === 'medium'   ? 'selected' : '' }}>🟡 Medium</option>
                    </select>
                </div>
                <div class="filter-group">
                    <span class="filter-label">Status</span>
                    <select name="status" class="filter-control" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="open"         {{ $selectedStatus === 'open'         ? 'selected' : '' }}>Open</option>
                        <option value="acknowledged" {{ $selectedStatus === 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                        <option value="resolved"     {{ $selectedStatus === 'resolved'     ? 'selected' : '' }}>Resolved</option>
                    </select>
                </div>
            </div>
        </form>

        <div class="card">
            @if($flags->count())
            <div style="overflow-x:auto">
                <table class="risk-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Risk</th>
                            <th>Academic</th>
                            <th>Attendance</th>
                            <th>Flags</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($flags as $flag)
                    <tr>
                        <td>
                            <a href="{{ route('risk.show', $flag) }}" style="text-decoration:none">
                                <div style="font-weight:600;color:var(--midnight)">
                                    {{ optional($flag->student)->full_name ?? '—' }}
                                </div>
                                <div style="font-size:11px;color:var(--slate-light)">
                                    {{ optional($flag->student)->admission_number ?? '' }}
                                </div>
                            </a>
                        </td>
                        <td style="font-size:12px">
                            {{ optional(optional(optional($flag->student)->currentClassArm)->classLevel)->name }}
                            {{ optional(optional($flag->student)->currentClassArm)->name }}
                        </td>
                        <td>
                            <span class="risk-badge risk-{{ $flag->risk_level }}">
                                {{ $flag->composite_risk }}% {{ ucfirst($flag->risk_level) }}
                            </span>
                        </td>
                        <td>
                            <div class="risk-bar-wrap">
                                <div class="risk-bar">
                                    <div class="risk-bar-fill" style="width:{{ $flag->academic_risk }}%;background:{{ $flag->academic_risk >= 70 ? '#DC2626' : ($flag->academic_risk >= 40 ? '#D97706' : '#16A34A') }}"></div>
                                </div>
                                <span class="risk-num">{{ $flag->academic_risk }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="risk-bar-wrap">
                                <div class="risk-bar">
                                    <div class="risk-bar-fill" style="width:{{ $flag->attendance_risk }}%;background:{{ $flag->attendance_risk >= 70 ? '#DC2626' : ($flag->attendance_risk >= 40 ? '#D97706' : '#16A34A') }}"></div>
                                </div>
                                <span class="risk-num">{{ $flag->attendance_risk }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="flag-chips">
                                @foreach(array_slice($flag->flags ?? [], 0, 2) as $f)
                                <span class="flag-chip">{{ ucwords(str_replace('_',' ',$f)) }}</span>
                                @endforeach
                                @if(count($flag->flags ?? []) > 2)
                                <span class="flag-chip">+{{ count($flag->flags)-2 }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="risk-badge status-{{ $flag->status }}">
                                {{ ucfirst($flag->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('risk.show', $flag) }}"
                               style="font-size:12px;color:var(--indigo);text-decoration:none;font-weight:600">
                                View →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:12px 18px;border-top:1px solid var(--border)">
                {{ $flags->links() }}
            </div>
            @else
            <div class="empty-state">
                <div class="empty-icon">✅</div>
                <div style="font-weight:700;font-size:15px;color:var(--midnight);margin-bottom:6px">No risk flags found</div>
                <div style="font-size:13px">
                    @if($selectedTermId)
                        Run the risk analysis above to scan students for academic risks.
                    @else
                        Select a term and run the risk analysis.
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ── RIGHT: Config ──────────────────────────────────────────────── --}}
    <div>
        <div class="card">
            <div class="card-head">
                <span class="card-title">⚙️ Risk Thresholds</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('risk.config.save') }}">
                    @csrf
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);margin-bottom:10px">Academic</div>
                    <div class="config-row">
                        <span class="config-label">Avg below (at risk)</span>
                        <input type="number" name="academic_threshold" class="config-input"
                               value="{{ $config->academic_threshold }}" step="0.5" min="0" max="100">
                    </div>
                    <div class="config-row">
                        <span class="config-label">Subjects failed (at risk)</span>
                        <input type="number" name="subjects_failed_threshold" class="config-input"
                               value="{{ $config->subjects_failed_threshold }}" min="1">
                    </div>

                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);margin:14px 0 10px">Attendance</div>
                    <div class="config-row">
                        <span class="config-label">Presence rate below (%)</span>
                        <input type="number" name="attendance_threshold" class="config-input"
                               value="{{ $config->attendance_threshold }}" step="0.5" min="0" max="100">
                    </div>

                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);margin:14px 0 10px">Fees</div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
                        <input type="checkbox" name="include_fee_risk" value="1" id="feeRisk"
                               {{ $config->include_fee_risk ? 'checked' : '' }} style="accent-color:var(--indigo)">
                        <label for="feeRisk" style="font-size:13px;cursor:pointer">Include outstanding fees in risk score</label>
                    </div>

                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);margin:14px 0 10px">
                        Weights (must sum to 100)
                    </div>
                    <div class="config-row">
                        <span class="config-label">Academic weight</span>
                        <input type="number" name="academic_weight" class="config-input weight-input"
                               value="{{ $config->academic_weight }}" min="0" max="100">
                    </div>
                    <div class="config-row">
                        <span class="config-label">Attendance weight</span>
                        <input type="number" name="attendance_weight" class="config-input weight-input"
                               value="{{ $config->attendance_weight }}" min="0" max="100">
                    </div>
                    <div class="config-row">
                        <span class="config-label">Fee weight</span>
                        <input type="number" name="fee_weight" class="config-input weight-input"
                               value="{{ $config->fee_weight }}" min="0" max="100">
                    </div>
                    <div id="weightTotal" style="font-size:11px;margin-bottom:12px;color:#94A3B8;text-align:right">
                        Total: {{ $config->academic_weight + $config->attendance_weight + $config->fee_weight }}/100
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                        Save Thresholds
                    </button>
                </form>
            </div>
        </div>

        {{-- Legend --}}
        <div class="card" style="margin-top:16px">
            <div class="card-head"><span class="card-title">📖 Risk Levels</span></div>
            <div class="card-body" style="padding:14px 18px">
                @foreach(['critical'=>['70–100%','#DC2626','#FEF2F2'],'high'=>['50–69%','#EA580C','#FFF7ED'],'medium'=>['25–49%','#D97706','#FFFBEB'],'low'=>['0–24%','#16A34A','#F0FDF4']] as $level=>[$range,$color,$bg])
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                    <span style="display:inline-block;width:72px;text-align:center;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:{{$bg}};color:{{$color}}">{{ ucfirst($level) }}</span>
                    <span style="font-size:12px;color:#475569">Composite {{ $range }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
// Live weight total counter
document.querySelectorAll('.weight-input').forEach(el => {
    el.addEventListener('input', () => {
        const inputs = document.querySelectorAll('.weight-input');
        const total  = Array.from(inputs).reduce((s, i) => s + (parseInt(i.value)||0), 0);
        const el_    = document.getElementById('weightTotal');
        el_.textContent = `Total: ${total}/100`;
        el_.style.color = total === 100 ? '#16A34A' : '#DC2626';
    });
});
</script>
@endpush
