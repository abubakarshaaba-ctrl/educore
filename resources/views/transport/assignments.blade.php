@extends('layouts.app')
@section('title','Transport Assignments')
@section('page-title','Transport')
@push('styles')
<style>
.tabs{display:flex;gap:6px;margin-bottom:20px}.tab{padding:8px 18px;font-size:13px;font-weight:600;border-radius:8px;border:1.5px solid var(--border);background:white;color:var(--slate);text-decoration:none;transition:all 150ms}.tab.active,.tab:hover{background:var(--indigo);border-color:var(--indigo);color:white}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse;font-size:13px}th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);text-align:left}td{padding:9px 14px;border-bottom:1px solid var(--border)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12px;font-weight:600;font-family:inherit;border:none;border-radius:8px;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-primary{background:var(--indigo);color:white}.btn-danger{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}.fl{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
.badge{display:inline-flex;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;background:#EFF6FF;color:var(--indigo)}
</style>
@endpush
@section('content')
<div class="tabs">
    <a href="{{ route('transport.routes') }}" class="tab">🛣 Routes</a>
    <a href="{{ route('transport.buses') }}" class="tab">🚌 Buses</a>
    <a href="{{ route('transport.assignments') }}" class="tab active">👦 Assignments</a>
</div>
@if(session('success'))<div class="alert-success">✓ {{ session('success') }}</div>@endif
<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">
<div class="card"><div class="card-head"><span style="font-size:13px;font-weight:700">👦 Student Assignments</span><span style="font-size:12px;color:var(--slate-light)">{{ $students->whereNotNull('transportAssignment')->count() }} assigned / {{ $students->count() }} total</span></div>
<div style="overflow-x:auto"><table><thead><tr><th>Student</th><th>Class</th><th>Route</th><th>Stop</th><th>Direction</th><th></th></tr></thead>
<tbody>
@foreach($students as $s)
@php $a = $s->transportAssignment; @endphp
<tr>
    <td style="font-weight:600">{{ $s->full_name }}<div style="font-size:11px;color:var(--slate-light)">{{ $s->admission_number }}</div></td>
    <td style="font-size:12px">{{ optional(optional($s->currentClassArm)->classLevel)->name }} {{ optional($s->currentClassArm)->name }}</td>
    <td>{{ $a ? optional($a->route)->name : '—' }}</td>
    <td style="font-size:12px">{{ optional($a)->pickup_stop ?? '—' }}</td>
    <td>{{ $a ? ucfirst($a->direction) : '—' }}</td>
    <td>
        @if($a)
        <form method="POST" action="{{ route('transport.unassign',$s) }}">@csrf @method('DELETE')<button class="btn btn-danger" style="padding:4px 8px;font-size:11px">Unassign</button></form>
        @endif
    </td>
</tr>
@endforeach
</tbody></table></div></div>

<div class="card"><div class="card-head"><span style="font-size:13px;font-weight:700">➕ Assign Student</span></div>
<div style="padding:18px"><form method="POST" action="{{ route('transport.assign') }}">@csrf
    <div class="fg"><label class="fl">Student *</label><select name="student_id" class="fc" required><option value="">Select student...</option>@foreach($students as $s)<option value="{{ $s->id }}">{{ $s->full_name }} ({{ $s->admission_number }})</option>@endforeach</select></div>
    <div class="fg"><label class="fl">Route *</label><select name="route_id" class="fc" required><option value="">Select route...</option>@foreach($routes as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
    <div class="fg"><label class="fl">Pickup Stop</label><input name="pickup_stop" class="fc" placeholder="e.g. Junction, before market"></div>
    <div class="fg"><label class="fl">Direction *</label><select name="direction" class="fc"><option value="both">Both (Morning & Evening)</option><option value="morning">Morning Only</option><option value="evening">Evening Only</option></select></div>
    <button class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px">Assign to Route</button>
</form></div></div>
</div>
@endsection
