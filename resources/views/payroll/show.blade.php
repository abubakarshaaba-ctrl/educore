@extends('layouts.app')
@section('title','Payroll Details')
@section('page-title','Payroll')
@push('styles')
<style>
.ph{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px}
.sum-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
.sc{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px}
.sv{font-size:20px;font-weight:800}.sl{font-size:10px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:2pt}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12.5px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-g{background:var(--emerald);color:white}.btn-a{background:var(--amber);color:white}
@media print{
    @page{size:A4 landscape;margin:12mm}
    .ph a,.ph form,.back,.ph .btn{display:none!important}
    .card{border:none;box-shadow:none}
    .sum-row .sc{border:1px solid #ccc}
    body{font-size:10pt;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    thead th,tbody td{font-size:9pt;padding:5pt 8pt}
    .print-header{display:block!important}
    .alert-s{display:none}
}
.print-header{display:none;text-align:center;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #071E45}
.print-header .ph-title{font-size:16pt;font-weight:800;color:#071E45}
.print-header .ph-sub{font-size:10pt;color:#475569;margin-top:3px}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-draft{background:#F1F5F9;color:var(--slate)}.b-approved{background:#FFFBEB;color:var(--amber)}.b-paid{background:#ECFDF5;color:var(--emerald)}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
@if(session('warning'))<div class="alert-s" style="background:#FFFBEB;border-color:#FCD34D;color:#92400E">⚠️ {{ session('warning') }}</div>@endif
<a href="{{ route('payroll.index') }}" class="back">← Back to Payroll</a>
<div class="print-header">
    <div class="ph-title">{{ optional(auth()->user()->tenant)->name }}</div>
    <div class="ph-sub">Payroll — {{ $period->title }} &nbsp;|&nbsp; Status: {{ ucfirst($period->status) }} &nbsp;|&nbsp; Printed: {{ now()->format('d M Y') }}</div>
</div>
<div class="ph">
    <div>
        <h2 style="font-size:18px;font-weight:700">{{ $period->title }}</h2>
        <p style="font-size:13px;color:var(--slate);margin-top:2px">{{ \Carbon\Carbon::parse($period->period_start)->format('d M') }} – {{ \Carbon\Carbon::parse($period->period_end)->format('d M Y') }} <span class="badge b-{{ $period->status }}" style="margin-left:6px">{{ ucfirst($period->status) }}</span></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('payroll.payslip', $period) }}" class="btn" style="background:#EFF6FF;color:var(--indigo);font-size:12px">🖨 Payslips</a>
        <button type="button" onclick="window.print()" class="btn" style="background:#F1F5F9;color:var(--slate);border:1px solid var(--border);font-size:12px">🖨 Print</button>
        <a href="{{ route('payroll.download.pdf', $period) }}" class="btn" style="background:#FEF2F2;color:#B91C1C;font-size:12px">↓ PDF</a>
        <a href="{{ route('payroll.download.excel', $period) }}" class="btn" style="background:#F0FDF4;color:#15803D;font-size:12px">↓ Excel</a>
        @if($period->status==='draft')<form method="POST" action="{{ route('payroll.approve',$period) }}">@csrf<button type="submit" class="btn btn-a">✓ Approve</button></form>@endif
        @if($period->status==='approved')<form method="POST" action="{{ route('payroll.paid',$period) }}">@csrf<button type="submit" class="btn btn-g">✓ Mark Paid</button></form>@endif
    </div>
</div>
<div class="sum-row">
    <div class="sc"><div class="sv">₦{{ number_format($period->total_gross) }}</div><div class="sl">Total Gross</div></div>
    <div class="sc"><div class="sv" style="color:var(--crimson)">₦{{ number_format($period->total_deductions) }}</div><div class="sl">Total Deductions</div></div>
    <div class="sc"><div class="sv" style="color:var(--emerald)">₦{{ number_format($period->total_net) }}</div><div class="sl">Net Pay</div></div>
</div>
<div class="card">
    <div class="ch">Staff Payslips <span style="font-weight:400;font-size:12px;color:var(--slate)">{{ $items->count() }} staff</span></div>
    <div class="tbl"><table>
        <thead><tr><th>Staff</th><th>Basic</th><th>Allowances</th><th>Gross</th><th>Tax</th><th>Pension</th><th>Net Pay</th><th>Bank</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($items as $item)
        <tr>
            <td><strong>{{ optional($item->staff)->name ?? '—' }}</strong><br><span style="font-size:10px;color:var(--slate-light)">{{ optional($item->staff)->role }}</span></td>
            <td>₦{{ number_format($item->basic_salary) }}</td>
            <td style="font-size:11px;color:var(--slate)">₦{{ number_format($item->housing_allowance+$item->transport_allowance+$item->other_allowances) }}</td>
            <td style="font-weight:700">₦{{ number_format($item->gross_pay) }}</td>
            <td style="color:var(--crimson);font-size:11px">₦{{ number_format($item->tax_deduction) }}</td>
            <td style="color:var(--crimson);font-size:11px">₦{{ number_format($item->pension_deduction) }}</td>
            <td style="font-weight:700;color:var(--emerald)">₦{{ number_format($item->net_pay) }}</td>
            <td style="font-size:11px">{{ $item->bank_name }}<br>{{ $item->account_number }}</td>
            <td><span class="badge {{ $item->payment_status==='paid'?'b-paid':'b-draft' }}">{{ ucfirst($item->payment_status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--slate-light)">No payroll items</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endsection