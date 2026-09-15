@extends('layouts.app')
@section('title','Payroll Details')
@section('page-title','Payroll')

@push('styles')
<style>
.period-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px}
.period-header h2{font-size:18px;font-weight:800;color:var(--brand-navy);margin:0}
.period-meta{font-size:13px;color:var(--slate);margin-top:3px}
.period-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px}
.summary-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:15px 16px;box-shadow:0 1px 2px rgba(15,23,42,.03)}
.summary-value{font-size:20px;font-weight:800;color:var(--brand-navy)}
.summary-value.danger{color:var(--crimson)}
.summary-value.success{color:var(--emerald)}
.summary-label{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:3px}
.payroll-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,.04)}
.payroll-card-head{padding:14px 18px;border-bottom:1px solid var(--border);background:var(--brand-navy-soft);font-size:13px;font-weight:800;color:var(--brand-navy);display:flex;align-items:center;justify-content:space-between;gap:12px}
.payroll-table-wrap{overflow-x:auto}
.payroll-table{width:100%;border-collapse:collapse;min-width:980px}
.payroll-table thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:9px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border);white-space:nowrap}
.payroll-table tbody td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle}
.payroll-table tbody tr:last-child td{border-bottom:none}
.payroll-table tbody tr:hover td{background:#F8FAFC}
.staff-name{font-weight:700;color:var(--brand-navy)}
.staff-role{font-size:10px;color:var(--slate-light);margin-top:2px}
.money-deduction{color:var(--crimson)}
.money-net{font-weight:800;color:var(--emerald)}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:4px 9px;border-radius:999px}
.b-draft{background:#F1F5F9;color:var(--slate)}
.b-approved{background:#FEF9EC;color:var(--brand-gold-dark)}
.b-paid{background:#ECFDF5;color:var(--emerald)}
.empty-state{text-align:center;padding:40px;color:var(--slate-light)}
.print-header{display:none;text-align:center;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #071E45}
.print-header .ph-title{font-size:16pt;font-weight:800;color:#071E45}
.print-header .ph-sub{font-size:10pt;color:#475569;margin-top:3px}
@media(max-width:800px){.summary-grid{grid-template-columns:1fr}.period-actions{width:100%}.period-actions .btn,.period-actions form{flex:1}.period-actions form .btn{width:100%;justify-content:center}}
@media print{
    @page{size:A4 landscape;margin:12mm}
    .period-actions,.back-action{display:none!important}
    .payroll-card{border:none;box-shadow:none}
    .summary-card{border:1px solid #ccc;box-shadow:none}
    body{font-size:10pt;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .payroll-table thead th,.payroll-table tbody td{font-size:9pt;padding:5pt 8pt}
    .print-header{display:block!important}
    .alert-success,.alert-warning{display:none!important}
}
</style>
@endpush

@section('content')
@if(session('success'))<div class="alert-success" role="status" aria-live="polite">{{ session('success') }}</div>@endif
@if(session('warning'))<div class="alert-warning" role="status" aria-live="polite">{{ session('warning') }}</div>@endif

<a href="{{ route('payroll.index') }}" class="btn btn-ghost back-action" style="margin-bottom:16px">← Back to Payroll</a>

<div class="print-header">
    <div class="ph-title">{{ optional(auth()->user()->tenant)->name }}</div>
    <div class="ph-sub">Payroll — {{ $period->title }} &nbsp;|&nbsp; Status: {{ ucfirst($period->status) }} &nbsp;|&nbsp; Printed: {{ now()->format('d M Y') }}</div>
</div>

<div class="period-header">
    <div>
        <h2>{{ $period->title }}</h2>
        <div class="period-meta">
            {{ \Carbon\Carbon::parse($period->period_start)->format('d M') }} – {{ \Carbon\Carbon::parse($period->period_end)->format('d M Y') }}
            <span class="badge b-{{ $period->status }}" style="margin-left:6px">{{ ucfirst($period->status) }}</span>
        </div>
    </div>
    <div class="period-actions">
        <a href="{{ route('payroll.payslip', $period) }}" class="btn btn-primary">Payslips</a>
        <button type="button" onclick="window.print()" class="btn btn-ghost">Print</button>
        <a href="{{ route('payroll.download.pdf', $period) }}" class="btn btn-ghost">PDF</a>
        <a href="{{ route('payroll.download.excel', $period) }}" class="btn btn-ghost">Excel</a>
        @if($period->status==='draft')
        <form method="POST" action="{{ route('payroll.approve',$period) }}">@csrf<button type="submit" class="btn btn-primary">Approve</button></form>
        @endif
        @if($period->status==='approved')
        <form method="POST" action="{{ route('payroll.paid',$period) }}">@csrf<button type="submit" class="btn btn-primary">Mark Paid</button></form>
        @endif
    </div>
</div>

<div class="summary-grid">
    <div class="summary-card"><div class="summary-value">₦{{ number_format($period->total_gross) }}</div><div class="summary-label">Total Gross</div></div>
    <div class="summary-card"><div class="summary-value danger">₦{{ number_format($period->total_deductions) }}</div><div class="summary-label">Total Deductions</div></div>
    <div class="summary-card"><div class="summary-value success">₦{{ number_format($period->total_net) }}</div><div class="summary-label">Net Pay</div></div>
</div>

<div class="payroll-card">
    <div class="payroll-card-head">
        <span>Staff Payroll</span>
        <span style="font-size:12px;font-weight:600;color:var(--slate-light)">{{ $items->count() }} staff</span>
    </div>
    <div class="payroll-table-wrap">
        <table class="payroll-table">
            <thead>
                <tr><th>Staff</th><th>Basic</th><th>Allowances</th><th>Gross</th><th>Tax</th><th>Pension</th><th>Net Pay</th><th>Bank</th><th>Status</th></tr>
            </thead>
            <tbody>
            @forelse($items as $item)
            <tr>
                <td><div class="staff-name">{{ optional($item->staff)->name ?? '—' }}</div><div class="staff-role">{{ optional($item->staff)->role }}</div></td>
                <td>₦{{ number_format($item->basic_salary) }}</td>
                <td>₦{{ number_format($item->housing_allowance+$item->transport_allowance+$item->other_allowances) }}</td>
                <td style="font-weight:700">₦{{ number_format($item->gross_pay) }}</td>
                <td class="money-deduction">₦{{ number_format($item->tax_deduction) }}</td>
                <td class="money-deduction">₦{{ number_format($item->pension_deduction) }}</td>
                <td class="money-net">₦{{ number_format($item->net_pay) }}</td>
                <td>{{ $item->bank_name }}<br>{{ $item->account_number }}</td>
                <td><span class="badge {{ $item->payment_status==='paid'?'b-paid':'b-draft' }}">{{ ucfirst($item->payment_status) }}</span></td>
            </tr>
            @empty
            <tr><td colspan="9"><div class="empty-state">No payroll items are available for this period.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection