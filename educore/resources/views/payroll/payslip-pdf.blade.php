<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payslip — {{ optional($item->staff)->name }}</title>
<style>
@page { size: A4 portrait; margin: 14mm; }
* { box-sizing: border-box; }
body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; font-size: 10.5px; line-height: 1.35; color: #1f2937; background: #fff; }
.header-table, .meta-table, .summary-table, .footer-table { width: 100%; border-collapse: collapse; }
.header-table td { vertical-align: top; padding: 0 0 10px 0; border-bottom: 1.5px solid #0b2d63; }
.school-name { font-size: 16px; font-weight: 700; color: #071e45; margin: 0 0 3px; }
.school-meta { color: #64748b; font-size: 9px; margin: 1px 0; }
.document-title { font-size: 19px; font-weight: 700; color: #a36a00; text-align: right; letter-spacing: .04em; }
.document-sub { color: #64748b; font-size: 9px; text-align: right; margin-top: 3px; }
.logo { max-height: 34px; max-width: 70px; margin-bottom: 5px; }
.section { margin-top: 10px; border: 1px solid #dbe3ec; }
.section-title { padding: 5px 7px; font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #475569; background: #f8fafc; border-bottom: 1px solid #dbe3ec; }
.meta-table td { width: 33.333%; padding: 6px 7px; vertical-align: top; border-right: 1px solid #edf2f7; border-bottom: 1px solid #edf2f7; }
.meta-table td:nth-child(3n) { border-right: none; }
.label { display: block; color: #64748b; font-size: 8px; text-transform: uppercase; margin-bottom: 2px; }
.value { display: block; color: #111827; font-size: 10px; font-weight: 500; overflow-wrap: break-word; }
table.money { width: 100%; border-collapse: collapse; }
table.money th { padding: 5px 7px; background: #f8fafc; color: #64748b; font-size: 8px; text-transform: uppercase; text-align: left; border-bottom: 1px solid #dbe3ec; }
table.money th:last-child, table.money td:last-child { text-align: right; }
table.money td { padding: 5px 7px; border-bottom: 1px solid #edf2f7; }
.total td { font-weight: 700; background: #f8fafc; border-top: 1px solid #cbd5e1; }
.net-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #071e45; color: #fff; }
.net-table td { padding: 9px 10px; vertical-align: middle; }
.net-label { font-size: 9px; font-weight: 700; letter-spacing: .05em; }
.net-amount { font-size: 18px; font-weight: 700; text-align: right; white-space: nowrap; }
.status { display: inline-block; border: 1px solid #cbd5e1; border-radius: 10px; padding: 2px 6px; font-size: 8px; font-weight: 700; color: #475569; }
.footer-table { margin-top: 12px; border-top: 1px solid #dbe3ec; }
.footer-table td { padding-top: 6px; color: #64748b; font-size: 8px; vertical-align: top; }
.footer-table td:last-child { text-align: right; }
.no-print { display: none; }
</style>
</head>
<body>
@php
    $staff = $item->staff;
    $periodLabel = optional($period)->label ?? optional($period)->title ?? 'Payroll period';
    $reference = 'PAY-' . str_pad((string) $item->id, 6, '0', STR_PAD_LEFT);
    $designation = $staff?->designation ?? $staff?->job_title ?? $staff?->roleLabel() ?? str_replace('_', ' ', (string) ($staff?->role ?? 'Staff'));
    $issueDate = $item->paid_at ? \Carbon\Carbon::parse($item->paid_at)->format('d M Y') : now()->format('d M Y');
@endphp

<table class="header-table">
    <tr>
        <td style="width:68%">
            @if($tenant && $tenant->logo_path)
                @php $psLogoPath = storage_path('app/public/' . ltrim($tenant->logo_path, 'storage/')); @endphp
                @if(file_exists($psLogoPath))
                    <img class="logo" src="{{ $psLogoPath }}" alt="Logo">
                @endif
            @endif
            <div class="school-name">{{ optional($tenant)->name ?? 'School Name' }}</div>
            @if($tenant?->address)<div class="school-meta">{{ $tenant->address }}</div>@endif
            @if($tenant?->phone)<div class="school-meta">{{ $tenant->phone }}</div>@endif
        </td>
        <td style="width:32%">
            <div class="document-title">PAYSLIP</div>
            <div class="document-sub">{{ $periodLabel }}</div>
            <div class="document-sub"><span class="status">{{ ucfirst($item->payment_status ?? 'Pending') }}</span></div>
        </td>
    </tr>
</table>

<div class="section">
    <div class="section-title">Employee details</div>
    <table class="meta-table">
        <tr>
            <td><span class="label">Employee name</span><span class="value">{{ $staff?->name ?? '—' }}</span></td>
            <td><span class="label">Staff ID</span><span class="value">{{ $staff?->staff_id ?? '—' }}</span></td>
            <td><span class="label">Role / designation</span><span class="value">{{ $designation ?: '—' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Payroll period</span><span class="value">{{ $periodLabel }}</span></td>
            <td><span class="label">Issue date</span><span class="value">{{ $issueDate }}</span></td>
            <td><span class="label">Reference</span><span class="value">{{ $reference }}</span></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Earnings</div>
    <table class="money">
        <thead><tr><th>Component</th><th>Amount (NGN)</th></tr></thead>
        <tbody>
            <tr><td>Basic salary</td><td>₦{{ number_format($item->basic_salary ?? 0, 2) }}</td></tr>
            @if(($item->housing_allowance ?? 0) > 0)<tr><td>Housing allowance</td><td>₦{{ number_format($item->housing_allowance, 2) }}</td></tr>@endif
            @if(($item->transport_allowance ?? 0) > 0)<tr><td>Transport allowance</td><td>₦{{ number_format($item->transport_allowance, 2) }}</td></tr>@endif
            @if(($item->other_allowances ?? 0) > 0)<tr><td>Other allowances</td><td>₦{{ number_format($item->other_allowances, 2) }}</td></tr>@endif
            <tr class="total"><td>Gross pay</td><td>₦{{ number_format($item->gross_pay ?? 0, 2) }}</td></tr>
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title">Deductions</div>
    <table class="money">
        <thead><tr><th>Deduction</th><th>Amount (NGN)</th></tr></thead>
        <tbody>
            @if(is_array($item->deduction_breakdown ?? null) && count($item->deduction_breakdown))
                @foreach($item->deduction_breakdown as $ded)
                    <tr><td>{{ $ded['label'] ?? 'Deduction' }}</td><td>₦{{ number_format($ded['amount'] ?? 0, 2) }}</td></tr>
                @endforeach
            @else
                @if(($item->tax_deduction ?? 0) > 0)<tr><td>Tax (PAYE)</td><td>₦{{ number_format($item->tax_deduction, 2) }}</td></tr>@endif
                @if(($item->pension_deduction ?? 0) > 0)<tr><td>Pension</td><td>₦{{ number_format($item->pension_deduction, 2) }}</td></tr>@endif
                @if(($item->other_deductions ?? 0) > 0)<tr><td>Other deductions</td><td>₦{{ number_format($item->other_deductions, 2) }}</td></tr>@endif
            @endif
            @if(($item->total_deductions ?? 0) <= 0)<tr><td>No deductions</td><td>₦0.00</td></tr>@endif
            <tr class="total"><td>Total deductions</td><td>₦{{ number_format($item->total_deductions ?? 0, 2) }}</td></tr>
        </tbody>
    </table>
</div>

<table class="net-table">
    <tr>
        <td><div class="net-label">NET PAY</div><div style="font-size:8px;opacity:.8">Gross pay less total deductions</div></td>
        <td class="net-amount">₦{{ number_format($item->net_pay ?? 0, 2) }}</td>
    </tr>
</table>

<table class="footer-table">
    <tr>
        <td>Verification reference: {{ $reference }}<br>Computer-generated by EduCore.</td>
        <td>{{ optional($tenant)->name }}<br>Generated {{ now()->format('d M Y, H:i') }}</td>
    </tr>
</table>
</body>
</html>
