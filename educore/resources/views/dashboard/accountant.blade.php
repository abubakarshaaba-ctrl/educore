@extends('layouts.app')
@section('title','Finance Dashboard')
@section('page-title','Finance Dashboard')

@push('styles')
<style>
.role-hero{display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:18px;flex-wrap:wrap}
.role-hero h1{font-size:22px;margin:0;color:var(--midnight)}
.role-hero p{margin:4px 0 0;font-size:13px;color:var(--slate-light)}
.metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}
.metric{background:white;border:1px solid var(--border);border-radius:12px;padding:15px}
.metric .label{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);font-weight:700}
.metric .value{font-size:23px;font-weight:700;color:var(--midnight);margin-top:5px;overflow-wrap:anywhere}
.metric .sub{font-size:11px;color:var(--slate-light);margin-top:3px}
.two-col-dash{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(300px,.85fr);gap:16px;align-items:start}
.dash-card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.dash-head{padding:13px 16px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;justify-content:space-between;align-items:center;gap:10px}
.dash-head strong{font-size:13px;color:var(--midnight)}
.tbl-lite{width:100%;border-collapse:collapse;min-width:640px}
.tbl-lite th,.tbl-lite td{padding:11px 14px;border-bottom:1px solid #EEF2F7;text-align:left;font-size:12px}
.tbl-lite th{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);background:#FCFDFE}
.tbl-lite tr:last-child td{border-bottom:0}
.amount{font-variant-numeric:tabular-nums;white-space:nowrap}
.status{display:inline-flex;padding:3px 8px;border-radius:999px;font-size:10px;font-weight:700;text-transform:capitalize;background:#F1F5F9;color:var(--slate)}
.status.paid,.status.approved{background:#ECFDF5;color:#047857}.status.unpaid,.status.draft{background:#FFF7ED;color:#C2410C}.status.partially_paid{background:#EFF6FF;color:#1D4ED8}
.list-row{display:flex;justify-content:space-between;gap:12px;padding:12px 16px;border-bottom:1px solid #EEF2F7;font-size:12px}
.list-row:last-child{border-bottom:0}.muted{color:var(--slate-light)}
.quick-actions{display:flex;gap:8px;flex-wrap:wrap}
.progress{height:8px;background:#E2E8F0;border-radius:999px;overflow:hidden;margin-top:8px}.progress span{display:block;height:100%;background:var(--indigo);border-radius:999px}
@media(max-width:1024px){.metric-grid{grid-template-columns:repeat(2,1fr)}.two-col-dash{grid-template-columns:1fr}}
@media(max-width:520px){.metric-grid{grid-template-columns:1fr 1fr}.metric .value{font-size:19px}}
</style>
@endpush

@section('content')
<div class="role-hero">
    <div>
        <h1>Finance Operations</h1>
        <p>{{ $currentTerm ? $currentTerm->name : 'Current financial position' }} — fees, collections, expenses and payroll.</p>
    </div>
    <div class="quick-actions">
        <a href="{{ route('fees.invoices') }}" class="btn btn-secondary">Fees & Invoices</a>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Expenses</a>
        <a href="{{ route('payroll.index') }}" class="btn btn-primary">Payroll</a>
    </div>
</div>

<div class="metric-grid">
    <div class="metric"><div class="label">Total Invoiced</div><div class="value">₦{{ number_format($stats['invoiced'],2) }}</div><div class="sub">{{ $currentTerm ? 'Current term' : 'All available records' }}</div></div>
    <div class="metric"><div class="label">Collected</div><div class="value">₦{{ number_format($stats['collected'],2) }}</div><div class="sub">{{ number_format($stats['collection_rate'],1) }}% collection rate<div class="progress"><span style="width:{{ min(100,$stats['collection_rate']) }}%"></span></div></div></div>
    <div class="metric"><div class="label">Outstanding</div><div class="value">₦{{ number_format($stats['outstanding'],2) }}</div><div class="sub">Uncollected invoice balance</div></div>
    <div class="metric"><div class="label">Expenses This Month</div><div class="value">₦{{ number_format($stats['expenses_this_month'],2) }}</div><div class="sub">Recorded school expenses</div></div>
    <div class="metric"><div class="label">Unpaid Invoices</div><div class="value">{{ number_format($stats['unpaid_invoices']) }}</div><div class="sub">No payment recorded</div></div>
    <div class="metric"><div class="label">Partially Paid</div><div class="value">{{ number_format($stats['partial_invoices']) }}</div><div class="sub">Invoices with outstanding balances</div></div>
    <div class="metric"><div class="label">Overdue Invoices</div><div class="value">{{ number_format($stats['overdue_invoices']) }}</div><div class="sub">Past due date and not fully paid</div></div>
    <div class="metric"><div class="label">Current-Term Expenses</div><div class="value">₦{{ number_format($stats['expenses_current_term'],2) }}</div><div class="sub">Expense exposure for the active term</div></div>
</div>

<div class="two-col-dash">
    <div>
        <div class="dash-card">
            <div class="dash-head"><strong>Recent Invoices</strong><a href="{{ route('fees.invoices') }}" style="font-size:11px;text-decoration:none">View all</a></div>
            <div class="responsive-table">
                <table class="tbl-lite">
                    <thead><tr><th>Student</th><th>Invoice</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($recentInvoices as $invoice)
                        @php $balance = max(0,(float)$invoice->total_amount-(float)$invoice->amount_paid); @endphp
                        <tr>
                            <td>{{ optional($invoice->student)->full_name ?? optional($invoice->student)->name ?? '—' }}</td>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td class="amount">₦{{ number_format($invoice->total_amount,2) }}</td>
                            <td class="amount">₦{{ number_format($invoice->amount_paid,2) }}</td>
                            <td class="amount">₦{{ number_format($balance,2) }}</td>
                            <td><span class="status {{ $invoice->status }}">{{ str_replace('_',' ',$invoice->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">No invoices available.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-head"><strong>Recent Expenses</strong><a href="{{ route('expenses.index') }}" style="font-size:11px;text-decoration:none">View all</a></div>
            <div class="responsive-table">
                <table class="tbl-lite">
                    <thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Method</th><th>Amount</th></tr></thead>
                    <tbody>
                    @forelse($recentExpenses as $expense)
                        <tr>
                            <td>{{ optional($expense->expense_date)->format('d M Y') }}</td>
                            <td>{{ $expense->title }}</td>
                            <td>{{ ucfirst($expense->category) }}</td>
                            <td>{{ $expense->payment_method ? ucfirst($expense->payment_method) : '—' }}</td>
                            <td class="amount"><strong>₦{{ number_format($expense->amount,2) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">No expenses recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="dash-card">
            <div class="dash-head"><strong>Latest Payroll</strong><a href="{{ route('payroll.index') }}" style="font-size:11px;text-decoration:none">Manage</a></div>
            @if($latestPayroll)
                <div class="list-row"><span>Period</span><strong>{{ $latestPayroll->title }}</strong></div>
                <div class="list-row"><span>Gross payroll</span><strong class="amount">₦{{ number_format((float)$latestPayroll->total_gross,2) }}</strong></div>
                <div class="list-row"><span>Deductions</span><strong class="amount">₦{{ number_format((float)$latestPayroll->total_deductions,2) }}</strong></div>
                <div class="list-row"><span>Net payroll</span><strong class="amount">₦{{ number_format((float)$latestPayroll->total_net,2) }}</strong></div>
                <div class="list-row"><span>Status</span><span class="status {{ $latestPayroll->status }}">{{ $latestPayroll->status ?: 'draft' }}</span></div>
            @else
                <div class="list-row"><span class="muted">No payroll period generated yet.</span></div>
            @endif
        </div>

        <div class="dash-card">
            <div class="dash-head"><strong>Recent Payroll Periods</strong></div>
            @forelse($payrollPeriods as $period)
                <div class="list-row">
                    <div><strong>{{ $period->title }}</strong><div class="muted">{{ $period->period_end ? \Carbon\Carbon::parse($period->period_end)->format('d M Y') : '—' }}</div></div>
                    <div style="text-align:right"><strong class="amount">₦{{ number_format((float)$period->total_net,2) }}</strong><div><span class="status {{ $period->status }}">{{ $period->status ?: 'draft' }}</span></div></div>
                </div>
            @empty
                <div class="list-row"><span class="muted">No payroll history available.</span></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
