@extends('layouts.app')
@section('title','Promotion Engine')
@section('page-title','Promotion Engine')

@push('styles')
<style>
.tabs{display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content}
.tab{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms}
.tab.active{background:var(--indigo);color:white}.tab:hover:not(.active){background:#F1F5F9}
.filter-card{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 18px;margin-bottom:16px;display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap}
.fg{display:flex;flex-direction:column;gap:5px}.fl{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;min-width:200px}
.fc:focus{border-color:var(--indigo)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-p:hover{background:#1D4ED8}
.btn-g{background:#059669;color:white}.btn-g:hover{background:#047857}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.kpi{background:white;border:1px solid var(--border);border-radius:12px;padding:14px 18px;text-align:center}
.kpi-val{font-size:26px;font-weight:800}.kpi-lbl{font-size:11px;color:var(--slate-light);margin-top:3px;text-transform:uppercase;letter-spacing:.06em}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:12px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);text-align:left}
td{padding:9px 14px;border-bottom:1px solid var(--border);color:var(--midnight);vertical-align:middle}
tr:hover td{background:#FAFBFF}
.badge{display:inline-flex;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px}
.b-promote{background:#ECFDF5;color:#059669}.b-repeat{background:#FEF2F2;color:#DC2626}.b-nodata{background:#F1F5F9;color:#64748B}
.reason{font-size:11px;color:var(--crimson);margin-top:2px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:#059669;margin-bottom:16px}
.rule-box{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#1E40AF}
.check-all{font-size:12px;color:var(--indigo);cursor:pointer;font-weight:600;text-decoration:underline}
@media(max-width:768px){.kpi-row{grid-template-columns:1fr 1fr}}
</style>
@endpush

@section('content')
<div class="tabs">
    <a href="{{ route('settings.promotion') }}"         class="tab">⚙️ Rules</a>
    <a href="{{ route('classes.promotion.preview') }}" class="tab active">🚀 Run Promotion</a>
    <a href="{{ route('classes.promotion.history') }}" class="tab">📋 History</a>
    <a href="{{ route('classes.bulk-promote.page') }}" class="tab">👥 Manual Bulk</a>
</div>

@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif

{{-- Filters --}}
<form method="GET" action="{{ route('classes.promotion.preview') }}">
<div class="filter-card">
    <div class="fg">
        <label class="fl">Class Arm *</label>
        <select name="class_arm_id" class="fc" required>
            <option value="">Select class...</option>
            @foreach($classArms as $a)
            <option value="{{ $a->id }}" {{ isset($arm) && $arm->id==$a->id ? 'selected':'' }}>
                {{ optional($a->classLevel)->name }} {{ $a->name }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="fg">
        <label class="fl">Term *</label>
        <select name="term_id" class="fc" required>
            <option value="">Select term...</option>
            @foreach($terms as $t)
            <option value="{{ $t->id }}" {{ isset($term) && $term->id==$t->id ? 'selected':'' }}>
                {{ $t->name }} — {{ optional($t->session)->name }}
            </option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-p">Preview Promotion</button>
</div>
</form>

@if(!isset($results))
<div style="background:white;border:1px solid var(--border);border-radius:12px;padding:50px;text-align:center;color:var(--slate-light)">
    <div style="font-size:40px;margin-bottom:12px">🎓</div>
    <div style="font-size:15px;font-weight:600;color:var(--slate)">Promotion Engine</div>
    <div style="font-size:13px;margin-top:6px">Select a class and term to preview which students pass promotion criteria.</div>
</div>
@else

{{-- Rule summary --}}
@if($rule)
<div class="rule-box">
    📏 <strong>Promotion Rule for {{ optional($arm->classLevel)->name }}:</strong>
    Min average: <strong>{{ $rule->min_required_average ?? 'None' }}%</strong> &nbsp;·&nbsp;
    Max failed subjects: <strong>{{ $rule->max_failed_subjects_allowed ?? 'No limit' }}</strong>
    @if(!empty($rule->compulsory_subject_ids))
    &nbsp;·&nbsp; Compulsory subjects: <strong>{{ count($rule->compulsory_subject_ids) }}</strong>
    @endif
</div>
@else
<div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:10px 16px;font-size:13px;color:#92400E;margin-bottom:16px">
    ⚠️ No promotion rule set for <strong>{{ optional($arm->classLevel)->name }}</strong>.
    All students with report cards will be marked eligible.
    <a href="{{ route('settings.promotion') }}" style="color:var(--indigo);font-weight:600;margin-left:8px">Set rules →</a>
</div>
@endif

{{-- KPI strip --}}
<div class="kpi-row">
    <div class="kpi"><div class="kpi-val">{{ count($results) }}</div><div class="kpi-lbl">Total Students</div></div>
    <div class="kpi"><div class="kpi-val" style="color:#059669">{{ $promoteCount }}</div><div class="kpi-lbl">Eligible to Promote</div></div>
    <div class="kpi"><div class="kpi-val" style="color:#DC2626">{{ $repeatCount }}</div><div class="kpi-lbl">To Repeat</div></div>
    <div class="kpi"><div class="kpi-val" style="color:#D97706">{{ collect($results)->whereNull('summary')->count() }}</div><div class="kpi-lbl">No Report Card</div></div>
</div>

{{-- Run form --}}
<form method="POST" action="{{ route('classes.promotion.run') }}" id="promoForm">
@csrf
<input type="hidden" name="class_arm_id" value="{{ $arm->id }}">
<input type="hidden" name="term_id" value="{{ $term->id }}">

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <div class="fg">
        <label class="fl">Promote eligible students to *</label>
        <select name="promote_to_arm_id" class="fc" required>
            <option value="">Select destination class...</option>
            @foreach($nextArms as $na)
            <option value="{{ $na->id }}">{{ optional($na->classLevel)->name }} {{ $na->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="fg">
        <label class="fl">Repeat students stay in (optional)</label>
        <select name="repeat_arm_id" class="fc">
            <option value="">Same class ({{ optional($arm->classLevel)->name }} {{ $arm->name }})</option>
            @foreach($classArms as $ca)
            <option value="{{ $ca->id }}">{{ optional($ca->classLevel)->name }} {{ $ca->name }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- Student table --}}
<div class="card">
    <div class="ch">
        <span>Preview Results — {{ optional($arm->classLevel)->name }} {{ $arm->name }}</span>
        <div style="display:flex;gap:10px">
            <span class="check-all" onclick="setAll('promote', true)">✓ Select All Eligible</span>
            <span class="check-all" onclick="setAll('repeat', true)" style="color:var(--crimson)">✓ Select All Repeat</span>
        </div>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th style="width:36px"></th>
                <th>Student</th>
                <th>Average</th>
                <th>Subjects Failed</th>
                <th>Position</th>
                <th>Recommendation</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($results as $r)
        <tr>
            <td>
                @if($r['can_promote'])
                <input type="checkbox" name="student_ids[]" value="{{ $r['student']->id }}"
                    class="promote-chk" {{ $r['can_promote'] ? 'checked':'' }}>
                @else
                <input type="checkbox" name="repeat_ids[]" value="{{ $r['student']->id }}"
                    class="repeat-chk" checked>
                @endif
            </td>
            <td>
                <div style="font-weight:600">{{ $r['student']->full_name }}</div>
                <div style="font-size:11px;color:var(--slate-light)">{{ $r['student']->admission_number }}</div>
            </td>
            <td>
                @if($r['summary'])
                <span style="font-weight:700;color:{{ $r['summary']->final_average >= ($rule->min_required_average ?? 0) ? 'var(--emerald)':'var(--crimson)' }}">
                    {{ number_format($r['summary']->final_average,1) }}%
                </span>
                @else <span style="color:var(--slate-light)">—</span> @endif
            </td>
            <td>{{ optional($r['summary'])->subjects_failed ?? '—' }}</td>
            <td>{{ optional($r['summary'])->position_in_class ? $r['summary']->position_in_class.'/'.$r['summary']->total_students_in_class : '—' }}</td>
            <td>
                @if($r['can_promote'])
                    <span class="badge b-promote">✓ Promote</span>
                @elseif(!$r['summary'])
                    <span class="badge b-nodata">No Data</span>
                @else
                    <span class="badge b-repeat">Repeat</span>
                    @foreach($r['reasons'] as $reason)
                    <div class="reason">{{ $reason }}</div>
                    @endforeach
                @endif
            </td>
            <td>
                @if($r['can_promote'])
                    <span style="font-size:12px;color:#059669">→ Promote</span>
                @else
                    <span style="font-size:12px;color:#DC2626">↺ Repeat</span>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

<div style="display:flex;align-items:center;gap:12px;padding:16px 0">
    <button type="submit" class="btn btn-g" onclick="return confirm('Run promotion for selected students? This will update their class assignments.')">
        🎓 Execute Promotion
    </button>
    <span style="font-size:12px;color:var(--slate-light)">
        This will update student class assignments and mark promotion status in report cards.
    </span>
</div>
</form>
@endif
@endsection

@push('scripts')
<script>
function setAll(type, checked) {
    const cls = type === 'promote' ? '.promote-chk' : '.repeat-chk';
    document.querySelectorAll(cls).forEach(c => c.checked = checked);
}
</script>
@endpush
