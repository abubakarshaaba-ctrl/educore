@extends('layouts.super')
@section('title','Group Report')
@section('page-title','Group Report')

@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:12px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border);color:#0F172A}
tr:hover td{background:#F8FAFC}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-active{background:#ECFDF5;color:#059669}.b-expired{background:#FEF2F2;color:#DC2626}
.kpi{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.kc{background:white;border:1px solid var(--border);border-radius:12px;padding:14px 18px}
.kv{font-size:22px;font-weight:800}.kl{font-size:11px;color:#64748B;text-transform:uppercase;letter-spacing:.06em;margin-top:4px}
.bar-wrap{padding:0 18px 18px}
.bar-row{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.bar-label{font-size:12px;font-weight:600;color:#0F172A;width:140px;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bar-track{flex:1;background:#F1F5F9;border-radius:4px;height:22px;overflow:hidden}
.bar-fill{height:100%;background:#2563EB;border-radius:4px;display:flex;align-items:center;padding-left:8px}
.bar-fill span{font-size:11px;font-weight:700;color:white;white-space:nowrap}
.bar-amt{font-size:12px;font-weight:700;width:80px;text-align:right;flex-shrink:0}
.back{font-size:13px;color:#2563EB;text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none}
.btn-ghost{background:#F1F5F9;color:#475569;border:1px solid #E2E8F0}
@media print{.back,.btn{display:none}}
</style>
@endpush

@section('content')
<a href="{{ route('super.groups.show',$group->id) }}" class="back">← {{ $group->name }}</a>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div>
        <h2 style="font-size:16px;font-weight:800;color:#0F172A">📊 {{ $group->name }} — Consolidated Report</h2>
        <div style="font-size:12px;color:#64748B;margin-top:2px">{{ $schools->count() }} schools · Generated {{ now()->format('d M Y') }}</div>
    </div>
    <button onclick="window.print()" class="btn btn-ghost">🖨 Print</button>
</div>

<div class="kpi">
    <div class="kc"><div class="kv" style="color:#2563EB">{{ number_format($schools->sum('students')) }}</div><div class="kl">Total Students</div></div>
    <div class="kc"><div class="kv">{{ number_format($schools->sum('staff')) }}</div><div class="kl">Total Staff</div></div>
    <div class="kc"><div class="kv" style="color:#059669">₦{{ number_format($schools->sum('revenue')) }}</div><div class="kl">Total Revenue</div></div>
    <div class="kc"><div class="kv" style="color:#DC2626">₦{{ number_format($schools->sum('outstanding')) }}</div><div class="kl">Total Outstanding</div></div>
</div>

<div class="card">
    <div class="ch">Students per School</div>
    @php $maxS = $schools->max('students') ?: 1; @endphp
    <div class="bar-wrap" style="padding-top:14px">
        @foreach($schools->sortByDesc('students') as $s)
        <div class="bar-row">
            <div class="bar-label">{{ $s->name }}</div>
            <div class="bar-track">
                <div class="bar-fill" style="width:{{ round(($s->students/$maxS)*100) }}%">
                    <span>{{ number_format($s->students) }}</span>
                </div>
            </div>
            <div class="bar-amt">{{ number_format($s->students) }}</div>
        </div>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="ch">School-by-School Breakdown</div>
    <div style="overflow-x:auto">
    <table>
        <thead><tr><th>School</th><th>Status</th><th>Students</th><th>Staff</th><th>Revenue Collected</th><th>Outstanding</th><th>Collection Rate</th></tr></thead>
        <tbody>
        @foreach($schools->sortByDesc('students') as $s)
        @php
            $total = $s->revenue + $s->outstanding;
            $rate  = $total > 0 ? round(($s->revenue/$total)*100) : 0;
        @endphp
        <tr>
            <td style="font-weight:600">{{ $s->name }}</td>
            <td><span class="badge b-{{ $s->status === 'active' ? 'active':'expired' }}">{{ ucfirst($s->status) }}</span></td>
            <td>{{ number_format($s->students) }}</td>
            <td>{{ number_format($s->staff) }}</td>
            <td style="color:#059669;font-weight:600">₦{{ number_format($s->revenue) }}</td>
            <td style="color:{{ $s->outstanding > 0 ? '#DC2626':'#64748B' }};font-weight:600">₦{{ number_format($s->outstanding) }}</td>
            <td>
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="flex:1;background:#F1F5F9;border-radius:4px;height:8px;overflow:hidden">
                        <div style="width:{{ $rate }}%;height:100%;background:{{ $rate>=80?'#059669':($rate>=60?'#D97706':'#DC2626') }};border-radius:4px"></div>
                    </div>
                    <span style="font-size:12px;font-weight:700;color:{{ $rate>=80?'#059669':($rate>=60?'#D97706':'#DC2626') }}">{{ $rate }}%</span>
                </div>
            </td>
        </tr>
        @endforeach
        <tr style="background:#F8FAFC;border-top:2px solid var(--border)">
            <td style="font-weight:800">TOTAL</td>
            <td></td>
            <td style="font-weight:700">{{ number_format($schools->sum('students')) }}</td>
            <td style="font-weight:700">{{ number_format($schools->sum('staff')) }}</td>
            <td style="font-weight:800;color:#059669">₦{{ number_format($schools->sum('revenue')) }}</td>
            <td style="font-weight:800;color:#DC2626">₦{{ number_format($schools->sum('outstanding')) }}</td>
            <td></td>
        </tr>
        </tbody>
    </table>
    </div>
</div>
@endsection
