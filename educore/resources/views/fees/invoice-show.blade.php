@extends('layouts.app')
@section('title', 'Invoice ' . $invoice->invoice_number)
@section('page-title', 'Invoice Detail')

@push('styles')
<style>
    .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--slate-light); margin-bottom: 20px; }
    .breadcrumb a { color: var(--indigo); text-decoration: none; font-weight: 500; }
    .breadcrumb svg { width: 14px; height: 14px; }

    .invoice-grid { display: grid; grid-template-columns: 1fr 360px; gap: 20px; align-items: start; }

    .card { background: white; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 16px; }
    .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .card-title { font-size: 14px; font-weight: 600; color: var(--midnight); }
    .card-body { padding: 20px; }

    .invoice-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    .meta-block { }
    .meta-label { font-size: 11px; font-weight: 600; color: var(--slate-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
    .meta-value { font-size: 14px; font-weight: 600; color: var(--midnight); }
    .meta-sub { font-size: 12px; color: var(--slate); margin-top: 2px; }

    table { width: 100%; border-collapse: collapse; }
    thead th { font-size: 11px; font-weight: 600; color: var(--slate-light); text-transform: uppercase; letter-spacing: 0.05em; padding: 10px 16px; text-align: left; background: #F8FAFC; border-bottom: 1px solid var(--border); }
    tbody td { padding: 13px 16px; border-bottom: 1px solid var(--border); font-size: 13px; color: var(--midnight); }
    tbody tr:last-child td { border-bottom: none; }
    tfoot td { padding: 13px 16px; font-weight: 700; font-size: 14px; }

    .total-row { background: #F8FAFC; border-top: 2px solid var(--border); }
    .amount-right { text-align: right; }

    .badge { display: inline-flex; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; }
    .badge-success { background: #ECFDF5; color: var(--emerald); }
    .badge-warning { background: #FFFBEB; color: var(--amber); }
    .badge-error   { background: #FEF2F2; color: var(--crimson); }

    .balance-display { background: #F8FAFC; border: 1px solid var(--border); border-radius: 10px; padding: 16px; margin-bottom: 20px; }
    .balance-row { display: flex; justify-content: space-between; font-size: 13px; padding: 5px 0; }
    .balance-row.total { font-weight: 700; font-size: 15px; border-top: 1px solid var(--border); margin-top: 8px; padding-top: 10px; }
    .balance-row.balance { color: var(--crimson); font-weight: 700; }

    .form-group { margin-bottom: 14px; }
    .form-label { display: block; font-size: 11px; font-weight: 600; color: var(--slate); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 5px; }
    .form-label span { color: var(--crimson); }
    .form-control { width: 100%; padding: 9px 12px; font-size: 13px; font-family: inherit; border: 1px solid var(--border); border-radius: 8px; background: #F8FAFC; outline: none; transition: border-color 200ms; }
    .form-control:focus { border-color: var(--indigo); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); background: white; }

    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; font-size: 13px; font-weight: 600; font-family: inherit; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; transition: background 150ms; }
    .btn-primary { background: var(--indigo); color: white; width: 100%; justify-content: center; }
    .btn-primary:hover { background: #1D4ED8; }
    .btn-success { background: var(--emerald); color: white; }

    .alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: var(--emerald); margin-bottom: 16px; }

    .txn-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
    .txn-row:last-child { border-bottom: none; }
    .txn-amount { font-weight: 700; color: var(--emerald); }
    .txn-meta { font-size: 11px; color: var(--slate-light); margin-top: 2px; }

    @media(max-width:1024px) { .invoice-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('fees.invoices') }}">Invoices</a>
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    {{ $invoice->invoice_number }}
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="invoice-grid">

    {{-- LEFT: Invoice detail --}}
    <div>
        <div class="card">
            <div class="card-header">
                <span class="card-title">{{ $invoice->invoice_number }}</span>
                @if($invoice->status === 'paid')
                    <span class="badge badge-success">Paid</span>
                @elseif($invoice->status === 'partially_paid')
                    <span class="badge badge-warning">Partially Paid</span>
                @else
                    <span class="badge badge-error">Unpaid</span>
                @endif
            </div>
            <div class="card-body">
                <div class="invoice-meta">
                    <div class="meta-block">
                        <div class="meta-label">Student</div>
                        <div class="meta-value">{{ optional($invoice->student)->full_name }}</div>
                        <div class="meta-sub">{{ optional($invoice->student)->admission_number }}</div>
                    </div>
                    <div class="meta-block">
                        <div class="meta-label">Class</div>
                        <div class="meta-value">{{ optional(optional($invoice->student)->currentClassArm)->classLevel->name }} {{ optional(optional($invoice->student)->currentClassArm)->name }}</div>
                    </div>
                    <div class="meta-block">
                        <div class="meta-label">Term</div>
                        <div class="meta-value">{{ optional($invoice->term)->name }}</div>
                    </div>
                    <div class="meta-block">
                        <div class="meta-label">Due Date</div>
                        <div class="meta-value">{{ optional($invoice->due_date)->format('d M Y') ?? '—' }}</div>
                    </div>
                </div>

                {{-- Line items --}}
                <div class="tbl"><table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="amount-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td class="amount-right">&#8358;{{ number_format($item->amount) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td>Total</td>
                            <td class="amount-right">&#8358;{{ number_format($invoice->total_amount) }}</td>
                        </tr>
                    </tfoot>
                </table></div>
            </div>
        </div>

        {{-- Payment history --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Payment History</span></div>
            <div class="card-body">
                @forelse($invoice->transactions as $txn)
                <div class="txn-row">
                    <div>
                        <div style="font-weight:600">{{ $txn->paid_by_name }}</div>
                        <div class="txn-meta">{{ optional($txn->paid_at)->format('d M Y, g:ia') }} · {{ ucfirst(str_replace('_',' ',$txn->gateway)) }} · {{ $txn->gateway_reference }}</div>
                    </div>
                    <div class="txn-amount">&#8358;{{ number_format($txn->amount_paid) }}</div>
                </div>
                @empty
                <p style="font-size:13px;color:var(--slate-light);text-align:center;padding:20px 0">No payments recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- RIGHT: Balance + Record payment --}}
    <div>
        <div class="card">
            <div class="card-header"><span class="card-title">Balance Summary</span></div>
            <div class="card-body">
                <div class="balance-display">
                    <div class="balance-row"><span>Total Billed</span><span>&#8358;{{ number_format($invoice->total_amount) }}</span></div>
                    <div class="balance-row"><span>Amount Paid</span><span style="color:var(--emerald)">&#8358;{{ number_format($invoice->amount_paid) }}</span></div>
                    <div class="balance-row balance"><span>Balance Due</span><span>&#8358;{{ number_format($invoice->balance) }}</span></div>
                </div>

                @if(!$invoice->isPaid())
                <form method="POST" action="{{ route('fees.payment.record', $invoice) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Amount Paying (&#8358;) <span>*</span></label>
                        <input type="number" name="amount_paid" class="form-control" value="{{ $invoice->balance }}" min="1" max="{{ $invoice->balance }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Paid By (Name) <span>*</span></label>
                        <input type="text" name="paid_by_name" class="form-control" placeholder="Guardian name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="paid_by_phone" class="form-control" placeholder="08012345678">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Method <span>*</span></label>
                        <select name="gateway" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="paystack">Paystack</option>
                            <option value="monnify">Monnify</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </form>
                @else
                <div style="text-align:center;padding:20px;color:var(--emerald);font-weight:600;font-size:14px">
                    ✅ Invoice fully paid
                </div>
                @endif
            </div>
        </div>
    </div>
</div>


    {{-- PAYMENT PLAN SECTION --}}
    {{-- $invPlan and $availablePlans passed from FeeController::showInvoice() --}}
    @if($invPlan)
    <div style="background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-top:16px">
        <div style="padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between">
            &#128197; Payment Plan — {{ optional($invPlan->plan)->name }}
            <span style="font-size:11px;font-weight:400;color:var(--slate)">{{ $invPlan->installments->count() }} installments</span>
        </div>
        @foreach($invPlan->installments->sortBy('installment_number') as $inst)
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--midnight)">Installment {{ $inst->installment_number }}</div>
                <div style="font-size:11px;color:var(--slate-light);margin-top:2px">Due: {{ \Carbon\Carbon::parse($inst->due_date)->format('d M Y') }}</div>
            </div>
            <div style="text-align:right">
                <div style="font-size:13px">₦{{ number_format($inst->amount_due) }} due &nbsp;·&nbsp; ₦{{ number_format($inst->amount_paid) }} paid</div>
                <div style="font-size:11px;color:{{ $inst->status==='paid'?'var(--emerald)':($inst->status==='overdue'?'var(--crimson)':'var(--amber)') }};font-weight:600;margin-top:2px">{{ ucfirst($inst->status) }}</div>
            </div>
            @if($inst->status !== 'paid')
            <div>
                <button onclick="document.getElementById('pay-inst-{{ $inst->id }}').classList.toggle('hidden')" class="btn" style="background:var(--emerald);color:white;font-size:11px;padding:5px 10px;font-family:inherit;border:none;border-radius:6px;cursor:pointer">Pay</button>
                <form id="pay-inst-{{ $inst->id }}" method="POST" action="{{ route('fees.plans.installment.pay',$inst) }}" class="hidden" style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap">
                    @csrf
                    <input type="number" name="amount" class="fc" value="{{ $inst->balance }}" min="1" max="{{ $inst->balance }}" style="width:120px;padding:6px 10px;font-size:12px;border:1px solid var(--border);border-radius:6px">
                    <select name="payment_method" style="padding:6px 10px;font-size:12px;border:1px solid var(--border);border-radius:6px;font-family:inherit">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="pos">POS</option>
                    </select>
                    <button type="submit" style="padding:6px 12px;font-size:12px;font-family:inherit;background:var(--indigo);color:white;border:none;border-radius:6px;cursor:pointer">Confirm</button>
                </form>
            </div>
            @else
            <div style="color:var(--emerald);font-size:18px">&#10003;</div>
            @endif
        </div>
        @endforeach
    </div>
    @else
    @if($invoice->status !== 'paid')
    <div style="background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-top:16px">
        <div style="padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)">
            &#128197; Assign Payment Plan
        </div>
        <div style="padding:16px">
            <form method="POST" action="{{ route('fees.plans.assign',$invoice) }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            @csrf
            <div>
                <div style="font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">Payment Plan</div>
                <select name="plan_id" style="padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;min-width:200px">
                    @foreach($availablePlans as $pl)
                    <option value="{{ $pl->id }}">{{ $pl->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div style="font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">Start Date</div>
                <input type="date" name="start_date" value="{{ date('Y-m-d') }}" style="padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC">
            </div>
            <button type="submit" style="padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;background:var(--indigo);color:white;border:none;border-radius:8px;cursor:pointer">Assign Plan</button>
            </form>
        </div>
    </div>
    @endif
    @endif

@endsection
