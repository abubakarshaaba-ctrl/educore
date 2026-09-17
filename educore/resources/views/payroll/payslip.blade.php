@extends('layouts.app')
@section('title','Payslips — '.$period->label)
@section('page-title','Payslips')

@push('styles')
<style>
.payslip-toolbar{display:flex;align-items:center;gap:10px;margin-bottom:18px;flex-wrap:wrap}
.payslip-toolbar h2{font-size:16px;font-weight:700;color:var(--brand-navy);margin:0;min-width:0}
.payslip-count{margin-left:auto;font-size:12px;color:var(--slate-light)}
.payroll-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}
.payroll-stat{background:#fff;border:1px solid var(--border);border-radius:12px;padding:15px 16px;box-shadow:0 1px 2px rgba(15,23,42,.03);min-width:0}
.payroll-stat-label{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px}
.payroll-stat-value{font-size:20px;font-weight:800;color:var(--brand-navy);line-height:1.15;overflow-wrap:anywhere}
.payroll-stat-value.danger{color:var(--crimson)}
.payroll-stat-value.success{color:var(--emerald)}
.payroll-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,.04);min-width:0}
.payroll-card-head{padding:14px 18px;border-bottom:1px solid var(--border);background:var(--brand-navy-soft);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.payroll-card-title{font-size:14px;font-weight:700;color:var(--brand-navy)}
.payroll-card-hint{font-size:12px;color:var(--slate-light)}
.payroll-table-wrap{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;overscroll-behavior-inline:contain}
.ps-table{width:100%;border-collapse:collapse;font-size:13px;min-width:980px}
.ps-table th{padding:10px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);white-space:nowrap}
.ps-table td{padding:11px 14px;border-bottom:1px solid var(--border);color:var(--midnight);vertical-align:middle;white-space:nowrap}
.ps-table td:nth-child(2){white-space:normal;min-width:190px}
.ps-table tbody tr:last-child td{border-bottom:none}
.ps-table tbody tr:hover td{background:#F8FAFC}
.staff-name{font-weight:700;color:var(--brand-navy)}
.staff-email{font-size:11px;color:var(--slate-light);margin-top:2px;overflow-wrap:anywhere}
.money-gross{font-weight:700}
.money-deduction{color:var(--crimson)}
.money-net{font-weight:800;color:var(--emerald)}
.badge{display:inline-flex;align-items:center;font-size:11px;font-weight:700;padding:4px 9px;border-radius:999px}
.badge-success{background:#ECFDF5;color:#047857}
.badge-warning{background:#FFF7E1;color:var(--brand-gold-dark)}
.badge-slate{background:#F1F5F9;color:#64748B}
.empty-state{text-align:center;padding:42px 20px;color:var(--slate-light)}
@media(max-width:1024px){.payroll-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.payslip-toolbar{align-items:flex-start;margin-bottom:14px}.payslip-toolbar .btn{flex:0 0 auto}.payslip-toolbar h2{width:100%;order:3;font-size:15px}.payslip-count{margin-left:auto;font-size:11px}.payroll-stats{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px;margin-bottom:14px}.payroll-stat{padding:10px 11px;border-radius:10px}.payroll-stat-label{font-size:8.5px;line-height:1.25}.payroll-stat-value{font-size:17px}.payroll-card{border-radius:10px}.payroll-card-head{padding:11px 12px;align-items:flex-start}.payroll-card-title{font-size:12px}.payroll-card-hint{font-size:10px;line-height:1.4}.ps-table{min-width:860px}.ps-table th{font-size:8.8px;padding:8px}.ps-table td{font-size:10.5px;padding:8px}.badge{font-size:9px;padding:3px 7px}}
@media(max-width:360px){.payroll-stat{padding:9px}.payroll-stat-label{font-size:8px}.payroll-stat-value{font-size:16px}.ps-table{min-width:820px}}
</style>
@endpush

@section('content')
<div class="payslip-toolbar">
    <a href="{{ route('payroll.show',$period) }}" class="btn btn-ghost">← Back to Period</a>
    <h2>Payslips — {{ $period->label }}</h2>
    <span class="payslip-count">{{ $items->count() }} staff members</span>
</div>

<div class="payroll-stats">
    <div class="payroll-stat">
        <div class="payroll-stat-label">Total Gross</div>
        <div class="payroll-stat-value">₦{{ number_format($items->sum('gross_pay'),2) }}</div>
    </div>
    <div class="payroll-stat">
        <div class="payroll-stat-label">Total Deductions</div>
        <div class="payroll-stat-value danger">₦{{ number_format($items->sum('total_deductions'),2) }}</div>
    </div>
    <div class="payroll-stat">
        <div class="payroll-stat-label">Total Net Pay</div>
        <div class="payroll-stat-value success">₦{{ number_format($items->sum('net_pay'),2) }}</div>
    </div>
    <div class="payroll-stat">
        <div class="payroll-stat-label">Period Status</div>
        <div class="payroll-stat-value" style="font-size:14px;margin-top:4px">
            <span class="badge {{ $period->status === 'paid' ? 'badge-success' : ($period->status === 'approved' ? 'badge-warning' : 'badge-slate') }}">
                {{ ucfirst($period->status) }}
            </span>
        </div>
    </div>
</div>

<div class="payroll-card">
    <div class="payroll-card-head">
        <span class="payroll-card-title">Staff Payslips</span>
        <span class="payroll-card-hint">Open the portable PDF for an individual staff member</span>
    </div>
    <div class="payroll-table-wrap">
        <table class="ps-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Staff</th>
                    <th>Role</th>
                    <th>Basic</th>
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
                    <div class="staff-name">{{ optional($item->staff)->name }}</div>
                    <div class="staff-email">{{ optional($item->staff)->email }}</div>
                </td>
                <td style="text-transform:capitalize">{{ str_replace('_',' ', optional($item->staff)->role ?? '—') }}</td>
                <td>₦{{ number_format($item->basic_salary ?? 0, 2) }}</td>
                <td>₦{{ number_format(($item->housing_allowance ?? 0) + ($item->transport_allowance ?? 0) + ($item->other_allowances ?? 0), 2) }}</td>
                <td class="money-gross">₦{{ number_format($item->gross_pay ?? 0, 2) }}</td>
                <td class="money-deduction">₦{{ number_format($item->total_deductions ?? 0, 2) }}</td>
                <td class="money-net">₦{{ number_format($item->net_pay ?? 0, 2) }}</td>
                <td>
                    <span class="badge {{ $item->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">
                        {{ ucfirst($item->payment_status ?? 'pending') }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('payroll.payslip.pdf', [$period, $item]) }}" target="_blank" rel="noopener" class="btn btn-primary">
                        View PDF
                    </a>
                </td>
            </tr>
            @empty
            <tr><td colspan="10"><div class="empty-state">No payroll items found for this period.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
