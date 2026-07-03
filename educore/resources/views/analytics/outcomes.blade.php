@extends('layouts.app')
@section('title','Learning Outcomes')
@section('page-title','Learning Outcome Tracker')
@push('styles')
<style>
.filter-card{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 18px;margin-bottom:16px;display:flex;gap:14px;align-items:flex-end}
.fg{display:flex;flex-direction:column;gap:5px}
.fl{font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;min-width:220px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#F8FAFC}
.spark{display:flex;align-items:center;gap:3px}
.spark-dot{width:8px;height:8px;border-radius:50%}
.improving{color:var(--emerald);font-weight:700}.declining{color:var(--crimson);font-weight:700}.stable{color:var(--slate)}
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
    <button type="submit" class="btn btn-p">Track</button>
</div>
</form>
@if($students)
<div class="card">
    <div class="ch">Learning Outcomes — {{ $students->count() }} students</div>
    <div class="tbl"><table>
        <thead><tr><th>Student</th><th>Latest Avg</th><th>Trend (all terms)</th><th>Status</th></tr></thead>
        <tbody>
        @foreach($students as $ts)
        @php $trend=$ts['trend']; $last=last($trend)?:0; $first=$trend[0]??0; $dir=$last>$first?'improving':($last<$first?'declining':'stable'); @endphp
        <tr>
            <td><strong>{{ $ts['student']->full_name }}</strong></td>
            <td><strong style="color:{{ $last>=70?'var(--emerald)':($last>=50?'var(--amber)':' var(--crimson)') }}">{{ $last }}</strong></td>
            <td>
                <div class="spark">
                @foreach($trend as $v)
                <div class="spark-dot" title="{{ $v }}" style="background:{{ $v>=70?'var(--emerald)':($v>=50?'var(--amber)':' var(--crimson)') }};width:{{ min(max($v/5,4),20) }}px;height:{{ min(max($v/5,4),20) }}px"></div>
                @endforeach
                @if(empty($trend))<span style="color:var(--slate-light);font-size:11px">No data</span>@endif
                </div>
            </td>
            <td><span class="{{ $dir }}" style="font-size:13px">{{ $dir==='improving'?'↑ Improving':($dir==='declining'?'↓ Declining':'→ Stable') }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table></div>
</div>
@else
<div style="background:white;border:1px solid var(--border);border-radius:12px;padding:50px;text-align:center;color:var(--slate-light)">Select a class to track learning outcomes over time.</div>
@endif
@endsection