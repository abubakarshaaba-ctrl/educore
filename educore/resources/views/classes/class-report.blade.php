@extends('layouts.app')
@section('title','Class Report')
@section('page-title','Class Performance Report')
@push('styles')
<style>
.filter-card{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 18px;margin-bottom:16px;display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap}
.fg{display:flex;flex-direction:column;gap:5px}
.fl{font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;min-width:200px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#F8FAFC}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.bg{background:#ECFDF5;color:var(--emerald)}.ba{background:#FFFBEB;color:var(--amber)}.br{background:#FEF2F2;color:var(--crimson)}
</style>
@endpush
@section('content')
<form method="GET">
<div class="filter-card">
    <div class="fg"><span class="fl">Class</span>
        <select name="class_arm_id" class="fc">
            <option value="">Select class</option>
            @foreach($classArms as $arm)<option value="{{ $arm->id }}" {{ request('class_arm_id')==$arm->id?'selected':'' }}>{{ $arm->classLevel->name }} {{ $arm->name }}</option>@endforeach
        </select>
    </div>
    <div class="fg"><span class="fl">Term</span>
        <select name="term_id" class="fc">
            <option value="">Select term</option>
            @foreach($terms as $term)<option value="{{ $term->id }}" {{ request('term_id')==$term->id?'selected':'' }}>{{ $term->name }} — {{ $term->session->name }}</option>@endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-p">Generate Report</button>
    @if(request('class_arm_id') && request('term_id'))
    <a href="{{ Route::has('exports.index') ? route('exports.index') : '#' }}" class="btn" style="background:white;border:1px solid var(--border);color:var(--midnight)">⬇ Export CSV</a>
    @endif
</div>
</form>
@if(isset($summaries))
<div class="card">
    <div class="ch">
        {{ $classArm->classLevel->name }} {{ $classArm->name }} — {{ $term->name }}
        <span style="font-size:12px;font-weight:400;color:var(--slate)">{{ $summaries->count() }} students</span>
    </div>
    <div class="tbl"><table>
        <thead><tr><th>Pos</th><th>Student</th><th>Adm No</th><th>Total</th><th>Average</th><th>Failed</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($summaries as $s)
        @php $avg=$s->final_average;$st=$avg>=75?'Distinction':($avg>=60?'Merit':($avg>=50?'Credit':'Below Avg')); @endphp
        <tr>
            <td style="font-weight:700;color:var(--indigo)">{{ $s->position_in_class }}<sup>{{ [1=>'st',2=>'nd',3=>'rd'][$s->position_in_class%10]??'th' }}</sup></td>
            <td><strong>{{ $s->student->full_name }}</strong></td>
            <td style="font-size:11px;color:var(--slate-light)">{{ $s->student->admission_number }}</td>
            <td>{{ $s->total_score }}</td>
            <td><span class="badge {{ $avg>=70?'bg':($avg>=50?'ba':'br') }}">{{ $avg }}</span></td>
            <td style="color:{{ $s->subjects_failed>0?'var(--crimson)':'' }}">{{ $s->subjects_failed }}</td>
            <td style="font-size:11px;text-transform:capitalize">{{ $st }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--slate-light)">No data. Compute report cards first.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endif
@endsection