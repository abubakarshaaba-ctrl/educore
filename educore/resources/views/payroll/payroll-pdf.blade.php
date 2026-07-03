<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { size: A4 landscape; margin: 10mm; }* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #111; }
.header { text-align:center; margin-bottom:14px; border-bottom:2px solid #071E45; padding-bottom:8px; }
.header h1 { font-size:14pt; font-weight:bold; color:#071E45; }
.header p { font-size:9pt; color:#475569; margin-top:3px; }
.summary { display:table; width:100%; table-layout:fixed; border-spacing:6px 0; margin:0 -6px 14px; }
.sum-box { display:table-cell; width:25%; vertical-align:top; border:1px solid #ccc; border-radius:6px; padding:9px 10px; text-align:center; }
.sum-val { font-size:13pt; font-weight:bold; }
.sum-lbl { font-size:8pt; color:#888; text-transform:uppercase; margin-top:2px; }
table { width:100%; table-layout:fixed; border-collapse:collapse; font-size:7.2pt; }
thead { display:table-header-group; }
tbody tr { page-break-inside:avoid; }
thead th { background:#071E45; color:white; padding:5pt 4pt; text-align:left; font-size:6.5pt; }
tbody td { padding:4pt; border-bottom:1px solid #e2e8f0; vertical-align:top; overflow-wrap:anywhere; }
tbody tr:nth-child(even) td { background:#f8fafc; }
.tfoot td { font-weight:bold; background:#f1f5f9; border-top:2px solid #cbd5e1; padding:6pt 8pt; }
.amt { text-align:right; }
.red { color:#B91C1C; }
.green { color:#15803D; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ optional($tenant)->name }}</h1>
    <p>Payroll — {{ $period->title }} &nbsp;|&nbsp; {{ \Carbon\Carbon::parse($period->period_start)->format('d M') }} – {{ \Carbon\Carbon::parse($period->period_end)->format('d M Y') }} &nbsp;|&nbsp; Status: {{ ucfirst($period->status) }}</p>
</div>

<div class="summary">
    <div class="sum-box">
        <div class="sum-val">₦{{ number_format($period->total_gross) }}</div>
        <div class="sum-lbl">Total Gross</div>
    </div>
    <div class="sum-box">
        <div class="sum-val red">₦{{ number_format($period->total_deductions) }}</div>
        <div class="sum-lbl">Total Deductions</div>
    </div>
    <div class="sum-box">
        <div class="sum-val green">₦{{ number_format($period->total_net) }}</div>
        <div class="sum-lbl">Net Pay</div>
    </div>
    <div class="sum-box">
        <div class="sum-val">{{ $items->count() }}</div>
        <div class="sum-lbl">Staff Count</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th><th>Staff Name</th><th>Role</th>
            <th class="amt">Basic</th><th class="amt">Allowances</th>
            <th class="amt">Gross</th><th class="amt">Tax</th><th class="amt">Pension</th>
            <th class="amt">Net Pay</th><th>Bank / Account</th>
        </tr>
    </thead>
    <tbody>
    @foreach($items as $i => $item)
    <tr>
        <td>{{ $i + 1 }}</td>
        <td><strong>{{ optional($item->staff)->name ?? '—' }}</strong></td>
        <td>{{ optional($item->staff)->role ?? '—' }}</td>
        <td class="amt">₦{{ number_format($item->basic_salary) }}</td>
        <td class="amt">₦{{ number_format($item->housing_allowance + $item->transport_allowance + $item->other_allowances) }}</td>
        <td class="amt"><strong>₦{{ number_format($item->gross_pay) }}</strong></td>
        <td class="amt red">₦{{ number_format($item->tax_deduction) }}</td>
        <td class="amt red">₦{{ number_format($item->pension_deduction) }}</td>
        <td class="amt green"><strong>₦{{ number_format($item->net_pay) }}</strong></td>
        <td>{{ $item->bank_name }}<br>{{ $item->account_number }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr class="tfoot">
            <td colspan="3"><strong>TOTAL</strong></td>
            <td class="amt">₦{{ number_format($period->total_gross) }}</td>
            <td class="amt"></td>
            <td class="amt">₦{{ number_format($period->total_gross) }}</td>
            <td class="amt red">₦{{ number_format($items->sum('tax_deduction')) }}</td>
            <td class="amt red">₦{{ number_format($items->sum('pension_deduction')) }}</td>
            <td class="amt green">₦{{ number_format($period->total_net) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<p style="margin-top:16px;font-size:8pt;color:#888;text-align:right">Generated: {{ now()->format('d M Y H:i') }}</p>
</body>
</html>
