@extends('layouts.app')
@section('title','Payslips — '.$period->label)
@section('page-title','Payslips')

@push('styles')
<style>
.ps-table{width:100%;border-collapse:collapse;font-size:13px}
.ps-table th{padding:9px 14px;text-align:left;background:#F8FAFC;border-bottom:2px solid var(--border);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light)}
.ps-table td{padding:10px 14px;border-bottom:1px solid var(--border);color:var(--midnight);vertical-align:middle}
.ps-table tr:hover td{background:#F8FAFC}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.card-head{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}
.card-title{font-size:14px;font-weight:700;color:var(--midnight)}
.stat-row{display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap}
.stat{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 18px;flex:1;min-width:140px}
.stat-label{font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px}
.stat-value{font-size:20px;font-weight:800;color:var(--midnight)}
.badge{display:inline-flex;align-items:center;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px}
.badge-success{background:#ECFDF5;color:#059669}
.badge-warning{background:#FFFBEB;color:#D97706}
.badge-slate{background:#F1F5F9;color:#64748B}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;font-size:12px;font-weight:600;font-family:inherit;border:none;border-radius:8px;cursor:pointer;transition:all 150ms;text-decoration:none}
.btn-primary{background:var(--indigo);color:white}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
</style>
@endpush

@section('content')
<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;flex-wrap:wrap">
    <a href="{{ route('payroll.show',$period) }}" class="btn btn-ghost">← Back to Period</a>
    <h2 style="font-size:16px;font-weight:700;color:var(--midnight);margin:0">Payslips — {{ $period->label }}</h2>
    <span style="margin-left:auto;font-size:12px;color:var(--slate-light)">{{ $items->count() }} staff members</span>
</div>

<div class="stat-row">
    <div class="stat">
        <div class="stat-label">Total Gross</div>
        <div class="stat-value">₦{{ number_format($items->sum('gross_pay'),2) }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Total Deductions</div>
        <div class="stat-value" style="color:var(--crimson)">₦{{ number_format($items->sum('total_deductions'),2) }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Total Net Pay</div>
        <div class="stat-value" style="color:var(--emerald)">₦{{ number_format($items->sum('net_pay'),2) }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Period Status</div>
        <div class="stat-value" style="font-size:14px;margin-top:4px">
            <span class="badge {{ $period->status === 'paid' ? 'badge-success' : ($period->status === 'approved' ? 'badge-warning' : 'badge-slate') }}">
                {{ ucfirst($period->status) }}
            </span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <span class="card-title">Staff Payslips</span>
        <span style="font-size:12px;color:var(--slate-light)">Click "View Payslip" to open individual slip</span>
    </div>
    <div style="overflow-x:auto">
        <table class="ps-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Staff Name</th>
                    <th>Role</th>
                    <th>Basic Salary</th>
                    <th>Allowances</th>
                    <th>Gross</th>
                    <th>Deductions</th>
                    <th>Net Pay</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $i => $item)
            <tr>
                <td style="color:var(--slate-light)">{{ $i+1 }}</td>
                <td>
                    <div style="font-weight:600">{{ optional($item->staff)->name }}</div>
                    <div style="font-size:11px;color:var(--slate-light)">{{ optional($item->staff)->email }}</div>
                </td>
                <td style="text-transform:capitalize">{{ str_replace('_',' ', optional($item->staff)->role ?? '—') }}</td>
                <td>₦{{ number_format($item->basic_salary ?? 0, 2) }}</td>
                <td>₦{{ number_format(($item->housing_allowance ?? 0) + ($item->transport_allowance ?? 0) + ($item->other_allowances ?? 0), 2) }}</td>
                <td style="font-weight:600">₦{{ number_format($item->gross_pay ?? 0, 2) }}</td>
                <td style="color:var(--crimson)">₦{{ number_format($item->total_deductions ?? 0, 2) }}</td>
                <td style="font-weight:700;color:var(--emerald)">₦{{ number_format($item->net_pay ?? 0, 2) }}</td>
                <td>
                    <span class="badge {{ $item->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">
                        {{ ucfirst($item->payment_status ?? 'pending') }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('payroll.payslip.pdf', [$period, $item]) }}" target="_blank" class="btn btn-primary" style="padding:6px 12px;font-size:11px">
                        🖨 Payslip
                    </a>
                </td>
            </tr>
            @empty
            <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--slate-light)">No payroll items found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
