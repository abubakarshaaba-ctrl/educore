@extends('layouts.app')
@section('title','Financial Report')
@section('page-title','Financial Report')

@push('styles')
<style>
.filter-bar{background:white;border:1px solid var(--border);border-radius:10px;padding:12px 18px;margin-bottom:18px;display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap}
.fg{display:flex;flex-direction:column;gap:4px}
.fl{font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none}
.fc:focus{border-color:var(--indigo)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms;text-decoration:none}
.btn-p{background:var(--indigo);color:white}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}

/* KPI cards */
.kpi-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px;margin-bottom:22px}
.kpi{background:white;border:1px solid var(--border);border-radius:13px;padding:18px 20px;position:relative;overflow:hidden}
.kpi::after{content:'';position:absolute;right:-10px;top:-10px;width:70px;height:70px;border-radius:50%;opacity:.06}
.kpi-green::after{background:#059669}
.kpi-red::after{background:#DC2626}
.kpi-blue::after{background:#2563EB}
.kpi-amber::after{background:#D97706}
.kpi-val{font-size:22px;font-weight:800;letter-spacing:-0.02em;margin-bottom:4px}
.kpi-lbl{font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.06em}
.kpi-sub{font-size:11px;color:var(--slate-light);margin-top:5px}

/* Layout */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
.three-col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:12px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
.cb{padding:18px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:8px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);text-align:left}
td{padding:9px 14px;border-bottom:1px solid var(--border);color:var(--midnight)}
tr:last-child td{border:none}
tr:hover td{background:#F8FAFC}

/* Bar chart */
.bar-wrap{padding:0 18px 18px}
.bar-row{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.bar-label{font-size:12px;font-weight:600;color:var(--midnight);width:140px;flex-shrink:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar-track{flex:1;background:#F1F5F9;border-radius:4px;height:22px;position:relative;overflow:hidden}
.bar-fill{height:100%;border-radius:4px;transition:width .6s ease;display:flex;align-items:center;padding-left:8px}
.bar-fill span{font-size:11px;font-weight:700;color:white;white-space:nowrap}
.bar-amt{font-size:12px;font-weight:700;width:100px;text-align:right;flex-shrink:0}

/* Collection rate ring */
.rate-ring{width:120px;height:120px;position:relative;margin:0 auto 10px}
.rate-ring svg{transform:rotate(-90deg)}
.rate-text{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:var(--midnight)}

/* Month trend */
.trend-bar-row{display:flex;align-items:flex-end;gap:6px;height:120px;padding:0 18px 18px}
.trend-bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px}
.trend-bar{width:100%;background:var(--indigo);border-radius:4px 4px 0 0;min-height:4px;transition:height .5s ease}
.trend-lbl{font-size:9px;color:var(--slate-light);text-align:center}

@media print{.filter-bar,.btn{display:none}}
@media(max-width:900px){.two-col,.three-col{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
{{-- ── Filters ───────────────────────────────────────────────────── --}}
<form method="GET">
<div class="filter-bar">
    <div class="fg">
        <label class="fl">Academic Session</label>
        <select name="session_id" class="fc" onchange="this.form.submit()">
            @foreach($sessions as $s)
            <option value="{{ $s->id }}" {{ $s->id == $sessionId ? 'selected':'' }}>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="fg">
        <label class="fl">Term (optional)</label>
        <select name="term_id" class="fc" onchange="this.form.submit()">
            <option value="">All Terms</option>
            @foreach($terms as $t)
            <option value="{{ $t->id }}" {{ $t->id == $termId ? 'selected':'' }}>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-p" type="submit">Refresh</button>
    <button type="button" onclick="window.print()" class="btn btn-ghost" style="margin-left:auto">🖨 Print Report</button>
</div>
</form>

{{-- ── KPI Cards ─────────────────────────────────────────────────── --}}
@php
    $billed      = $feeData->billed ?? 0;
    $collected   = $feeData->collected ?? 0;
    $outstanding = $feeData->outstanding ?? 0;
    $netCollected = $netFeeCollections ?? $collected;
    $revenueDeductions = $totalRevenueDeductions ?? 0;
    $totalIncome = $netCollected;
    $totalCost   = ($totalExp ?? 0) + ($payrollCost ?? 0);
@endphp
<div class="kpi-row">
    <div class="kpi kpi-blue">
        <div class="kpi-val" style="color:var(--indigo)">₦{{ number_format($billed) }}</div>
        <div class="kpi-lbl">Total Billed</div>
        <div class="kpi-sub">{{ number_format($feeData->invoice_count ?? 0) }} invoices</div>
    </div>
    <div class="kpi kpi-green">
        <div class="kpi-val" style="color:var(--emerald)">₦{{ number_format($netCollected) }}</div>
        <div class="kpi-lbl">Net Fees Collected</div>
        <div class="kpi-sub">{{ $collectionRate }}% of collectable billed fees</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-val" style="color:var(--crimson)">₦{{ number_format($outstanding) }}</div>
        <div class="kpi-lbl">Outstanding</div>
        <div class="kpi-sub">{{ number_format($feeData->unpaid_count ?? 0) }} unpaid invoices</div>
    </div>
    <div class="kpi kpi-amber">
        <div class="kpi-val" style="color:var(--amber)">₦{{ number_format($revenueDeductions) }}</div>
        <div class="kpi-lbl">Deductions & Adjustments</div>
        <div class="kpi-sub">Discounts, waivers, reversals</div>
    </div>
    <div class="kpi kpi-amber">
        <div class="kpi-val" style="color:var(--amber)">₦{{ number_format($totalExp + $payrollCost) }}</div>
        <div class="kpi-lbl">Total Expenditure</div>
        <div class="kpi-sub">Expenses + paid payroll net pay</div>
    </div>
    <div class="kpi {{ $netBalance >= 0 ? 'kpi-green':'kpi-red' }}">
        <div class="kpi-val" style="color:{{ $netBalance >= 0 ? 'var(--emerald)':'var(--crimson)' }}">
            {{ $netBalance >= 0 ? '' : '-' }}₦{{ number_format(abs($netBalance)) }}
        </div>
        <div class="kpi-lbl">Net Balance</div>
        <div class="kpi-sub">Net collections minus outflows</div>
    </div>
    <div class="kpi">
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
            <div style="text-align:center">
                <div style="font-size:16px;font-weight:800;color:var(--emerald)">{{ number_format($feeData->fully_paid_count ?? 0) }}</div>
                <div style="font-size:10px;color:var(--slate-light)">Fully Paid</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:16px;font-weight:800;color:var(--amber)">{{ number_format($feeData->partial_count ?? 0) }}</div>
                <div style="font-size:10px;color:var(--slate-light)">Partial</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:16px;font-weight:800;color:var(--crimson)">{{ number_format($feeData->unpaid_count ?? 0) }}</div>
                <div style="font-size:10px;color:var(--slate-light)">Unpaid</div>
            </div>
        </div>
        <div class="kpi-lbl" style="text-align:center;margin-top:8px">Invoice Status</div>
    </div>
</div>

{{-- ── Row 1: Collection Rate + Monthly Trend ───────────────────── --}}
<div class="two-col">
    {{-- Collection Rate --}}
    <div class="card">
        <div class="ch">📊 Collection Rate</div>
        <div class="cb" style="text-align:center">
            @php
                $rate = $collectionRate;
                $circ = 2 * 3.14159 * 45; // circumference for r=45
                $dash = $circ * ($rate / 100);
                $gap  = $circ - $dash;
                $clr  = $rate >= 80 ? '#059669' : ($rate >= 60 ? '#D97706' : '#DC2626');
            @endphp
            <div class="rate-ring">
                <svg viewBox="0 0 100 100" width="120" height="120">
                    <circle cx="50" cy="50" r="45" fill="none" stroke="#E2E8F0" stroke-width="10"/>
                    <circle cx="50" cy="50" r="45" fill="none" stroke="{{ $clr }}" stroke-width="10"
                        stroke-dasharray="{{ $dash }} {{ $gap }}" stroke-linecap="round"/>
                </svg>
                <div class="rate-text" style="color:{{ $clr }}">{{ $rate }}%</div>
            </div>
            <div style="font-size:13px;font-weight:600;margin-bottom:12px">of collectable billed fees collected</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;text-align:left">
                <div style="background:#F0FDF4;border-radius:8px;padding:10px 14px">
                    <div style="font-size:12px;color:var(--emerald);font-weight:700">₦{{ number_format($netCollected) }}</div>
                    <div style="font-size:10px;color:#64748B;margin-top:2px">Net Collected</div>
                </div>
                <div style="background:#FEF2F2;border-radius:8px;padding:10px 14px">
                    <div style="font-size:12px;color:var(--crimson);font-weight:700">₦{{ number_format($outstanding) }}</div>
                    <div style="font-size:10px;color:#64748B;margin-top:2px">Outstanding</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly collection trend --}}
    <div class="card">
        <div class="ch">📈 Monthly Collection Trend</div>
        @if($monthlyCollection->isNotEmpty())
        @php $maxAmt = $monthlyCollection->max('amount') ?: 1; @endphp
        <div class="trend-bar-row">
            @foreach($monthlyCollection as $mon => $mc)
            @php $pct = ($mc->amount / $maxAmt) * 100; @endphp
            <div class="trend-bar-col">
                <div style="font-size:10px;color:var(--slate-light);margin-bottom:2px">₦{{ number_format($mc->amount/1000,0) }}k</div>
                <div class="trend-bar" style="height:{{ max(4, $pct * 0.8) }}px"></div>
                <div class="trend-lbl">{{ \Carbon\Carbon::createFromFormat('Y-m',$mon)->format('M') }}</div>
            </div>
            @endforeach
        </div>
        @else
        <div style="padding:40px;text-align:center;color:var(--slate-light);font-size:13px">No monthly data available</div>
        @endif
    </div>
</div>

{{-- ── Row 2: By Class + By Category ───────────────────────────── --}}
<div class="two-col">
    <div class="card">
        <div class="ch">🏫 Collections by Class</div>
        @if($byClass->isNotEmpty())
        @php $maxB = $byClass->max('billed') ?: 1; @endphp
        <div class="bar-wrap">
            @foreach($byClass as $row)
            @php $pct = round(($row->collected / max($row->billed, 1)) * 100); @endphp
            <div class="bar-row">
                <div class="bar-label" title="{{ $row->class_name }}">{{ $row->class_name }}</div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:{{ $pct }}%;background:{{ $pct >= 80 ? 'var(--emerald)':($pct >= 60 ? 'var(--amber)':'var(--crimson)') }}">
                        <span>{{ $pct }}%</span>
                    </div>
                </div>
                <div class="bar-amt">₦{{ number_format($row->collected) }}</div>
            </div>
            @endforeach
        </div>
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Class</th><th>Billed</th><th>Collected</th><th>Outstanding</th></tr></thead>
            <tbody>
            @foreach($byClass as $row)
            <tr>
                <td style="font-weight:600">{{ $row->class_name }}</td>
                <td>₦{{ number_format($row->billed) }}</td>
                <td style="color:var(--emerald);font-weight:600">₦{{ number_format($row->collected) }}</td>
                <td style="color:var(--crimson);font-weight:600">₦{{ number_format($row->outstanding) }}</td>
            </tr>
            @endforeach
            </tbody>
        </table></div>
        </div>
        @else
        <div style="padding:30px;text-align:center;color:var(--slate-light)">No class data available</div>
        @endif
    </div>

    <div class="card">
        <div class="ch">🏷 Revenue by Fee Category</div>
        @if($byCategory->isNotEmpty())
        @php $maxCat = $byCategory->max('total') ?: 1; @endphp
        <div class="bar-wrap">
            @foreach($byCategory as $cat)
            @php $pct = round(($cat->total / $maxCat) * 100); @endphp
            <div class="bar-row">
                <div class="bar-label">{{ $cat->category }}</div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:{{ $pct }}%;background:var(--indigo)">
                        <span>{{ $pct }}%</span>
                    </div>
                </div>
                <div class="bar-amt">₦{{ number_format($cat->total) }}</div>
            </div>
            @endforeach
        </div>
        @else
        <div style="padding:30px;text-align:center;color:var(--slate-light)">No category data available</div>
        @endif
    </div>
</div>

{{-- ── Row 3: Expenditure breakdown + P&L summary ───────────────── --}}
<div class="two-col">
    <div class="card">
        <div class="ch">Revenue Deductions & Adjustments</div>
        <div class="tbl"><table>
            <thead><tr><th>Deduction Type</th><th style="text-align:right">Amount</th></tr></thead>
            <tbody>
            <tr>
                <td>Generation discounts</td>
                <td style="text-align:right;color:var(--crimson);font-weight:600">₦{{ number_format($generationDiscountTotal ?? 0) }}</td>
            </tr>
            <tr>
                <td>Approved invoice discounts</td>
                <td style="text-align:right;color:var(--crimson);font-weight:600">₦{{ number_format($approvedDiscountTotal ?? 0) }}</td>
            </tr>
            <tr>
                <td>Waived invoice balances <span style="font-size:11px;color:var(--slate-light)">({{ number_format($waivedInvoiceCount ?? 0) }} invoices)</span></td>
                <td style="text-align:right;color:var(--crimson);font-weight:600">₦{{ number_format($waivedInvoiceTotal ?? 0) }}</td>
            </tr>
            <tr>
                <td>Reversed payment transactions</td>
                <td style="text-align:right;color:var(--crimson);font-weight:600">₦{{ number_format($reversedPaymentTotal ?? 0) }}</td>
            </tr>
            <tr style="background:#FEF2F2;border-top:2px solid var(--border)">
                <td style="font-weight:800;color:var(--crimson)">Total Revenue Deductions</td>
                <td style="text-align:right;font-weight:800;color:var(--crimson)">₦{{ number_format($totalRevenueDeductions ?? 0) }}</td>
            </tr>
            </tbody>
        </table></div>
    </div>

    <div class="card">
        <div class="ch">Payroll Deduction Summary</div>
        <div class="tbl"><table>
            <thead><tr><th>Deduction Type</th><th style="text-align:right">Amount</th></tr></thead>
            <tbody>
            <tr>
                <td>Tax deductions</td>
                <td style="text-align:right">₦{{ number_format($payrollDeductions['tax'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>Pension deductions</td>
                <td style="text-align:right">₦{{ number_format($payrollDeductions['pension'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>Other payroll deductions</td>
                <td style="text-align:right">₦{{ number_format($payrollDeductions['other'] ?? 0) }}</td>
            </tr>
            <tr style="background:#F8FAFC;border-top:2px solid var(--border)">
                <td style="font-weight:800">Total Withheld Deductions</td>
                <td style="text-align:right;font-weight:800">₦{{ number_format($payrollDeductions['total'] ?? 0) }}</td>
            </tr>
            <tr>
                <td style="font-size:11px;color:var(--slate-light)" colspan="2">
                    Payroll deductions are shown separately. They are not subtracted again because paid payroll net pay already excludes them.
                </td>
            </tr>
            </tbody>
        </table></div>
    </div>
</div>

<div class="two-col">
    <div class="card">
        <div class="ch">💸 Expenditure Breakdown</div>
        <div class="tbl"><table>
            <thead><tr><th>Category</th><th style="text-align:right">Amount</th><th style="text-align:right">% of Total</th></tr></thead>
            <tbody>
            @foreach($expenses as $exp)
            @php $pct = $totalExp > 0 ? round(($exp->total/$totalExp)*100,1) : 0; @endphp
            <tr>
                <td style="text-transform:capitalize">{{ str_replace('_',' ',$exp->category) }}</td>
                <td style="text-align:right;font-weight:600">₦{{ number_format($exp->total) }}</td>
                <td style="text-align:right;color:var(--slate)">{{ $pct }}%</td>
            </tr>
            @endforeach
            @if($payrollCost > 0)
            <tr style="background:#FAFBFF">
                <td style="font-weight:600">Paid payroll net pay</td>
                <td style="text-align:right;font-weight:700">₦{{ number_format($payrollCost) }}</td>
                <td style="text-align:right;color:var(--slate)">
                    {{ ($totalExp + $payrollCost) > 0 ? round(($payrollCost/($totalExp+$payrollCost))*100,1) : 0 }}%
                </td>
            </tr>
            @endif
            <tr style="background:#F8FAFC;border-top:2px solid var(--border)">
                <td style="font-weight:800">TOTAL EXPENDITURE</td>
                <td style="text-align:right;font-weight:800;color:var(--crimson)">₦{{ number_format($totalExp + $payrollCost) }}</td>
                <td style="text-align:right">100%</td>
            </tr>
            </tbody>
        </table></div>
    </div>

    <div class="card">
        <div class="ch">📋 Profit & Loss Summary</div>
        <div class="cb">
            <div class="tbl"><table>
                <tbody>
                <tr style="background:#F0FDF4">
                    <td style="font-weight:700;color:var(--emerald)">INCOME</td>
                    <td></td>
                </tr>
                <tr>
                    <td style="padding-left:20px">Posted fee collections</td>
                    <td style="text-align:right;font-weight:600">₦{{ number_format($collected) }}</td>
                </tr>
                @if(($reversedPaymentTotal ?? 0) > 0)
                <tr>
                    <td style="padding-left:20px">Less payment reversals</td>
                    <td style="text-align:right;color:var(--crimson);font-weight:600">-₦{{ number_format($reversedPaymentTotal) }}</td>
                </tr>
                @endif
                <tr style="background:#F8FAFC">
                    <td style="font-weight:700;color:var(--crimson)">Total Income</td>
                    <td style="text-align:right;font-weight:800;color:var(--emerald)">₦{{ number_format($netCollected) }}</td>
                </tr>
                <tr style="background:#FEF9F0;margin-top:8px">
                    <td style="font-weight:700;color:var(--crimson);padding-top:12px">EXPENDITURE</td>
                    <td></td>
                </tr>
                @foreach($expenses as $exp)
                <tr>
                    <td style="padding-left:20px;text-transform:capitalize">{{ str_replace('_',' ',$exp->category) }}</td>
                    <td style="text-align:right">₦{{ number_format($exp->total) }}</td>
                </tr>
                @endforeach
                @if($payrollCost > 0)
                <tr>
                    <td style="padding-left:20px">Paid payroll net pay</td>
                    <td style="text-align:right">₦{{ number_format($payrollCost) }}</td>
                </tr>
                @endif
                <tr style="background:#FEF2F2">
                    <td style="font-weight:700;color:var(--crimson)">Total Expenditure</td>
                    <td style="text-align:right;font-weight:800;color:var(--crimson)">₦{{ number_format($totalExp + $payrollCost) }}</td>
                </tr>
                <tr style="background:{{ $netBalance >= 0 ? '#ECFDF5':'#FEF2F2' }};border-top:2px solid {{ $netBalance >= 0 ? '#059669':'#DC2626' }}">
                    <td style="font-weight:800;font-size:14px">NET {{ $netBalance >= 0 ? 'SURPLUS':'DEFICIT' }}</td>
                    <td style="text-align:right;font-weight:900;font-size:16px;color:{{ $netBalance >= 0 ? 'var(--emerald)':'var(--crimson)' }}">
                        {{ $netBalance >= 0 ? '' : '-' }}₦{{ number_format(abs($netBalance)) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div style="text-align:center;font-size:11px;color:var(--slate-light);padding:10px 0">
    Report generated {{ now()->format('d M Y, H:i') }} · Data filtered by {{ $termId ? 'selected term' : 'all terms in session' }}
</div>
@endsection
