<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Fees — Parent Portal</title>

@include('parent.partials.base')
<style>.nav{background:var(--midnight);padding:0 24px;height:58px;display:flex;align-items:center;justify-content:space-between}.nav a{color:#94A3B8;text-decoration:none;font-size:13px;font-weight:600;padding:7px 12px;border-radius:7px}.nav a:hover{color:white;background:rgba(255,255,255,0.1)}.nav-title{font-size:14px;font-weight:800;color:white}.content{max-width:900px;margin:0 auto;padding:24px}.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}.card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700}table{width:100%;border-collapse:collapse;font-size:13px}th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}td{padding:9px 14px;border-bottom:1px solid var(--border)}.badge{display:inline-flex;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px}.b-paid{background:#ECFDF5;color:#059669}.b-partial{background:#FFFBEB;color:#D97706}.b-unpaid{background:#FEF2F2;color:#DC2626}</style>
@include('parent.partials.responsive')
{!! \App\Helpers\ThemeHelper::css() !!}
</head><body>
<nav class="nav"><span class="nav-title">Parent Portal</span><div style="display:flex;gap:6px"><a href="{{ route('portal.parent.dashboard') }}">← Home</a><a href="{{ route('portal.parent.results') }}">Results</a><a href="{{ route('portal.parent.fees') }}">Fees</a><a href="{{ route('portal.parent.messages') }}">Messages</a></div></nav>
<div class="content">
    <h2 style="font-size:16px;font-weight:700;margin-bottom:16px">Fee Statements — {{ optional($student)->full_name }}</h2>
    <div class="card"><div class="card-head">Invoice History</div>
    <div class="tbl"><table><thead><tr><th>Invoice</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
    <tbody>
    @forelse($invoices as $inv)
    <tr>
        <td style="font-weight:600">{{ $inv->invoice_number }}<div style="font-size:11px;color:#94A3B8">{{ $inv->created_at->format('d M Y') }}</div></td>
        <td>₦{{ number_format($inv->total_amount) }}</td>
        <td style="color:var(--emerald)">₦{{ number_format($inv->amount_paid) }}</td>
        <td style="color:{{ ($inv->total_amount-$inv->amount_paid)>0 ? 'var(--crimson)':'var(--emerald)' }};font-weight:700">₦{{ number_format($inv->total_amount-$inv->amount_paid) }}</td>
        <td><span class="badge b-{{ $inv->status === 'paid' ? 'paid' : ($inv->status === 'partially_paid' ? 'partial' : 'unpaid') }}">{{ ucfirst(str_replace('_',' ',$inv->status)) }}</span></td>
    </tr>
    @empty
    <tr><td colspan="5" style="text-align:center;padding:40px;color:#94A3B8">No invoices found.</td></tr>
    @endforelse
    </tbody></table></div><div style="padding:14px">{{ $invoices->links() }}</div></div>
</div></body></html>
