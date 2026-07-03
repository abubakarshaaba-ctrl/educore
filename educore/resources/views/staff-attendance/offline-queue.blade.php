@extends('layouts.app')
@section('title','Offline Clock-In Queue')
@section('page-title','Staff Attendance')

@push('styles')
<style>
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:18px}
.breadcrumb a{color:var(--indigo);text-decoration:none;font-weight:500}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700}
table{width:100%;border-collapse:collapse}
thead th{font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:9px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
.badge{display:inline-flex;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px}
.b-pending{background:#FFFBEB;color:var(--amber)}.b-applied{background:#ECFDF5;color:var(--emerald)}.b-rejected{background:#FEF2F2;color:var(--crimson)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--emerald);color:white}.btn-r{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:var(--emerald);margin-bottom:14px}
.nav-tab{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;font-size:12.5px;font-weight:600;border:1.5px solid var(--border);background:white;color:var(--slate);text-decoration:none;transition:all 150ms}
.nav-tab:hover{background:#F1F5F9;color:var(--midnight)}
.nav-tab.active{background:var(--indigo);border-color:var(--indigo);color:white}
</style>
@endpush

@section('content')
{{-- Staff Attendance Nav --}}
<div style="display:flex;gap:4px;margin-bottom:20px;flex-wrap:wrap">
    <a href="{{ route('staff-attendance.my') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.my') ? 'active':'' }}">
        👤 My Attendance
    </a>
    @if(auth()->user()->canManage('staff-attendance'))
    <a href="{{ route('staff-attendance.index') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.index') ? 'active':'' }}">
        📋 Today's Dashboard
    </a>
    <a href="{{ route('staff-attendance.report') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.report') ? 'active':'' }}">
        📊 Monthly Report
    </a>
    <a href="{{ route('staff-attendance.qr') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.qr') ? 'active':'' }}">
        📱 QR Display
    </a>
    <a href="{{ route('staff-attendance.settings') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.settings*') ? 'active':'' }}">
        ⚙️ Settings
    </a>
    @if($hasPendingOffline)
    <a href="{{ route('staff-attendance.offline-queue') }}"
       class="nav-tab" style="color:var(--amber)">
        📡 Offline Queue
    </a>
    @endif
    @endif
</div>

<div class="breadcrumb">
    <a href="{{ route('staff-attendance.index') }}">Staff Attendance</a>
    <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    Offline Clock-In Queue
</div>

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif

<div class="card">
    <div class="ch">📡 Pending Offline Clock-Ins — {{ $queue->total() }} record(s)</div>
    <div class="tbl"><table>
        <thead><tr><th>Staff</th><th>Clocked By</th><th>Date</th><th>Clock-In Time</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($queue as $rec)
        <tr>
            <td><strong>{{ optional($rec->staff)->name }}</strong></td>
            <td style="font-size:12px;color:var(--slate-light)">{{ optional($rec->clockedBy)->name }}</td>
            <td>{{ $rec->attendance_date->format('D d M Y') }}</td>
            <td style="font-family:monospace;font-weight:700">{{ \Carbon\Carbon::parse($rec->clock_in_time)->format('H:i') }}</td>
            <td style="font-size:11px;color:var(--slate-light)">
                @if($rec->lat && $rec->lng)
                    {{ number_format($rec->lat, 4) }}, {{ number_format($rec->lng, 4) }}
                @else No GPS @endif
            </td>
            <td><span class="badge b-{{ $rec->status }}">{{ ucfirst($rec->status) }}</span></td>
            <td>
                <div style="display:flex;gap:6px">
                    <form method="POST" action="{{ route('staff-attendance.offline.process', $rec) }}" style="display:inline">
                        @csrf
                        <input type="hidden" name="action" value="approve">
                        <button class="btn btn-p">✓ Approve</button>
                    </form>
                    <form method="POST" action="{{ route('staff-attendance.offline.process', $rec) }}" style="display:inline"
                          onsubmit="return confirm('Reject this clock-in?')">
                        @csrf
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="reason" value="Rejected by admin">
                        <button class="btn btn-r">✗ Reject</button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--slate-light)">No pending offline records.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:12px 16px">{{ $queue->links() }}</div>
</div>
@endsection
