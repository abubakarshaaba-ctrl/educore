@extends('layouts.app')
@section('title','Procurement')
@section('page-title','Procurement')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.two{display:grid;grid-template-columns:340px 1fr;gap:16px}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:10px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:8px 11px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;font-family:inherit}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:#ECFDF5;color:#059669;border:1px solid #A7F3D0;padding:5px 10px;font-size:11px}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;padding:5px 10px;font-size:11px}
.item-row{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;margin-bottom:8px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:top}
tr:last-child td{border:none}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:capitalize}
.b-draft{background:#F1F5F9;color:#475569}.b-pending_approval{background:#FFFBEB;color:#D97706}
.b-approved{background:#EFF6FF;color:#2563EB}.b-received{background:#ECFDF5;color:#059669}.b-cancelled{background:#FEF2F2;color:#DC2626}
.mini{font-size:11px;color:#94A3B8}
@media(max-width:1000px){.two{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="two">
    <div>
        <div class="card">
            <div class="ch">Add Vendor</div>
            <div class="cb">
                <form method="POST" action="{{ route('procurement.vendors.store') }}">
                    @csrf
                    <div class="fg"><label class="fl">Name *</label><input type="text" name="name" class="fc" required></div>
                    <div class="fg"><label class="fl">Contact Person</label><input type="text" name="contact_person" class="fc"></div>
                    <div class="fg"><label class="fl">Phone</label><input type="text" name="phone" class="fc"></div>
                    <div class="fg"><label class="fl">Email</label><input type="email" name="email" class="fc"></div>
                    <div class="fg"><label class="fl">Category</label><input type="text" name="category" class="fc" placeholder="e.g. Stationery, Furniture"></div>
                    <button type="submit" class="btn btn-p" style="width:100%;justify-content:center">Add Vendor</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="ch">Vendors ({{ $vendors->count() }})</div>
            <div style="max-height:340px;overflow-y:auto">
            @forelse($vendors as $v)
                <div style="padding:10px 18px;border-bottom:1px solid var(--border)">
                    <div style="font-weight:600;font-size:13px">{{ $v->name }}</div>
                    <div class="mini">{{ $v->contact_person }} {{ $v->phone }}</div>
                </div>
            @empty
                <div style="padding:20px;text-align:center;color:#94A3B8;font-size:13px">No vendors yet.</div>
            @endforelse
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="ch">New Purchase Order</div>
            <div class="cb">
                <form method="POST" action="{{ route('procurement.orders.store') }}" id="poForm">
                    @csrf
                    <div class="fr">
                        <div class="fg"><label class="fl">Vendor *</label>
                            <select name="vendor_id" class="fc" required>
                                <option value="">Select vendor...</option>
                                @foreach($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="fg"><label class="fl">Expected Delivery</label><input type="date" name="expected_delivery_date" class="fc"></div>
                    </div>
                    <label class="fl">Items *</label>
                    <div id="items">
                        <div class="item-row">
                            <input type="text" name="items[0][item_name]" class="fc" placeholder="Item name" required>
                            <input type="number" name="items[0][quantity]" class="fc" placeholder="Qty" min="1" value="1" required>
                            <input type="number" step="0.01" name="items[0][unit_price]" class="fc" placeholder="Unit price" required>
                            <span></span>
                        </div>
                    </div>
                    <button type="button" onclick="addItemRow()" class="btn" style="background:#F1F5F9;color:#475569;padding:6px 12px;font-size:12px;margin-bottom:12px">+ Add Item</button>
                    <div class="fg"><label class="fl">Notes</label><textarea name="notes" class="fc" rows="2"></textarea></div>
                    <button type="submit" class="btn btn-p">Create Purchase Order</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="ch">Purchase Orders</div>
            <div style="overflow-x:auto"><table>
                <thead><tr><th>PO Number</th><th>Vendor</th><th>Total</th><th>Status</th><th>Requested By</th><th></th></tr></thead>
                <tbody>
                @forelse($orders as $o)
                <tr>
                    <td style="font-family:monospace;font-size:12px">{{ $o->po_number }}</td>
                    <td>{{ optional($o->vendor)->name }}</td>
                    <td>₦{{ number_format($o->total_amount, 2) }}</td>
                    <td><span class="badge b-{{ $o->status }}">{{ ucwords(str_replace('_',' ',$o->status)) }}</span></td>
                    <td>{{ optional($o->requester)->name ?? '—' }}</td>
                    <td>
                        <div style="display:flex;gap:5px;flex-wrap:wrap">
                        @if($o->status === 'pending_approval')
                        <form method="POST" action="{{ route('procurement.orders.approve', $o) }}">@csrf @method('PATCH')<button class="btn btn-g">Approve</button></form>
                        @endif
                        @if($o->status === 'approved')
                        <form method="POST" action="{{ route('procurement.orders.received', $o) }}">@csrf @method('PATCH')<button class="btn btn-g">Mark Received</button></form>
                        @endif
                        @if(!in_array($o->status, ['received','cancelled']))
                        <form method="POST" action="{{ route('procurement.orders.cancel', $o) }}" onsubmit="return confirm('Cancel this PO?')">@csrf @method('PATCH')<button class="btn btn-danger">Cancel</button></form>
                        @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:30px;color:#94A3B8">No purchase orders yet.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div style="padding:14px">{{ $orders->links() }}</div>
        </div>
    </div>
</div>

<script>
let itemIndex = 1;
function addItemRow() {
    const div = document.createElement('div');
    div.className = 'item-row';
    div.innerHTML = `
        <input type="text" name="items[${itemIndex}][item_name]" class="fc" placeholder="Item name" required>
        <input type="number" name="items[${itemIndex}][quantity]" class="fc" placeholder="Qty" min="1" value="1" required>
        <input type="number" step="0.01" name="items[${itemIndex}][unit_price]" class="fc" placeholder="Unit price" required>
        <button type="button" onclick="this.parentElement.remove()" style="border:none;background:none;color:#DC2626;cursor:pointer;font-size:16px">✕</button>
    `;
    document.getElementById('items').appendChild(div);
    itemIndex++;
}
</script>
@endsection
