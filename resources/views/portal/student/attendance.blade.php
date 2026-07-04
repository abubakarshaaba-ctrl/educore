@extends('layouts.portal')
@section('title','My Attendance')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <h2 style="font-size:17px;font-weight:800">📅 My Attendance</h2>
    <select onchange="location.href='?term_id='+this.value" style="padding:8px 14px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none">
        <option value="">All Terms</option>
        @foreach($terms as $t)
        <option value="{{ $t->id }}" {{ $t->id==$termId ? 'selected':'' }}>{{ $t->name }} — {{ optional($t->session)->name }}</option>
        @endforeach
    </select>
</div>

<div class="kpi-row">
    <div class="kpi"><div class="kv">{{ $stats['total'] }}</div><div class="kl">School Days</div></div>
    <div class="kpi"><div class="kv" style="color:#059669">{{ $stats['present'] }}</div><div class="kl">Present</div></div>
    <div class="kpi"><div class="kv" style="color:#DC2626">{{ $stats['absent'] }}</div><div class="kl">Absent</div></div>
    <div class="kpi"><div class="kv" style="color:#D97706">{{ $stats['late'] }}</div><div class="kl">Late</div></div>
    <div class="kpi">
        <div class="kv" style="color:{{ $stats['rate']>=80?'#059669':($stats['rate']>=60?'#D97706':'#DC2626') }}">{{ $stats['rate'] }}%</div>
        <div class="kl">Rate</div>
        <div class="att-bar" style="margin-top:6px">
            <div class="att-fill" style="width:{{ $stats['rate'] }}%;background:{{ $stats['rate']>=80?'#059669':($stats['rate']>=60?'#D97706':'#DC2626') }}"></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="ch">Daily Records</div>
    <div style="overflow-x:auto">
    <table>
        <thead><tr><th>Date</th><th>Day</th><th>Status</th><th>Note</th></tr></thead>
        <tbody>
        @forelse($records as $r)
        <tr>
            <td style="font-weight:600">{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</td>
            <td style="color:var(--muted)">{{ \Carbon\Carbon::parse($r->date)->format('l') }}</td>
            <td>
                <span class="badge {{ $r->status==='present'?'b-g':($r->status==='absent'?'b-r':'b-a') }}">
                    {{ ucfirst($r->status) }}
                </span>
            </td>
            <td style="font-size:12px;color:var(--muted)">{{ $r->note ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="empty">No attendance records found.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
