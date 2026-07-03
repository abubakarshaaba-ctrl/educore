@extends('layouts.app')
@section('title','Fee Reminders')
@section('page-title','Fee Reminder System')
@push('styles')
<style>
.pg{display:grid;grid-template-columns:1fr 380px;gap:20px}
.sg{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px}
@media(max-width:768px){.sg{grid-template-columns:1fr 1fr}}
@media(max-width:480px){.sg{grid-template-columns:1fr}}
.sc{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px;text-align:center}
.sv{font-size:22px;font-weight:800;letter-spacing:-0.02em}.sl{font-size:10px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:2px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:16px}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#F8FAFC}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms;width:100%;justify-content:center;margin-bottom:8px}
.btn-p{background:var(--indigo);color:white}.btn-a{background:var(--amber);color:white}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.chk{width:14px;height:14px;cursor:pointer;accent-color:var(--indigo)}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
@media(max-width:1024px){.pg{grid-template-columns:1fr}.sg{grid-template-columns:1fr 1fr 1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<div class="sg">
    <div class="sc"><div class="sv" style="color:var(--crimson)">₦{{ number_format($stats['total_outstanding']) }}</div><div class="sl">Total Outstanding</div></div>
    <div class="sc"><div class="sv" style="color:var(--amber)">{{ $stats['invoices_unpaid'] }}</div><div class="sl">Unpaid Invoices</div></div>
    <div class="sc"><div class="sv" style="color:var(--emerald)">{{ $stats['reminders_sent'] }}</div><div class="sl">Sent Today</div></div>
</div>
<div class="pg">
  <div>
    <form method="POST" action="{{ route('fees.reminders.send') }}" id="reminder-form">
    @csrf
    <div class="card">
      <div class="ch" style="display:flex;align-items:center;justify-content:space-between">
        Outstanding Invoices
        <label style="font-size:12px;font-weight:500;display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="checkbox" id="select-all" class="chk"> Select All
        </label>
      </div>
      <div class="tbl"><table>
        <thead><tr><th style="width:36px"></th><th>Student</th><th>Guardian</th><th>Billed</th><th>Paid</th><th>Balance</th></tr></thead>
        <tbody>
        @forelse($outstanding as $inv)
        @php $bal = $inv->total_amount - $inv->amount_paid; @endphp
        <tr>
            <td><input type="checkbox" name="invoice_ids[]" value="{{ $inv->id }}" class="chk inv-chk"></td>
            <td><strong>{{ optional($inv->student)->full_name }}</strong></td>
            <td style="font-size:11px">
                @php $guardian = optional($inv->student)->guardians?->first(); @endphp
                {{ optional($guardian)->name }}<br>{{ optional($guardian)->phone }}
            </td>
            <td>₦{{ number_format($inv->total_amount) }}</td>
            <td style="color:var(--emerald)">₦{{ number_format($inv->amount_paid) }}</td>
            <td style="color:var(--crimson);font-weight:700">₦{{ number_format($bal) }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--slate-light)">All fees are up to date!</td></tr>
        @endforelse
        </tbody>
      </table></div>
      {{ $outstanding->links() }}
    </div>
    <input type="hidden" name="channel" id="channel-input" value="sms">
    <input type="hidden" name="message" value="">
    </form>
  </div>
  <div>
    <div class="card">
      <div class="ch">Send Reminders</div>
      <div class="cb">
        <div class="fg"><label class="fl">Channel</label>
          <select class="fc" id="channel-select" onchange="document.getElementById('channel-input').value=this.value">
            <option value="sms">SMS</option>
            <option value="email">Email</option>
            <option value="both">Both</option>
          </select>
        </div>
        <button type="submit" form="reminder-form" class="btn btn-p">📨 Send to Selected</button>
      </div>
    </div>
    <div class="card">
      <div class="ch">Bulk Send (All Unpaid)</div>
      <div class="cb">
        <form method="POST" action="{{ route('fees.reminders.bulk') }}">
        @csrf
        <div class="fg"><label class="fl">Term</label>
          <select name="term_id" class="fc">
            <option value="">All Terms</option>
            @foreach($terms as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
          </select>
        </div>
        <div class="fg"><label class="fl">Channel</label>
          <select name="channel" class="fc"><option value="sms">SMS</option><option value="email">Email</option></select>
        </div>
        <button type="submit" class="btn btn-a" onclick="return confirm('Send to ALL guardians with outstanding fees?')">⚡ Bulk Send</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
document.getElementById('select-all').addEventListener('change', function() {
    document.querySelectorAll('.inv-chk').forEach(cb => cb.checked = this.checked);
});
</script>
@endsection