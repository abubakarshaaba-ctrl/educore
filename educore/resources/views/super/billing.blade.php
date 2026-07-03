@extends('layouts.super')
@section('title','Billing & Invoices')
@section('page-title','Billing & Invoices')

@push('styles')
<style>
.kpi{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.kc{background:white;border:1px solid var(--border);border-radius:12px;padding:16px 18px}
.kv{font-size:22px;font-weight:800}.kl{font-size:11px;color:#64748B;text-transform:uppercase;letter-spacing:.06em;margin-top:4px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:12px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:8px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border);color:#0F172A}
tr:hover td{background:#F8FAFC}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-paid{background:#ECFDF5;color:#059669}.b-pending{background:#FFFBEB;color:#D97706}.b-overdue{background:#FEF2F2;color:#DC2626}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:#2563EB;color:white}.btn-g{background:#059669;color:white}.btn-ghost{background:#F1F5F9;color:#475569;border:1px solid #E2E8F0}
.fg{display:flex;flex-direction:column;gap:4px;margin-bottom:12px}
.fl{font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em}
.fc{padding:8px 12px;font-size:13px;font-family:inherit;border:1.5px solid #E2E8F0;border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:#2563EB}
.two{display:grid;grid-template-columns:1fr 360px;gap:16px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
.filter-bar{background:white;border:1px solid #E2E8F0;border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap}

@media (max-width: 1024px) {
    .two-col { grid-template-columns: 1fr !important; }
    .stats-row, .stat-row { grid-template-columns: repeat(2, 1fr) !important; }
    .kpi { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 640px) {
    .two, .fr { grid-template-columns: 1fr !important; }
}
@media (max-width: 480px) {
    .fr3 { grid-template-columns: 1fr !important; }
}
</style>
@endpush

@section('content')
@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif

{{-- KPIs --}}
<div class="kpi">
    <div class="kc"><div class="kv" style="color:#2563EB">₦{{ number_format($stats['total_invoiced']) }}</div><div class="kl">Total Invoiced</div></div>
    <div class="kc"><div class="kv" style="color:#059669">₦{{ number_format($stats['total_paid']) }}</div><div class="kl">Total Collected</div></div>
    <div class="kc"><div class="kv" style="color:#DC2626">₦{{ number_format($stats['total_overdue']) }}</div><div class="kl">Overdue Amount</div></div>
    <div class="kc"><div class="kv" style="color:#D97706">{{ number_format($stats['pending_count']) }}</div><div class="kl">Pending Invoices</div></div>
</div>

<div class="two">
    {{-- Invoice list --}}
    <div>
        <div class="filter-bar">
            <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                <div class="fg" style="margin:0">
                    <label class="fl">Status</label>
                    <select name="status" class="fc" style="min-width:130px" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                        <option value="paid" {{ request('status')=='paid'?'selected':'' }}>Paid</option>
                        <option value="overdue" {{ request('status')=='overdue'?'selected':'' }}>Overdue</option>
                    </select>
                </div>
                <div class="fg" style="margin:0">
                    <label class="fl">School</label>
                    <select name="tenant_id" class="fc" style="min-width:180px" onchange="this.form.submit()">
                        <option value="">All Schools</option>
                        @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id')==$t->id?'selected':'' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
        <div class="card">
            <div class="ch">📋 Platform Invoices<span style="font-size:12px;color:#64748B">{{ $invoices->total() }} records</span></div>
            <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Invoice #</th><th>School</th><th>Plan</th><th>Amount</th><th>Due</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($invoices as $inv)
                <tr>
                    <td style="font-weight:700;font-family:monospace">{{ $inv->invoice_number }}</td>
                    <td style="font-weight:600">{{ $inv->school_name }}</td>
                    <td>{{ $inv->plan_name }} <span style="font-size:11px;color:#94A3B8">({{ $inv->billing_cycle }})</span></td>
                    <td style="font-weight:700">₦{{ number_format($inv->amount) }}</td>
                    <td style="font-size:12px;color:{{ \Carbon\Carbon::parse($inv->due_date)->isPast() && $inv->status!='paid' ? '#DC2626':'#64748B' }}">
                        {{ \Carbon\Carbon::parse($inv->due_date)->format('d M Y') }}
                    </td>
                    <td><span class="badge b-{{ $inv->status }}">{{ ucfirst($inv->status) }}</span></td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('super.billing.pdf',$inv->id) }}" target="_blank" class="btn btn-ghost">PDF</a>
                            @if($inv->status !== 'paid')
                            <a href="{{ route('super.billing.pay', $inv->id) }}"
                               style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;background:#2563EB;color:white;border-radius:7px;font-size:12px;font-weight:700;text-decoration:none">
                                💳 Pay Online
                            </a>
                            <button class="btn btn-g" onclick="markPaid({{ $inv->id }})">Mark Paid</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#94A3B8">No invoices found.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
            <div style="padding:14px">{{ $invoices->links() }}</div>
        </div>
    </div>

    {{-- Generate invoice form --}}
    <div>
        <div class="card">
            <div class="ch">➕ Generate Invoice</div>
            <div style="padding:18px">
            <form method="POST" action="{{ route('super.billing.generate') }}">
                @csrf
                <div class="fg"><label class="fl">School *</label>
                    <select name="tenant_id" class="fc" required>
                        <option value="">Select school...</option>
                        @foreach($tenants as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                </div>
                <div class="fg"><label class="fl">Plan *</label>
                    <select name="plan_id" class="fc" id="planSel" required onchange="updateAmount()">
                        <option value="">Select plan...</option>
                        @foreach(\Illuminate\Support\Facades\DB::table('subscription_plans')->where('is_active',1)->orderBy('sort_order')->get() as $p)
                        <option value="{{ $p->id }}" data-monthly="{{ $p->monthly_price }}" data-annual="{{ $p->annual_price }}">
                            {{ $p->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="fg"><label class="fl">Billing Cycle *</label>
                    <select name="billing_cycle" class="fc" id="cycleSel" required onchange="updateAmount()">
                        <option value="monthly">Monthly</option>
                        <option value="annual">Annual</option>
                    </select>
                </div>
                <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px;margin-bottom:12px;text-align:center">
                    <div style="font-size:11px;color:#64748B;text-transform:uppercase;letter-spacing:.06em">Invoice Amount</div>
                    <div id="amtDisplay" style="font-size:22px;font-weight:800;color:#2563EB">₦0</div>
                </div>
                <div class="fg"><label class="fl">Due Date *</label>
                    <input type="date" name="due_date" class="fc" required value="{{ now()->addDays(14)->format('Y-m-d') }}">
                </div>
                <div class="fg"><label class="fl">Notes</label>
                    <textarea name="notes" class="fc" rows="2" placeholder="Optional note..."></textarea>
                </div>
                <button type="submit" class="btn btn-p" style="width:100%;justify-content:center">🧾 Generate Invoice</button>
            </form>
            </div>
        </div>
    </div>
</div>

{{-- Mark Paid Modal --}}
@foreach($invoices as $inv)
@if($inv->status !== 'paid')
<form method="POST" action="{{ route('super.billing.paid',$inv->id) }}" id="paid_{{ $inv->id }}" style="display:none">
    @csrf
    <input type="hidden" name="payment_method" id="pm_{{ $inv->id }}" value="bank_transfer">
    <input type="hidden" name="payment_ref" id="pr_{{ $inv->id }}" value="">
</form>
@endif
@endforeach
@endsection

@push('scripts')
<script>
const plans = {!! json_encode(\Illuminate\Support\Facades\DB::table('subscription_plans')->where('is_active',1)->get()->keyBy('id')) !!};
function updateAmount() {
    const pId = document.getElementById('planSel').value;
    const cycle = document.getElementById('cycleSel').value;
    const plan = plans[pId];
    if (!plan) { document.getElementById('amtDisplay').textContent = '₦0'; return; }
    const amt = cycle === 'annual' ? plan.annual_price : plan.monthly_price;
    document.getElementById('amtDisplay').textContent = '₦'+Number(amt).toLocaleString();
}
function markPaid(id) {
    const method = prompt('Payment method (bank_transfer / card / cash / pos):','bank_transfer');
    if (!method) return;
    const ref = prompt('Payment reference (optional):', '');
    document.getElementById('pm_'+id).value = method;
    document.getElementById('pr_'+id).value = ref || '';
    document.getElementById('paid_'+id).submit();
}
</script>
@endpush
