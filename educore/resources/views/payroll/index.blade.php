@extends('layouts.app')
@section('title','Payroll')
@section('page-title','Payroll')

@push('styles')
<style>
.payroll-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px}
.payroll-header h2{font-size:18px;font-weight:800;color:var(--brand-navy);margin:0}
.payroll-header p{font-size:12px;color:var(--slate-light);margin:4px 0 0}
.payroll-actions{display:flex;gap:8px;flex-wrap:wrap}
.payroll-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,.04)}
.payroll-card-head{padding:14px 18px;border-bottom:1px solid var(--border);background:var(--brand-navy-soft);font-size:13px;font-weight:800;color:var(--brand-navy);display:flex;align-items:center;justify-content:space-between}
.payroll-table-wrap{overflow-x:auto}
.payroll-table{width:100%;border-collapse:collapse;min-width:800px}
.payroll-table thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:9px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border);white-space:nowrap}
.payroll-table tbody td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle}
.payroll-table tbody tr:last-child td{border-bottom:none}
.payroll-table tbody tr:hover td{background:#F8FAFC}
.period-title{font-weight:700;color:var(--brand-navy)}
.period-range{font-size:11px;color:var(--slate-light);margin-top:2px}
.money-deduction{color:var(--crimson)}
.money-net{font-weight:800;color:var(--emerald)}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:4px 9px;border-radius:999px}
.b-draft{background:#F1F5F9;color:var(--slate)}
.b-approved{background:#FEF9EC;color:var(--brand-gold-dark)}
.b-paid{background:#ECFDF5;color:var(--emerald)}
.empty-state{text-align:center;padding:42px 20px;color:var(--slate-light)}
.pagination-wrap{padding:14px 18px;border-top:1px solid var(--border)}
@media(max-width:700px){.payroll-actions{width:100%}.payroll-actions .btn{flex:1;justify-content:center;min-width:140px}}
</style>
@endpush

@section('content')
@if(session('success'))
<div class="alert-success" role="status" aria-live="polite">{{ session('success') }}</div>
@endif

<div class="payroll-header">
    <div>
        <h2>Payroll Management</h2>
        <p>Generate, review and manage staff payroll periods and payslips.</p>
    </div>
    <div class="payroll-actions">
        <a href="{{ route('payroll.salary') }}" class="btn btn-ghost">Salary Settings</a>
        <a href="{{ route('payroll.staff-deductions') }}" class="btn btn-ghost">Staff Deductions</a>
        <a href="{{ route('payroll.tax-bands') }}" class="btn btn-ghost">Tax Bands</a>
        <a href="{{ route('payroll.create') }}" class="btn btn-primary">+ Generate Payroll</a>
    </div>
</div>

<div class="payroll-card">
    <div class="payroll-card-head">
        <span>Payroll Periods</span>
        <span style="font-size:11px;font-weight:600;color:var(--slate-light)">{{ $periods->total() }} total</span>
    </div>
    <div class="payroll-table-wrap">
        <table class="payroll-table">
            <thead>
                <tr><th>Period</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th>Payment Date</th><th>Action</th></tr>
            </thead>
            <tbody>
            @forelse($periods as $period)
            <tr>
                <td>
                    <div class="period-title">{{ $period->title }}</div>
                    <div class="period-range">{{ \Carbon\Carbon::parse($period->period_start)->format('d M') }} – {{ \Carbon\Carbon::parse($period->period_end)->format('d M Y') }}</div>
                </td>
                <td>₦{{ number_format($period->total_gross) }}</td>
                <td class="money-deduction">₦{{ number_format($period->total_deductions) }}</td>
                <td class="money-net">₦{{ number_format($period->total_net) }}</td>
                <td><span class="badge b-{{ $period->status }}">{{ ucfirst($period->status) }}</span></td>
                <td>{{ $period->payment_date ? \Carbon\Carbon::parse($period->payment_date)->format('d M Y') : '—' }}</td>
                <td><a href="{{ route('payroll.show',$period) }}" class="btn btn-primary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="7"><div class="empty-state">No payroll periods yet. Generate the first payroll period to begin.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($periods->hasPages())
    <div class="pagination-wrap">{{ $periods->links() }}</div>
    @endif
</div>
@endsection