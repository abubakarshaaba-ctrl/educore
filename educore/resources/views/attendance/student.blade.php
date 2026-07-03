@extends('layouts.app')
@section('title','Student Attendance History')
@section('page-title','Student Attendance History')
@section('content')
@php $student = $student ?? null; @endphp
@if($student)
<div style="background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px">
    <div style="padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700">
        {{ $student->full_name }} — Attendance History
    </div>
    <div style="padding:16px">
        @php
            $present = $records->where('status','present')->count();
            $total   = $records->count();
            $rate    = $total > 0 ? round(($present/$total)*100) : 0;
        @endphp
        <div style="display:flex;gap:20px;margin-bottom:16px;flex-wrap:wrap">
            <div style="text-align:center"><div style="font-size:28px;font-weight:800;color:var(--emerald)">{{ $rate }}%</div><div style="font-size:11px;color:var(--slate-light)">Attendance Rate</div></div>
            <div style="text-align:center"><div style="font-size:28px;font-weight:800">{{ $present }}</div><div style="font-size:11px;color:var(--slate-light)">Days Present</div></div>
            <div style="text-align:center"><div style="font-size:28px;font-weight:800;color:var(--crimson)">{{ $total - $present }}</div><div style="font-size:11px;color:var(--slate-light)">Days Absent</div></div>
        </div>
        <div class="tbl"><table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead><tr>
                <th style="padding:8px 12px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light)">Date</th>
                <th style="padding:8px 12px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light)">Status</th>
                <th style="padding:8px 12px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light)">Note</th>
            </tr></thead>
            <tbody>
            @forelse($records->sortByDesc('attendance_date') as $rec)
            <tr>
                <td style="padding:9px 12px;border-bottom:1px solid var(--border)">{{ \Carbon\Carbon::parse($rec->attendance_date)->format('d M Y') }}</td>
                <td style="padding:9px 12px;border-bottom:1px solid var(--border)">
                    <span style="display:inline-flex;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;background:{{ $rec->status==='present'?'#ECFDF5':'#FEF2F2' }};color:{{ $rec->status==='present'?'#059669':'#DC2626' }}">
                        {{ ucfirst($rec->status) }}
                    </span>
                </td>
                <td style="padding:9px 12px;border-bottom:1px solid var(--border);font-size:12px;color:var(--slate-light)">{{ $rec->note ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center;padding:30px;color:var(--slate-light)">No attendance records found.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>
@else
<div style="text-align:center;padding:60px;color:var(--slate-light)">Student not found.</div>
@endif
@endsection
