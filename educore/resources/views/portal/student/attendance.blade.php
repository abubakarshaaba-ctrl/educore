@extends('layouts.portal')
@section('title','My Attendance')

@push('styles')
<style>
.attendance-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;min-width:0}
.attendance-head h2{font-size:17px;font-weight:800;overflow-wrap:anywhere}
.term-select{padding:8px 14px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;max-width:100%;min-width:220px;box-sizing:border-box}
.attendance-table{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;overscroll-behavior-inline:contain}
.attendance-table table{min-width:560px}
.attendance-table th{white-space:nowrap}
.attendance-table td{overflow-wrap:anywhere}
@media(max-width:640px){
    .attendance-head{align-items:stretch;flex-direction:column}
    .term-select{width:100%;min-width:0}
    .attendance-table table{min-width:520px}
}
@media(max-width:420px){
    .attendance-head h2{font-size:16px}
    .attendance-table table{min-width:500px;font-size:12px}
    .attendance-table th,.attendance-table td{padding:8px 10px}
}
</style>
@endpush

@section('content')
<div class="attendance-head">
    <h2>📅 My Attendance</h2>
    <select class="term-select" onchange="location.href='?term_id='+this.value">
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
    <div class="attendance-table">
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
