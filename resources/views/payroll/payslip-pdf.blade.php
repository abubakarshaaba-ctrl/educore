<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payslip — {{ optional($item->staff)->name }}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Arial',sans-serif;font-size:13px;color:#1e293b;background:white;padding:30px}
.header{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:16px;border-bottom:2px solid #2563EB;margin-bottom:20px}
.school-info h1{font-size:18px;font-weight:800;color:#0F172A}
.school-info p{font-size:12px;color:#64748B;margin-top:2px}
.payslip-label{text-align:right}
.payslip-label .title{font-size:20px;font-weight:800;color:#2563EB;letter-spacing:-0.02em}
.payslip-label .sub{font-size:11px;color:#64748B;margin-top:3px}
.section{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px 18px;margin-bottom:14px}
.section-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94A3B8;margin-bottom:10px}
.info-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.info-item label{font-size:10px;font-weight:600;color:#94A3B8;display:block;margin-bottom:2px}
.info-item span{font-size:13px;font-weight:600;color:#0F172A}
table{width:100%;border-collapse:collapse}
table th{padding:8px 12px;background:#F1F5F9;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B;text-align:left;border-bottom:1px solid #E2E8F0}
table td{padding:9px 12px;border-bottom:1px solid #F1F5F9;font-size:13px}
table td:last-child{text-align:right;font-weight:600}
.total-row td{background:#EFF6FF;font-weight:700;color:#2563EB;font-size:14px;border-top:2px solid #2563EB}
.net-box{background:#059669;color:white;border-radius:10px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;margin-top:14px}
.net-box .label{font-size:12px;font-weight:600;opacity:0.85}
.net-box .amount{font-size:24px;font-weight:800;letter-spacing:-0.02em}
.footer{margin-top:24px;padding-top:14px;border-top:1px dashed #E2E8F0;display:flex;justify-content:space-between;font-size:11px;color:#94A3B8}
.status-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}
.paid{background:#ECFDF5;color:#059669}
.pending{background:#FFFBEB;color:#D97706}
@media print{body{padding:0}.no-print{display:none}}
</style>
</head>
<body>

<div class="no-print" style="margin-bottom:20px;display:flex;gap:10px">
    <button onclick="window.print()" style="padding:9px 18px;background:#2563EB;color:white;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer">🖨 Print / Save PDF</button>
    <a href="{{ route('payroll.payslip', $period) }}" style="padding:9px 18px;background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none">← Back</a>
</div>

<div class="header">
    <div class="school-info">
        @if($tenant && $tenant->logo_path)
        @php $psLogoPath = storage_path('app/public/' . ltrim($tenant->logo_path, 'storage/')); @endphp
        @if(file_exists($psLogoPath))
        <img src="{{ $psLogoPath }}" alt="Logo" style="height:44px;margin-bottom:6px;border-radius:6px">
        @endif
        @endif
        <h1>{{ optional($tenant)->name ?? 'School Name' }}</h1>
        <p>{{ optional($tenant)->address ?? '' }}</p>
        @if($tenant?->phone)<p>{{ $tenant->phone }}</p>@endif
    </div>
    <div class="payslip-label">
        <div class="title">PAYSLIP</div>
        <div class="sub">{{ $period->label }}</div>
        <div class="sub" style="margin-top:6px">
            <span class="status-badge {{ $item->payment_status === 'paid' ? 'paid' : 'pending' }}">
                {{ ucfirst($item->payment_status ?? 'Pending') }}
            </span>
        </div>
    </div>
</div>

{{-- Employee Info --}}
<div class="section">
    <div class="section-title">Employee Details</div>
    <div class="info-grid">
        <div class="info-item">
            <label>Name</label>
            <span>{{ optional($item->staff)->name }}</span>
        </div>
        <div class="info-item">
            <label>Role</label>
            <span style="text-transform:capitalize">{{ str_replace('_',' ', optional($item->staff)->role ?? '—') }}</span>
        </div>
        <div class="info-item">
            <label>Email</label>
            <span>{{ optional($item->staff)->email }}</span>
        </div>
        <div class="info-item">
            <label>Pay Period</label>
            <span>{{ $period->label }}</span>
        </div>
        <div class="info-item">
            <label>Payment Date</label>
            <span>{{ $item->paid_at ? \Carbon\Carbon::parse($item->paid_at)->format('d M Y') : '—' }}</span>
        </div>
        <div class="info-item">
            <label>Reference</label>
            <span>PAY-{{ str_pad($item->id, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
    </div>
</div>

{{-- Earnings --}}
<div class="section">
    <div class="section-title">Earnings</div>
    <table>
        <thead>
            <tr><th>Component</th><th style="text-align:right">Amount (₦)</th></tr>
        </thead>
        <tbody>
            <tr><td>Basic Salary</td><td>{{ number_format($item->basic_salary ?? 0, 2) }}</td></tr>
            @if(($item->housing_allowance ?? 0) > 0)
            <tr><td>Housing Allowance</td><td>{{ number_format($item->housing_allowance, 2) }}</td></tr>
            @endif
            @if(($item->transport_allowance ?? 0) > 0)
            <tr><td>Transport Allowance</td><td>{{ number_format($item->transport_allowance, 2) }}</td></tr>
            @endif
            @if(($item->other_allowances ?? 0) > 0)
            <tr><td>Other Allowances</td><td>{{ number_format($item->other_allowances, 2) }}</td></tr>
            @endif
            <tr class="total-row">
                <td>Gross Salary</td>
                <td>₦{{ number_format($item->gross_pay ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Deductions --}}
@if(($item->total_deductions ?? 0) > 0)
<div class="section">
    <div class="section-title">Deductions</div>
    <table>
        <thead>
            <tr><th>Deduction</th><th style="text-align:right">Amount (₦)</th></tr>
        </thead>
        <tbody>
            @if(is_array($item->deduction_breakdown ?? null) && count($item->deduction_breakdown))
                @foreach($item->deduction_breakdown as $ded)
                <tr>
                    <td>{{ $ded['label'] ?? 'Deduction' }}</td>
                    <td>{{ number_format($ded['amount'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
                <tr><td>Tax (PAYE)</td><td>{{ number_format($item->tax_deduction ?? 0, 2) }}</td></tr>
                <tr><td>Pension (8%)</td><td>{{ number_format($item->pension_deduction ?? 0, 2) }}</td></tr>
            @else
            <tr><td>Tax (PAYE)</td><td>{{ number_format($item->tax_deduction ?? 0, 2) }}</td></tr>
            <tr><td>Pension (8%)</td><td>{{ number_format($item->pension_deduction ?? 0, 2) }}</td></tr>
            @endif
            <tr class="total-row" style="--tw:1px solid #DC2626">
                <td style="color:#DC2626">Total Deductions</td>
                <td style="color:#DC2626">₦{{ number_format($item->total_deductions ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endif

{{-- Net Pay --}}
<div class="net-box">
    <div>
        <div class="label">NET PAY</div>
        <div style="font-size:11px;opacity:0.7;margin-top:2px">Gross − Deductions</div>
    </div>
    <div class="amount">₦{{ number_format($item->net_pay ?? 0, 2) }}</div>
</div>

<div class="footer">
    <div>Generated: {{ now()->format('d M Y, H:i') }}</div>
    <div>This is a computer-generated payslip and requires no signature.</div>
    <div>{{ optional($tenant)->name }}</div>
</div>

</body>
</html>
