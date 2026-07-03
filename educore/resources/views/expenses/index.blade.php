@extends('layouts.app')
@section('title','Expenses')
@section('page-title','School Expenses')
@push('styles')
<style>
.page-grid{display:grid;grid-template-columns:1fr 360px;gap:16px}
.sg{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
.sc{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px;text-align:center}
.sv{font-size:20px;font-weight:800;letter-spacing:-0.02em}
.sl{font-size:10px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:2px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:16px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms;width:100%;justify-content:center}
.btn-p{background:var(--indigo);color:white}
.btn-sm{padding:4px 10px;font-size:11px;width:auto}
.btn-r{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}
.cat-badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 7px;border-radius:20px;background:#F1F5F9;color:var(--slate);text-transform:capitalize}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
@media(max-width:1024px){.sg{grid-template-columns:repeat(2,1fr)}.page-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<div class="sg">
    <div class="sc"><div class="sv" style="color:var(--indigo)">₦{{ number_format($grandTotal) }}</div><div class="sl">Total Expenses</div></div>
    @foreach(array_slice($categories,0,3) as $cat)
    <div class="sc"><div class="sv">₦{{ number_format($totals[$cat]??0) }}</div><div class="sl">{{ ucfirst($cat) }}</div></div>
    @endforeach
</div>
<div class="page-grid">
  <div>
    <div class="card">
      <div class="ch">Expense Records</div>
      <div class="tbl"><table>
        <thead><tr><th>Date</th><th>Title</th><th>Category</th><th>Amount</th><th></th></tr></thead>
        <tbody>
        @forelse($expenses as $exp)
        <tr>
            <td style="font-size:11px">{{ \Carbon\Carbon::parse($exp->expense_date)->format('d M Y') }}</td>
            <td><strong>{{ $exp->title }}</strong><br><span style="font-size:11px;color:var(--slate-light)">{{ $exp->description }}</span></td>
            <td><span class="cat-badge">{{ ucfirst($exp->category) }}</span></td>
            <td><strong style="color:var(--crimson)">₦{{ number_format($exp->amount) }}</strong></td>
            <td>
                <form method="POST" action="{{ route('expenses.destroy',$exp) }}" style="display:inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-r btn-sm" onclick="return confirm('Delete this expense?')">Delete</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--slate-light)">No expenses recorded yet</td></tr>
        @endforelse
        </tbody>
      </table></div>
      {{ $expenses->links() }}
    </div>
  </div>
  <div>
    <div class="card">
      <div class="ch">Record Expense</div>
      <div class="cb">
        <form method="POST" action="{{ route('expenses.store') }}">
          @csrf
          <div class="fg"><label class="fl">Title *</label><input type="text" name="title" class="fc" required placeholder="e.g. Generator fuel"></div>
          <div class="fg"><label class="fl">Category *</label>
            <select name="category" class="fc" required>
                @foreach($categories as $cat)<option value="{{ $cat }}">{{ ucfirst($cat) }}</option>@endforeach
            </select>
          </div>
          <div class="fg"><label class="fl">Amount (₦) *</label><input type="number" name="amount" class="fc" step="0.01" min="0" required></div>
          <div class="fg"><label class="fl">Date *</label><input type="date" name="expense_date" class="fc" value="{{ date('Y-m-d') }}" required></div>
          <div class="fg"><label class="fl">Payment Method</label>
            <select name="payment_method" class="fc">
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
            </select>
          </div>
          <div class="fg"><label class="fl">Description</label><textarea name="description" class="fc" rows="2" placeholder="Optional details"></textarea></div>
          <button type="submit" class="btn btn-p">Record Expense</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection