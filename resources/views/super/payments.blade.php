@extends('layouts.super')
@section('title', 'Platform Revenue')
@section('page-title', 'Revenue & Payments')

@push('styles')
<style>
    .rev-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
    .rev-card { background:white; border:1px solid var(--border); border-radius:12px; padding:16px 18px; }
    .rev-val { font-size:22px; font-weight:800; color:#059669; letter-spacing:-0.02em; }
    .rev-lbl { font-size:10px; font-weight:600; color:#94A3B8; text-transform:uppercase; letter-spacing:0.05em; margin-top:3px; }
    .card { background:white; border:1px solid var(--border); border-radius:12px; overflow:hidden; }
    .card-h { padding:12px 18px; border-bottom:1px solid var(--border); background:#F8FAFC; display:flex; align-items:center; justify-content:space-between; }
    .card-title { font-size:13px; font-weight:700; color:#0F172A; }
    table { width:100%; border-collapse:collapse; }
    thead th { font-size:10px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.05em; padding:8px 14px; text-align:left; background:#F8FAFC; border-bottom:1px solid var(--border); }
    tbody td { padding:11px 14px; border-bottom:1px solid var(--border); font-size:12.5px; color:#0F172A; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#F8FAFC; }
    .badge { display:inline-flex; font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; }
    .badge-green { background:#ECFDF5; color:#059669; }
    .badge-red   { background:#FEF2F2; color:#DC2626; }
    .badge-amber { background:#FFFBEB; color:#D97706; }
    .filter-bar { background:white; border:1px solid var(--border); border-radius:10px; padding:12px 16px; margin-bottom:16px; display:flex; gap:12px; align-items:flex-end; }
    .filter-group { display:flex; flex-direction:column; gap:4px; }
    .filter-label { font-size:10px; font-weight:700; color:#64748B; text-transform:uppercase; }
    .filter-control { padding:7px 10px; font-size:12.5px; font-family:inherit; border:1px solid var(--border); border-radius:7px; background:#F8FAFC; outline:none; }
    .btn { display:inline-flex; align-items:center; gap:5px; padding:8px 14px; font-size:12.5px; font-weight:600; font-family:inherit; border-radius:8px; border:none; cursor:pointer; transition:all 150ms; }
    
    @media(max-width:1024px) { .rev-grid { grid-template-columns:repeat(2,1fr); } }
</style>
@endpush

@section('content')
<div class="rev-grid">
    <div class="rev-card">
        <div class="rev-val">₦{{ number_format($revenue['total']) }}</div>
        <div class="rev-lbl">Total Revenue</div>
    </div>
    <div class="rev-card">
        <div class="rev-val">₦{{ number_format($revenue['this_year']) }}</div>
        <div class="rev-lbl">This Year</div>
    </div>
    <div class="rev-card">
        <div class="rev-val">₦{{ number_format($revenue['this_month']) }}</div>
        <div class="rev-lbl">This Month</div>
    </div>
    <div class="rev-card">
        <div class="rev-val">₦{{ number_format($revenue['today']) }}</div>
        <div class="rev-lbl">Today</div>
    </div>
</div>

<form method="GET">
    <div class="filter-bar">
        <div class="filter-group">
            <span class="filter-label">Status</span>
            <select name="status" class="filter-control">
                <option value="">All</option>
                <option value="confirmed" {{ request('status')==='confirmed'?'selected':'' }}>Confirmed</option>
                <option value="pending"   {{ request('status')==='pending'?'selected':'' }}>Pending</option>
                <option value="failed"    {{ request('status')==='failed'?'selected':'' }}>Failed</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>

<div class="card">
    <div class="card-h"><span class="card-title">Payment History</span></div>
    <div class="tbl"><table>
        <thead>
            <tr><th>School</th><th>Reference</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody>
            @forelse($payments as $pay)
            <tr>
                <td><strong>{{ $pay->school_name }}</strong></td>
                <td style="font-size:11px;color:#64748B;font-family:monospace">{{ $pay->reference }}</td>
                <td style="font-weight:700;color:#059669">₦{{ number_format($pay->amount) }}</td>
                <td style="font-size:11.5px">{{ $pay->payment_method ?? '—' }}</td>
                <td>
                    <span class="badge {{ $pay->status==='confirmed'?'badge-green':($pay->status==='failed'?'badge-red':'badge-amber') }}">
                        {{ ucfirst($pay->status) }}
                    </span>
                </td>
                <td style="font-size:11px;color:#64748B">{{ \Carbon\Carbon::parse($pay->created_at)->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#94A3B8">No payments found</td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>
{{ $payments->links() }}
@endsection
