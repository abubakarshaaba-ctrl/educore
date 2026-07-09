@extends('layouts.app')
@section('title','Asset & Inventory')
@section('page-title','Asset & Inventory Management')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.fr{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;font-family:inherit}
.btn-p{background:var(--indigo);color:white}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;padding:5px 10px;font-size:11px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border)}
tr:last-child td{border:none}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:capitalize}
.b-in_use{background:#ECFDF5;color:#059669}.b-in_storage{background:#F1F5F9;color:#475569}
.b-under_repair{background:#FFFBEB;color:#D97706}.b-disposed{background:#FEF2F2;color:#DC2626}
select.inline{padding:4px 6px;font-size:11px;border:1px solid var(--border);border-radius:6px}
@media(max-width:900px){.fr{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="card">
    <div class="ch">Add Asset</div>
    <div class="cb">
        <form method="POST" action="{{ route('inventory.store') }}">
            @csrf
            <div class="fr">
                <div class="fg"><label class="fl">Name *</label><input type="text" name="name" class="fc" required></div>
                <div class="fg"><label class="fl">Category</label><input type="text" name="category" class="fc" placeholder="e.g. Furniture, IT Equipment"></div>
                <div class="fg"><label class="fl">Serial Number</label><input type="text" name="serial_number" class="fc"></div>
                <div class="fg"><label class="fl">Location</label><input type="text" name="location" class="fc" placeholder="e.g. Staff Room, Lab 2"></div>
                <div class="fg"><label class="fl">Assigned To</label>
                    <select name="assigned_to" class="fc">
                        <option value="">— Unassigned —</option>
                        @foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="fg"><label class="fl">Purchase Date</label><input type="date" name="purchase_date" class="fc"></div>
                <div class="fg"><label class="fl">Purchase Cost</label><input type="number" step="0.01" name="purchase_cost" class="fc"></div>
                <div class="fg"><label class="fl">Condition *</label>
                    <select name="condition" class="fc" required>
                        <option value="new">New</option><option value="good" selected>Good</option>
                        <option value="fair">Fair</option><option value="poor">Poor</option><option value="damaged">Damaged</option>
                    </select>
                </div>
                <div class="fg"><label class="fl">Status *</label>
                    <select name="status" class="fc" required>
                        <option value="in_use" selected>In Use</option><option value="in_storage">In Storage</option>
                        <option value="under_repair">Under Repair</option><option value="disposed">Disposed</option>
                    </select>
                </div>
            </div>
            <div class="fg"><label class="fl">Notes</label><textarea name="notes" class="fc" rows="2"></textarea></div>
            <button type="submit" class="btn btn-p">Add Asset</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="ch">Inventory ({{ $assets->total() }})</div>
    <div style="overflow-x:auto"><table>
        <thead><tr><th>Name</th><th>Category</th><th>Location</th><th>Assigned To</th><th>Condition</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($assets as $a)
        <tr>
            <td>
                <div style="font-weight:600">{{ $a->name }}</div>
                @if($a->serial_number)<div style="font-size:11px;color:#94A3B8">S/N: {{ $a->serial_number }}</div>@endif
            </td>
            <td>{{ $a->category ?? '—' }}</td>
            <td>{{ $a->location ?? '—' }}</td>
            <td>{{ optional($a->assignedTo)->name ?? '—' }}</td>
            <td>{{ ucfirst($a->condition) }}</td>
            <td><span class="badge b-{{ $a->status }}">{{ ucwords(str_replace('_',' ',$a->status)) }}</span></td>
            <td>
                <form method="POST" action="{{ route('inventory.destroy', $a) }}" onsubmit="return confirm('Remove this asset?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger">Remove</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:30px;color:#94A3B8">No assets recorded yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $assets->links() }}</div>
</div>
@endsection
