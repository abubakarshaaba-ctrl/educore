@extends('layouts.app')
@section('title','Payroll & Payslips')
@section('page-title','Payroll & Payslips')

@push('styles')
<style>
.pr-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.pr-kpi{background:white;border:1px solid var(--border);border-radius:12px;padding:16px}
.pr-kpi-val{font-size:22px;font-weight:800;letter-spacing:-.02em}
.pr-kpi-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light,#7A7F87);margin-top:4px}

.pr-card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.pr-card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.pr-table{width:100%;border-collapse:collapse;font-size:13px}
.pr-table th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light,#7A7F87);padding:11px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
.pr-table td{padding:12px 16px;border-bottom:1px solid var(--border)}
.pr-table tr:last-child td{border-bottom:none}
.pr-table tr:hover td{background:#FAFBFC}
.pr-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:capitalize}
.pr-badge-paid{background:#ECFDF5;color:#059669}
.pr-badge-pending{background:#FFFBEB;color:#D97706}
.pr-empty{padding:48px;text-align:center;color:var(--slate-light,#7A7F87);font-size:13px}
.pr-print-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;background:#071E45;color:white;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none}

@media(max-width:1000px){.pr-kpis{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.pr-kpis{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

<h2 style="font-size:17px;font-weight:800;color:var(--midnight);margin-bottom:16px">💰 My Payroll & Payslips</h2>

@if($totals && $totals->count > 0)
<div class="pr-kpis">
    <div class="pr-kpi"><div class="pr-kpi-val" style="color:#2563EB">{{ $totals->count }}</div><div class="pr-kpi-label">Payslips</div></div>
    <div class="pr-kpi"><div class="pr-kpi-val" style="color:#059669">₦{{ number_format($totals->total_gross ?? 0) }}</div><div class="pr-kpi-label">Total Gross Earned</div></div>
    <div class="pr-kpi"><div class="pr-kpi-val" style="color:#DC2626">₦{{ number_format($totals->total_deductions ?? 0) }}</div><div class="pr-kpi-label">Total Deductions</div></div>
    <div class="pr-kpi"><div class="pr-kpi-val" style="color:#059669">₦{{ number_format($totals->total_net ?? 0) }}</div><div class="pr-kpi-label">Total Net Pay</div></div>
</div>
@endif

<div class="pr-card">
    <div class="pr-card-head">Payslip History</div>
    <div style="overflow-x:auto"><table class="pr-table">
        <thead>
            <tr>
                <th>Period</th><th>Dates</th><th>Gross Pay</th><th>Deductions</th><th>Net Pay</th><th>Status</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
        <tr>
            <td><div style="font-weight:700;color:var(--midnight)">{{ optional($item->period)->title ?? '—' }}</div></td>
            <td style="font-size:12px;color:var(--slate-light,#7A7F87)">
                @if($item->period?->period_start)
                    {{ \Carbon\Carbon::parse($item->period->period_start)->format('d M Y') }}
                    – {{ \Carbon\Carbon::parse($item->period->period_end)->format('d M Y') }}
                @else
                    —
                @endif
            </td>
            <td style="font-weight:600">₦{{ number_format($item->gross_pay ?? 0) }}</td>
            <td style="color:#DC2626">₦{{ number_format($item->total_deductions ?? 0) }}</td>
            <td style="font-weight:800;color:#059669">₦{{ number_format($item->net_pay ?? 0) }}</td>
            <td>
                <span class="pr-badge {{ $item->payment_status === 'paid' ? 'pr-badge-paid' : 'pr-badge-pending' }}">
                    {{ ucfirst($item->payment_status ?? 'pending') }}
                </span>
            </td>
            <td>
                <a href="{{ route('staff.portal.payslip.print', $item->period) }}" target="_blank" class="pr-print-btn">🖨 View / Print</a>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="pr-empty">No payslips available yet. Contact payroll administration.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $items->links() }}</div>
</div>
@endsection
