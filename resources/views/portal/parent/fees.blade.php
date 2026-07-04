@extends('layouts.portal')
@section('title','Fees & Payments')
@section('content')

@if($students->count() > 1)
<div class="child-tabs">
    @foreach($students as $s)
    <a href="?student_id={{ $s->id }}" class="child-tab {{ optional($student)->id==$s->id ? 'active':'' }}">👦 {{ $s->first_name }}</a>
    @endforeach
</div>
@endif

<h2 style="font-size:17px;font-weight:800;margin-bottom:18px">💳 Fees & Payments — {{ optional($student)->full_name }}</h2>

@if($errors->any())
<div class="alert-e">{{ $errors->first() }}</div>
@endif

@if($totals)
<div class="kpi-row">
    <div class="kpi"><div class="kv" style="color:#2563EB">₦{{ number_format($totals->billed??0) }}</div><div class="kl">Total Billed</div></div>
    <div class="kpi"><div class="kv" style="color:#059669">₦{{ number_format($totals->paid??0) }}</div><div class="kl">Amount Paid</div></div>
    <div class="kpi"><div class="kv" style="color:{{ ($totals->outstanding??0)>0?'#DC2626':'#059669' }}">₦{{ number_format($totals->outstanding??0) }}</div><div class="kl">Outstanding</div></div>
</div>
@endif

{{-- Invoice list --}}
<div class="card">
    <div class="ch">Invoice History</div>
    <div class="tbl"><table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Description</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @forelse($invoices as $inv)
        @php $bal = $inv->total_amount - $inv->amount_paid; @endphp
        <tr>
            <td style="font-weight:700;font-family:monospace">
                {{ $inv->invoice_number }}
                <div style="font-size:10px;color:var(--muted);font-family:sans-serif">{{ $inv->created_at->format('d M Y') }}</div>
            </td>
            <td style="font-size:12px">{{ $inv->description ?? '—' }}</td>
            <td style="font-weight:600">₦{{ number_format($inv->total_amount) }}</td>
            <td style="color:#059669;font-weight:600">₦{{ number_format($inv->amount_paid) }}</td>
            <td style="color:{{ $bal>0?'#DC2626':'#059669' }};font-weight:700">₦{{ number_format($bal) }}</td>
            <td><span class="badge {{ $inv->status==='paid'?'b-g':($inv->status==='partially_paid'?'b-a':'b-r') }}">
                {{ ucfirst(str_replace('_',' ',$inv->status)) }}
            </span></td>
            <td>
                @if($bal > 0 && $gatewayActive)
                <a href="{{ route('parent.fees.pay', $inv) }}"
                   style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;background:#2563EB;color:white;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap">
                    💳 Pay Now
                </a>
                @elseif($bal > 0)
                <span style="font-size:11px;color:#94A3B8">Contact school</span>
                @else
                <span style="font-size:11px;color:#059669;font-weight:600">✓ Settled</span>
                @endif
            </td>
        </tr>

        {{-- Payment plan installments for this invoice --}}
        @if(isset($installments[$inv->id]) && $installments[$inv->id]->isNotEmpty())
        <tr style="background:#FAFAFA">
            <td colspan="7" style="padding:0">
                <div style="padding:10px 14px 6px;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;border-top:1px dashed var(--border)">
                    Payment Plan Installments
                </div>
                <table style="margin:0;border:none">
                    <thead>
                        <tr style="background:#F1F5F9">
                            <th style="padding:6px 14px">Installment</th>
                            <th style="padding:6px 14px">Due Date</th>
                            <th style="padding:6px 14px">Amount Due</th>
                            <th style="padding:6px 14px">Amount Paid</th>
                            <th style="padding:6px 14px">Status</th>
                            <th style="padding:6px 14px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($installments[$inv->id] as $inst)
                    @php $instBal = $inst->amount_due - $inst->amount_paid; @endphp
                    <tr>
                        <td style="padding:7px 14px;font-weight:600">No. {{ $inst->installment_number }}</td>
                        <td style="padding:7px 14px;font-size:12px">
                            {{ $inst->due_date ? \Carbon\Carbon::parse($inst->due_date)->format('d M Y') : '—' }}
                            @if($inst->due_date && \Carbon\Carbon::parse($inst->due_date)->isPast() && $inst->status !== 'paid')
                            <span style="color:#DC2626;font-size:10px;font-weight:700"> OVERDUE</span>
                            @endif
                        </td>
                        <td style="padding:7px 14px">₦{{ number_format($inst->amount_due) }}</td>
                        <td style="padding:7px 14px;color:#059669">₦{{ number_format($inst->amount_paid) }}</td>
                        <td style="padding:7px 14px">
                            <span class="badge {{ $inst->status==='paid'?'b-g':($inst->status==='partial'?'b-a':'b-r') }}">
                                {{ ucfirst($inst->status ?? 'unpaid') }}
                            </span>
                        </td>
                        <td style="padding:7px 14px">
                            @if($instBal > 0 && $gatewayActive)
                            <a href="{{ route('parent.fees.pay', $inv) }}"
                               style="display:inline-flex;align-items:center;gap:3px;padding:4px 10px;background:#2563EB;color:white;border-radius:6px;font-size:10px;font-weight:700;text-decoration:none">
                                💳 Pay
                            </a>
                            @elseif($inst->status === 'paid')
                            <span style="font-size:11px;color:#059669">✓</span>
                            @else
                            <span style="font-size:11px;color:#94A3B8">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
        @endif

        @empty
        <tr><td colspan="7" class="empty" style="padding:40px">No invoices found for this student.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $invoices->links() }}</div>
</div>

@if(!$gatewayActive)
<div style="padding:14px 16px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;font-size:13px;color:#92400E;margin-top:8px">
    ⚠ Online payment is not yet enabled for this school. Please visit the school office to make payments.
</div>
@endif
@endsection
