@extends('layouts.portal')
@section('title','Payroll & Payslips')
@section('content')

<div class="page-header">
    <h2 style="font-size:17px;font-weight:800">💰 My Payroll & Payslips</h2>
</div>

{{-- Summary KPIs --}}
@if($totals && $totals->count > 0)
<div class="kpi-row" style="margin-bottom:20px">
    <div class="kpi">
        <div class="kv" style="color:#2563EB">{{ $totals->count }}</div>
        <div class="kl">Payslips</div>
    </div>
    <div class="kpi">
        <div class="kv" style="color:#059669">₦{{ number_format($totals->total_gross ?? 0) }}</div>
        <div class="kl">Total Gross Earned</div>
    </div>
    <div class="kpi">
        <div class="kv" style="color:#DC2626">₦{{ number_format($totals->total_deductions ?? 0) }}</div>
        <div class="kl">Total Deductions</div>
    </div>
    <div class="kpi">
        <div class="kv" style="color:#059669">₦{{ number_format($totals->total_net ?? 0) }}</div>
        <div class="kl">Total Net Pay</div>
    </div>
</div>
@endif

<div class="card">
    <div class="ch">Payslip History</div>
    <div class="tbl"><table>
        <thead>
            <tr>
                <th>Period</th>
                <th>Dates</th>
                <th>Gross Pay</th>
                <th>Deductions</th>
                <th>Net Pay</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
        <tr>
            <td>
                <div style="font-weight:700">{{ optional($item->period)->title ?? '—' }}</div>
            </td>
            <td style="font-size:12px;color:var(--muted)">
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
                <span class="badge {{ $item->payment_status === 'paid' ? 'b-g' : 'b-a' }}">
                    {{ ucfirst($item->payment_status ?? 'pending') }}
                </span>
            </td>
            <td>
                <a href="{{ route('staff.portal.payslip.print', $item->period) }}"
                   target="_blank"
                   style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;background:#071E45;color:white;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none">
                    🖨 View / Print
                </a>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="empty">No payslips available yet. Contact payroll administration.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $items->links() }}</div>
</div>
@endsection
