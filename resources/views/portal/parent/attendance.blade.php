@extends('layouts.portal')
@section('title','Attendance')
@section('content')
@if($students->count() > 1)
<div class="child-tabs">
    @foreach($students as $s)
    <a href="?student_id={{ $s->id }}&term_id={{ $termId }}" class="child-tab {{ optional($student)->id==$s->id ? 'active':'' }}">👦 {{ $s->first_name }}</a>
    @endforeach
</div>
@endif
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <h2 style="font-size:17px;font-weight:800">📅 Attendance — {{ optional($student)->full_name }}</h2>
    <select onchange="location.href='?student_id={{ optional($student)->id }}&term_id='+this.value" style="padding:8px 14px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none">
        <option value="">All Terms</option>
        @foreach($terms as $t)<option value="{{ $t->id }}" {{ $t->id==$termId ? 'selected':'' }}>{{ $t->name }}</option>@endforeach
    </select>
</div>
<div class="kpi-row">
    <div class="kpi"><div class="kv">{{ $stats['total'] }}</div><div class="kl">School Days</div></div>
    <div class="kpi"><div class="kv" style="color:#059669">{{ $stats['present'] }}</div><div class="kl">Present</div></div>
    <div class="kpi"><div class="kv" style="color:#DC2626">{{ $stats['absent'] }}</div><div class="kl">Absent</div></div>
    <div class="kpi">
        <div class="kv" style="color:{{ $stats['rate']>=80?'#059669':($stats['rate']>=60?'#D97706':'#DC2626') }}">{{ $stats['rate'] }}%</div>
        <div class="kl">Rate</div>
        <div class="att-bar"><div class="att-fill" style="width:{{ $stats['rate'] }}%;background:{{ $stats['rate']>=80?'#059669':($stats['rate']>=60?'#D97706':'#DC2626') }}"></div></div>
    </div>
</div>
<div class="card">
    <div class="ch">Daily Records</div>
    <div style="overflow-x:auto"><table>
        <thead><tr><th>Date</th><th>Day</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($records as $r)
        <tr>
            <td style="font-weight:600">{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</td>
            <td style="color:var(--muted)">{{ \Carbon\Carbon::parse($r->date)->format('l') }}</td>
            <td><span class="badge {{ $r->status==='present'?'b-g':($r->status==='absent'?'b-r':'b-a') }}">{{ ucfirst($r->status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="3" class="empty">No records.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endsection
