@extends('layouts.app')
@section('title','Comparative Report')
@section('page-title','Comparative Term Report')
@push('styles')
<style>
.filter-card{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 18px;margin-bottom:16px;display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap}
.fg{display:flex;flex-direction:column;gap:5px}
.fl{font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;min-width:220px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#F8FAFC}
.badge{display:inline-flex;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px}
.bg{background:#ECFDF5;color:var(--emerald)}.ba{background:#FFFBEB;color:var(--amber)}.br{background:#FEF2F2;color:var(--crimson)}
.trend-up{color:var(--emerald)}.trend-down{color:var(--crimson)}.trend-flat{color:var(--slate-light)}
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
    <button type="submit" class="btn btn-p">Compare</button>
</div>
</form>
@if($data)
<div class="card">
    <div class="ch">Term-by-Term Performance — {{ request('class_arm_id') ? $optional(classArms->find(request('class_arm_id')))->optional(classLevel)->name.' '.$optional(classArms->find(request('class_arm_id')))->name : 'All Classes' }}</div>
    <div class="tbl"><table>
        <thead><tr><th>Term</th><th>Session</th><th>Students</th><th>Class Average</th><th>Highest</th><th>Lowest</th><th>Trend</th></tr></thead>
        <tbody>
        @php $prev = null; @endphp
        @foreach($data as $row)
        @php $a=round($row->avg,1); $trend = $prev ? ($a > $prev ? '↑' : ($a < $prev ? '↓' : '→')) : '—'; $tclass = $prev ? ($a > $prev ? 'trend-up' : ($a < $prev ? 'trend-down' : 'trend-flat')) : 'trend-flat'; $prev=$a; @endphp
        <tr>
            <td><strong>{{ $row->term_name }}</strong></td>
            <td style="font-size:11px;color:var(--slate-light)">{{ $row->session_name }}</td>
            <td>{{ $row->students }}</td>
            <td><span class="badge {{ $a>=70?'bg':($a>=50?'ba':'br') }}">{{ $a }}</span></td>
            <td style="color:var(--emerald)">{{ round($row->highest,1) }}</td>
            <td style="color:var(--crimson)">{{ round($row->lowest,1) }}</td>
            <td class="{{ $tclass }}" style="font-size:20px">{{ $trend }}</td>
        </tr>
        @endforeach
        </tbody>
    </table></div>
</div>
@else
<div style="background:white;border:1px solid var(--border);border-radius:12px;padding:50px;text-align:center;color:var(--slate-light)">Select a class to compare performance across terms.</div>
@endif
@endsection