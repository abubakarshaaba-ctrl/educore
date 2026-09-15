@extends('layouts.app')
@section('title', 'Invoice ' . $invoice->invoice_number)
@section('page-title', 'Invoice Detail')

@push('styles')
<style>
    .invoice-breadcrumb{display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--slate-light)}
    .invoice-breadcrumb a{color:var(--brand-navy);font-weight:700;text-decoration:none}.invoice-breadcrumb a:hover{color:var(--brand-gold-dark)}
    .invoice-grid{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:20px;align-items:start}
    .finance-card{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 8px 24px rgba(15,23,42,.05);overflow:hidden;margin-bottom:16px}
    .finance-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid var(--border);background:var(--brand-navy-soft)}
    .finance-card-title{font-size:14px;font-weight:800;color:var(--brand-navy)}
    .finance-card-body{padding:20px}
    .invoice-heading-actions{display:flex;align-items:center;gap:9px;flex-wrap:wrap}
    .invoice-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:24px}
    .meta-label{margin-bottom:4px;font-size:11px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em}
    .meta-value{font-size:14px;font-weight:700;color:var(--midnight)}.meta-sub{margin-top:2px;font-size:12px;color:var(--slate)}
    .invoice-table-wrap{overflow-x:auto}.invoice-table{width:100%;border-collapse:collapse}.invoice-table th{padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em}.invoice-table td{padding:13px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight)}.invoice-table tbody tr:last-child td{border-bottom:0}.invoice-table tfoot td{padding:13px 16px;font-size:14px;font-weight:800;background:#F8FAFC;border-top:2px solid var(--border)}
    .amount-right{text-align:right}
    .status-badge{display:inline-flex;align-items:center;min-height:28px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:800}.status-paid{background:#ECFDF5;color:#047857}.status-partial{background:#FFFBEB;color:#B45309}.status-unpaid{background:#FEF2F2;color:#B91C1C}
    .btn-print,.btn-primary-finance,.btn-success-finance,.btn-secondary-finance{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:8px 14px;border-radius:9px;font:inherit;font-size:12px;font-weight:800;cursor:pointer;text-decoration:none}
    .btn-print{border:1px solid var(--brand-navy);background:var(--brand-navy);color:#fff}.btn-print:hover{background:var(--brand-navy-hover)}
    .btn-primary-finance{width:100%;border:1px solid var(--brand-gold);background:var(--brand-gold);color:var(--brand-navy)}.btn-primary-finance:hover{background:var(--brand-gold-dark);border-color:var(--brand-gold-dark)}
    .btn-success-finance{border:1px solid #A7F3D0;background:#ECFDF5;color:#047857}.btn-success-finance:hover{background:#D1FAE5}
    .btn-secondary-finance{border:1px solid var(--border);background:#fff;color:var(--brand-navy)}.btn-secondary-finance:hover{background:var(--brand-navy-soft)}
    .balance-display{padding:16px;margin-bottom:20px;border:1px solid var(--border);border-radius:10px;background:#F8FAFC}.balance-row{display:flex;justify-content:space-between;gap:12px;padding:5px 0;font-size:13px}.balance-row strong{font-weight:800}.balance-row.balance{padding-top:10px;margin-top:8px;border-top:1px solid var(--border);color:var(--crimson);font-size:15px;font-weight:800}
    .form-group{margin-bottom:14px}.form-label{display:block;margin-bottom:5px;font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}.form-label span{color:var(--crimson)}
    .form-control{width:100%;min-height:44px;padding:9px 12px;border:1px solid var(--border);border-radius:9px;background:#F8FAFC;color:var(--midnight);font:inherit;font-size:13px;outline:none}.form-control:focus{background:#fff;border-color:var(--brand-gold-dark);box-shadow:0 0 0 3px rgba(215,154,33,.16)}
    .alert-success{margin-bottom:16px;padding:12px 16px;border:1px solid #A7F3D0;border-radius:9px;background:#ECFDF5;color:#047857;font-size:13px}
    .txn-row{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid var(--border);font-size:13px}.txn-row:last-child{border-bottom:0}.txn-name{font-weight:700;color:var(--midnight)}.txn-meta{margin-top:2px;font-size:11px;color:var(--slate-light)}.txn-amount{font-weight:800;color:var(--emerald);white-space:nowrap}
    .paid-state{padding:20px;text-align:center;color:var(--emerald);font-size:14px;font-weight:800}
    .plan-card{margin-top:16px}.plan-head-meta{font-size:11px;font-weight:600;color:var(--slate)}
    .installment-row{display:grid;grid-template-columns:minmax(140px,1fr) minmax(200px,auto) auto;gap:14px;align-items:center;padding:14px 18px;border-bottom:1px solid var(--border)}.installment-row:last-child{border-bottom:0}.installment-title{font-size:13px;font-weight:800;color:var(--midnight)}.installment-due{margin-top:2px;font-size:11px;color:var(--slate-light)}.installment-money{text-align:right;font-size:13px}.installment-status{margin-top:2px;font-size:11px;font-weight:800}.status-text-paid{color:var(--emerald)}.status-text-overdue{color:var(--crimson)}.status-text-pending{color:var(--amber)}
    .installment-action{display:flex;flex-direction:column;align-items:flex-end;gap:8px}.installment-form{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}.installment-form .form-control{width:auto;min-height:38px;padding:6px 9px;font-size:12px}.installment-form input{max-width:120px}.installment-check{font-size:20px;color:var(--emerald);font-weight:800}
    .assign-plan-form{display:grid;grid-template-columns:minmax(200px,1fr) minmax(170px,.7fr) auto;gap:10px;align-items:end}.assign-plan-form .form-group{margin-bottom:0}.assign-plan-form .btn-primary-finance{width:auto;min-height:44px}
    .hidden{display:none!important}
    @media(max-width:1024px){.invoice-grid{grid-template-columns:1fr}}
    @media(max-width:720px){.invoice-meta{grid-template-columns:1fr}.finance-card-head{align-items:flex-start;flex-direction:column}.installment-row{grid-template-columns:1fr}.installment-money{text-align:left}.installment-action{align-items:flex-start}.installment-form{justify-content:flex-start}.assign-plan-form{grid-template-columns:1fr}.assign-plan-form .btn-primary-finance{width:100%}.txn-row{align-items:flex-start}}
</style>
@endpush

@section('content')
<div class="invoice-breadcrumb">
    <a href="{{ route('fees.invoices') }}">Invoices</a><span aria-hidden="true">›</span><span>{{ $invoice->invoice_number }}</span>
</div>

@if(session('success'))<div class="alert-success" role="status">{{ session('success') }}</div>@endif

<div class="invoice-grid">
    <div>
        <div class="finance-card">
            <div class="finance-card-head">
                <div class="invoice-heading-actions">
                    <span class="finance-card-title">{{ $invoice->invoice_number }}</span>
                    @if($invoice->amount_paid > 0)
                        <a href="{{ route('fees.invoices.print', $invoice) }}" target="_blank" rel="noopener" class="btn-print">Print Invoice</a>
                    @endif
                </div>
                @if($invoice->status === 'paid')
                    <span class="status-badge status-paid">Paid</span>
                @elseif($invoice->status === 'partially_paid')
                    <span class="status-badge status-partial">Partially Paid</span>
                @else
                    <span class="status-badge status-unpaid">Unpaid</span>
                @endif
            </div>
            <div class="finance-card-body">
                <div class="invoice-meta">
                    <div><div class="meta-label">Student</div><div class="meta-value">{{ optional($invoice->student)->full_name }}</div><div class="meta-sub">{{ optional($invoice->student)->admission_number }}</div></div>
                    <div><div class="meta-label">Class</div><div class="meta-value">{{ optional(optional($invoice->student)->currentClassArm)->classLevel->name }} {{ optional(optional($invoice->student)->currentClassArm)->name }}</div></div>
                    <div><div class="meta-label">Term</div><div class="meta-value">{{ optional($invoice->term)->name }}</div></div>
                    <div><div class="meta-label">Due Date</div><div class="meta-value">{{ optional($invoice->due_date)->format('d M Y') ?? '—' }}</div></div>
                </div>

                <div class="invoice-table-wrap">
                    <table class="invoice-table">
                        <thead><tr><th scope="col">Description</th><th scope="col" class="amount-right">Amount</th></tr></thead>
                        <tbody>
                        @foreach($invoice->items as $item)
                            <tr><td>{{ $item->description }}</td><td class="amount-right">&#8358;{{ number_format($item->amount) }}</td></tr>
                        @endforeach
                        </tbody>
                        <tfoot><tr><td>Total</td><td class="amount-right">&#8358;{{ number_format($invoice->total_amount) }}</td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="finance-card">
            <div class="finance-card-head"><span class="finance-card-title">Payment History</span></div>
            <div class="finance-card-body">
                @forelse($invoice->transactions as $txn)
                    <div class="txn-row">
                        <div><div class="txn-name">{{ $txn->paid_by_name }}</div><div class="txn-meta">{{ optional($txn->paid_at)->format('d M Y, g:ia') }} · {{ ucfirst(str_replace('_',' ',$txn->gateway)) }} · {{ $txn->gateway_reference }}</div></div>
                        <div class="txn-amount">&#8358;{{ number_format($txn->amount_paid) }}</div>
                    </div>
                @empty
                    <div class="paid-state" style="color:var(--slate-light);font-weight:600">No payments recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div>
        <div class="finance-card">
            <div class="finance-card-head"><span class="finance-card-title">Balance Summary</span></div>
            <div class="finance-card-body">
                <div class="balance-display">
                    <div class="balance-row"><span>Total Billed</span><strong>&#8358;{{ number_format($invoice->total_amount) }}</strong></div>
                    <div class="balance-row"><span>Amount Paid</span><strong style="color:var(--emerald)">&#8358;{{ number_format($invoice->amount_paid) }}</strong></div>
                    <div class="balance-row balance"><span>Balance Due</span><span>&#8358;{{ number_format($invoice->balance) }}</span></div>
                </div>

                @if(!$invoice->isPaid())
                    <form method="POST" action="{{ route('fees.payment.record', $invoice) }}">
                        @csrf
                        <div class="form-group"><label class="form-label" for="amount_paid">Amount Paying (&#8358;) <span>*</span></label><input id="amount_paid" type="number" name="amount_paid" class="form-control" value="{{ $invoice->balance }}" min="1" max="{{ $invoice->balance }}" required></div>
                        <div class="form-group"><label class="form-label" for="paid_by_name">Paid By (Name) <span>*</span></label><input id="paid_by_name" type="text" name="paid_by_name" class="form-control" placeholder="Guardian name" required></div>
                        <div class="form-group"><label class="form-label" for="paid_by_phone">Phone Number</label><input id="paid_by_phone" type="tel" name="paid_by_phone" class="form-control" placeholder="08012345678" inputmode="tel"></div>
                        <div class="form-group"><label class="form-label" for="gateway">Payment Method <span>*</span></label><select id="gateway" name="gateway" class="form-control" required><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="paystack">Paystack</option><option value="monnify">Monnify</option></select></div>
                        <button type="submit" class="btn-primary-finance">Record Payment</button>
                    </form>
                @else
                    <div class="paid-state">✓ Invoice fully paid</div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($invPlan)
    <div class="finance-card plan-card">
        <div class="finance-card-head">
            <div><div class="finance-card-title">Payment Plan — {{ optional($invPlan->plan)->name }}</div><div class="plan-head-meta">{{ $invPlan->installments->count() }} installments</div></div>
        </div>
        @foreach($invPlan->installments->sortBy('installment_number') as $inst)
            <div class="installment-row">
                <div><div class="installment-title">Installment {{ $inst->installment_number }}</div><div class="installment-due">Due: {{ \Carbon\Carbon::parse($inst->due_date)->format('d M Y') }}</div></div>
                <div class="installment-money">
                    <div>₦{{ number_format($inst->amount_due) }} due · ₦{{ number_format($inst->amount_paid) }} paid</div>
                    <div class="installment-status {{ $inst->status==='paid' ? 'status-text-paid' : ($inst->status==='overdue' ? 'status-text-overdue' : 'status-text-pending') }}">{{ ucfirst($inst->status) }}</div>
                </div>
                @if($inst->status !== 'paid')
                    <div class="installment-action">
                        <button type="button" onclick="document.getElementById('pay-inst-{{ $inst->id }}').classList.toggle('hidden')" class="btn-success-finance">Pay</button>
                        <form id="pay-inst-{{ $inst->id }}" method="POST" action="{{ route('fees.plans.installment.pay',$inst) }}" class="installment-form hidden">
                            @csrf
                            <input type="number" name="amount" class="form-control" value="{{ $inst->balance }}" min="1" max="{{ $inst->balance }}" aria-label="Installment payment amount" required>
                            <select name="payment_method" class="form-control" aria-label="Installment payment method" required><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="pos">POS</option></select>
                            <button type="submit" class="btn-secondary-finance">Confirm</button>
                        </form>
                    </div>
                @else
                    <div class="installment-check" aria-label="Paid">✓</div>
                @endif
            </div>
        @endforeach
    </div>
@elseif($invoice->status !== 'paid')
    <div class="finance-card plan-card">
        <div class="finance-card-head"><div><div class="finance-card-title">Assign Payment Plan</div><div class="plan-head-meta">Split the outstanding invoice into scheduled installments.</div></div></div>
        <div class="finance-card-body">
            <form method="POST" action="{{ route('fees.plans.assign',$invoice) }}" class="assign-plan-form">
                @csrf
                <div class="form-group"><label class="form-label" for="plan_id">Payment Plan</label><select id="plan_id" name="plan_id" class="form-control" required>@foreach($availablePlans as $pl)<option value="{{ $pl->id }}">{{ $pl->name }}</option>@endforeach</select></div>
                <div class="form-group"><label class="form-label" for="start_date">Start Date</label><input id="start_date" type="date" name="start_date" value="{{ date('Y-m-d') }}" class="form-control" required></div>
                <button type="submit" class="btn-primary-finance">Assign Plan</button>
            </form>
        </div>
    </div>
@endif
@endsection
