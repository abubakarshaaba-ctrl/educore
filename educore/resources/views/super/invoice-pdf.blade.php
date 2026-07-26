<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice {{ $invoice->invoice_number }}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Arial',sans-serif;font-size:11pt;color:#0F172A;background:white;padding:28pt}
.header{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:16pt;border-bottom:3pt solid #2563EB;margin-bottom:20pt}
.co-name{font-size:18pt;font-weight:800;color:#0F172A}
.co-sub{font-size:10pt;color:#64748B;margin-top:3pt}
.inv-title{font-size:14pt;font-weight:800;color:#2563EB;text-align:right}
.inv-meta{font-size:9pt;color:#64748B;text-align:right;margin-top:4pt;line-height:1.6}
.bill-row{display:flex;justify-content:space-between;margin-bottom:18pt}
.bill-box{background:#F8FAFC;border:1pt solid #E2E8F0;border-radius:6pt;padding:12pt;width:48%}
.bill-lbl{font-size:8pt;font-weight:700;text-transform:uppercase;color:#94A3B8;letter-spacing:.06em;margin-bottom:6pt}
.bill-name{font-size:13pt;font-weight:700;color:#0F172A}
.bill-detail{font-size:10pt;color:#64748B;margin-top:3pt;line-height:1.5}
table{width:100%;border-collapse:collapse;margin-bottom:20pt}
th{padding:9pt 12pt;background:#0F172A;color:white;font-size:9pt;font-weight:700;text-transform:uppercase;letter-spacing:.06em;text-align:left}
td{padding:10pt 12pt;border-bottom:1pt solid #E2E8F0;font-size:11pt}
.tot-row td{border-top:2pt solid #E2E8F0;font-weight:700;font-size:13pt}
.status-box{padding:8pt 14pt;border-radius:6pt;font-size:10pt;font-weight:700;display:inline-block;margin-bottom:16pt}
.s-paid{background:#ECFDF5;color:#059669;border:1pt solid #A7F3D0}
.s-pending{background:#FFFBEB;color:#D97706;border:1pt solid #FDE68A}
.s-overdue{background:#FEF2F2;color:#DC2626;border:1pt solid #FECACA}
.footer{margin-top:24pt;padding-top:12pt;border-top:1pt dashed #CBD5E1;font-size:8pt;color:#94A3B8;display:flex;justify-content:space-between}
.no-print{margin-bottom:14pt}
@media print{.no-print{display:none}body{padding:0}}
</style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()" style="padding:8pt 18pt;background:#D79A21;color:white;border:none;border-radius:7pt;font-size:12pt;font-weight:700;cursor:pointer;margin-right:10pt">🖨 Print / Save PDF</button>
    <a href="{{ route('super.billing') }}" style="padding:8pt 16pt;background:#F1F5F9;color:#475569;border:1pt solid #E2E8F0;border-radius:7pt;font-size:12pt;font-weight:700;text-decoration:none">← Back</a>
</div>

<div class="header">
    <div>
        <div class="co-name">{{ $superSettings->get('platform_name', 'EduCore') }}</div>
        <div class="co-sub">{{ $superSettings->get('platform_email', 'admin@enterprisesms.com') }}</div>
        <div class="co-sub">{{ $superSettings->get('platform_address', '') }}</div>
    </div>
    <div>
        <div class="inv-title">INVOICE</div>
        <div class="inv-meta">
            <strong>{{ $invoice->invoice_number }}</strong><br>
            Issued: {{ \Carbon\Carbon::parse($invoice->created_at)->format('d M Y') }}<br>
            Due: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}
        </div>
    </div>
</div>

<span class="status-box s-{{ $invoice->status }}">{{ strtoupper($invoice->status) }}</span>

<div class="bill-row">
    <div class="bill-box">
        <div class="bill-lbl">From</div>
        <div class="bill-name">{{ $superSettings->get('platform_name','EduCore') }}</div>
        <div class="bill-detail">School Management Software Platform<br>{{ $superSettings->get('platform_email','') }}</div>
    </div>
    <div class="bill-box">
        <div class="bill-lbl">Billed To</div>
        <div class="bill-name">{{ $invoice->school_name }}</div>
        <div class="bill-detail">{{ $invoice->school_address ?? '' }}<br>{{ $invoice->school_email ?? '' }}</div>
    </div>
</div>

<table>
    <thead>
        <tr><th>Description</th><th>Billing Cycle</th><th>Period</th><th style="text-align:right">Amount</th></tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Pay-Per-Student Subscription</strong> — {{ number_format($invoice->student_count) }} students<br>
                <span style="font-size:9pt;color:#64748B">School Management Software — EduCore</span>
            </td>
            <td style="text-transform:capitalize">{{ $invoice->billing_cycle }}</td>
            <td>{{ \Carbon\Carbon::parse($invoice->due_date)->format('M Y') }}</td>
            <td style="text-align:right;font-weight:700">₦{{ number_format($invoice->amount) }}</td>
        </tr>
        @if($invoice->notes)
        <tr><td colspan="4" style="background:#F8FAFC;font-size:10pt;color:#64748B;padding:8pt 12pt">Note: {{ $invoice->notes }}</td></tr>
        @endif
    </tbody>
    <tfoot>
        <tr class="tot-row">
            <td colspan="3" style="text-align:right;color:#64748B">TOTAL DUE</td>
            <td style="text-align:right;font-size:16pt;color:#2563EB">₦{{ number_format($invoice->amount) }}</td>
        </tr>
    </tfoot>
</table>

@if($invoice->status === 'paid')
<div style="background:#ECFDF5;border:2pt solid #A7F3D0;border-radius:8pt;padding:12pt 16pt;margin-bottom:16pt">
    <div style="font-size:11pt;font-weight:700;color:#059669">✓ PAYMENT RECEIVED</div>
    <div style="font-size:10pt;color:#065F46;margin-top:4pt">
        Paid on {{ $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->format('d M Y') : '—' }}
        via {{ str_replace('_',' ',ucfirst($invoice->payment_method ?? '')) }}
        @if($invoice->payment_ref) · Ref: {{ $invoice->payment_ref }} @endif
    </div>
</div>
@else
<div style="background:#F8FAFC;border:1pt solid #E2E8F0;border-radius:8pt;padding:12pt 16pt;margin-bottom:16pt">
    <div style="font-size:10pt;font-weight:700;color:#0F172A">Payment Instructions</div>
    <div style="font-size:10pt;color:#475569;margin-top:4pt;line-height:1.6">
        {{ $superSettings->get('payment_instructions','Please contact us for payment details.') }}
    </div>
</div>
@endif

<div class="footer">
    <div>{{ $superSettings->get('platform_name','EduCore') }} · {{ $invoice->invoice_number }}</div>
    <div>Generated: {{ now()->format('d M Y') }}</div>
    <div>{{ $superSettings->get('platform_email','') }}</div>
</div>
</body>
</html>
