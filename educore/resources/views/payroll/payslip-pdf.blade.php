<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payslip — {{ optional($item->staff)->name }}</title>
<style>
@page{size:A4 portrait;margin:12mm 14mm}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:DejaVu Sans,Arial,sans-serif;font-size:10.5px;line-height:1.35;color:#1e293b;background:#fff}
.header{display:table;width:100%;padding-bottom:9px;border-bottom:2px solid #1D4ED8;margin-bottom:10px}
.school-info,.payslip-label{display:table-cell;vertical-align:top;width:50%}
.school-info h1{font-size:15px;font-weight:700;color:#0F172A;margin-top:2px}
.school-info p{font-size:9px;color:#64748B;margin-top:1px}
.payslip-label{text-align:right}
.payslip-label .title{font-size:17px;font-weight:700;color:#1D4ED8}
.payslip-label .sub{font-size:9.5px;color:#64748B;margin-top:2px}
.section{border:1px solid #E2E8F0;border-radius:5px;padding:8px 10px;margin-bottom:8px;page-break-inside:avoid}
.section-title{font-size:8.5px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748B;margin-bottom:6px}
.info-grid{display:table;width:100%}.info-row{display:table-row}.info-item{display:table-cell;width:33.33%;padding:2px 8px 4px 0;vertical-align:top}
.info-item label{font-size:8px;font-weight:700;color:#94A3B8;display:block;margin-bottom:1px;text-transform:uppercase}
.info-item span{font-size:10px;font-weight:600;color:#0F172A}
table{width:100%;border-collapse:collapse}th{padding:5px 7px;background:#F1F5F9;font-size:8px;font-weight:700;text-transform:uppercase;color:#64748B;text-align:left;border-bottom:1px solid #E2E8F0}td{padding:5px 7px;border-bottom:1px solid #F1F5F9;font-size:10px}td:last-child{text-align:right;font-weight:600}.total-row td{background:#EFF6FF;font-weight:700;color:#1D4ED8;border-top:1px solid #93C5FD}.net-box{background:#0F766E;color:#fff;border-radius:5px;padding:9px 12px;display:table;width:100%;margin-top:8px;page-break-inside:avoid}.net-label,.net-amount{display:table-cell;vertical-align:middle}.net-label{font-size:9px;font-weight:700}.net-amount{text-align:right;font-size:18px;font-weight:700}.footer{margin-top:10px;padding-top:7px;border-top:1px solid #E2E8F0;font-size:8px;color:#94A3B8;text-align:center}.status-badge{display:inline-block;padding:2px 7px;border-radius:10px;font-size:8px;font-weight:700}.paid{background:#ECFDF5;color:#047857}.pending{background:#FFFBEB;color:#B45309}
</style>
</head>
<body>
<div class="header">
    <div class="school-info">
        @if($tenant && $tenant->logo_path)
        @php $psLogoPath = storage_path('app/public/' . ltrim($tenant->logo_path, 'storage/')); @endphp
        @if(file_exists($psLogoPath))<img src="{{ $psLogoPath }}" alt="Logo" style="height:34px;margin-bottom:3px">@endif
        @endif
        <h1>{{ optional($tenant)->name ?? 'School Name' }}</h1>
        @if($tenant?->address)<p>{{ $tenant->address }}</p>@endif
        @if($tenant?->phone)<p>{{ $tenant->phone }}</p>@endif
    </div>
    <div class="payslip-label">
        <div class="title">PAYSLIP</div>
        <div class="sub">{{ $period->label }}</div>
        <div class="sub"><span class="status-badge {{ $item->payment_status === 'paid' ? 'paid' : 'pending' }}">{{ ucfirst($item->payment_status ?? 'Pending') }}</span></div>
    </div>
</div>

<div class="section">
    <div class="section-title">Employee Details</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-item"><label>Name</label><span>{{ optional($item->staff)->name }}</span></div>
            <div class="info-item"><label>Staff ID</label><span>{{ optional($item->staff)->staff_id ?? '—' }}</span></div>
            <div class="info-item"><label>Role</label><span>{{ str_replace('_',' ', optional($item->staff)->role ?? '—') }}</span></div>
        </div>
        <div class="info-row">
            <div class="info-item"><label>Pay Period</label><span>{{ $period->label }}</span></div>
            <div class="info-item"><label>Payment Date</label><span>{{ $item->paid_at ? \Carbon\Carbon::parse($item->paid_at)->format('d M Y') : '—' }}</span></div>
            <div class="info-item"><label>Reference</label><span>PAY-{{ str_pad($item->id, 5, '0', STR_PAD_LEFT) }}</span></div>
        </div>
    </div>
</div>

<div class="section">
    <div class="section-title">Earnings</div>
    <table>
        <thead><tr><th>Component</th><th style="text-align:right">Amount (₦)</th></tr></thead>
        <tbody>
            <tr><td>Basic Salary</td><td>{{ number_format($item->basic_salary ?? 0, 2) }}</td></tr>
            @if(($item->housing_allowance ?? 0) > 0)<tr><td>Housing Allowance</td><td>{{ number_format($item->housing_allowance, 2) }}</td></tr>@endif
            @if(($item->transport_allowance ?? 0) > 0)<tr><td>Transport Allowance</td><td>{{ number_format($item->transport_allowance, 2) }}</td></tr>@endif
            @if(($item->other_allowances ?? 0) > 0)<tr><td>Other Allowances</td><td>{{ number_format($item->other_allowances, 2) }}</td></tr>@endif
            <tr class="total-row"><td>Gross Salary</td><td>₦{{ number_format($item->gross_pay ?? 0, 2) }}</td></tr>
        </tbody>
    </table>
</div>

@if(($item->total_deductions ?? 0) > 0)
<div class="section">
    <div class="section-title">Deductions</div>
    <table>
        <thead><tr><th>Deduction</th><th style="text-align:right">Amount (₦)</th></tr></thead>
        <tbody>
            @if(is_array($item->deduction_breakdown ?? null) && count($item->deduction_breakdown))
                @foreach($item->deduction_breakdown as $ded)
                <tr><td>{{ $ded['label'] ?? 'Deduction' }}</td><td>{{ number_format($ded['amount'] ?? 0, 2) }}</td></tr>
                @endforeach
            @else
                @if(($item->tax_deduction ?? 0) > 0)<tr><td>Tax (PAYE)</td><td>{{ number_format($item->tax_deduction, 2) }}</td></tr>@endif
                @if(($item->pension_deduction ?? 0) > 0)<tr><td>Pension</td><td>{{ number_format($item->pension_deduction, 2) }}</td></tr>@endif
                @if(($item->other_deductions ?? 0) > 0)<tr><td>Other Deductions</td><td>{{ number_format($item->other_deductions, 2) }}</td></tr>@endif
            @endif
            <tr class="total-row"><td>Total Deductions</td><td>₦{{ number_format($item->total_deductions ?? 0, 2) }}</td></tr>
        </tbody>
    </table>
</div>
@endif

<div class="net-box"><div class="net-label">NET PAY</div><div class="net-amount">₦{{ number_format($item->net_pay ?? 0, 2) }}</div></div>
<div class="footer">Generated {{ now()->format('d M Y, H:i') }} · {{ optional($tenant)->name }} · Computer-generated payslip</div>
</body>
</html>
