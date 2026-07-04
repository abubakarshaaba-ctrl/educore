@extends('layouts.super')
@section('title', 'Subscriptions')
@section('page-title', 'All Subscriptions')

@push('styles')
<style>
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
    .badge-blue  { background:#EFF6FF; color:#2563EB; }
    .filter-bar { background:white; border:1px solid var(--border); border-radius:10px; padding:12px 16px; margin-bottom:16px; display:flex; gap:12px; align-items:flex-end; }
    .filter-control { padding:7px 10px; font-size:12.5px; font-family:inherit; border:1px solid var(--border); border-radius:7px; background:#F8FAFC; outline:none; }
    .btn { display:inline-flex; align-items:center; padding:7px 14px; font-size:12px; font-weight:600; font-family:inherit; border-radius:7px; border:none; cursor:pointer; transition:all 150ms; }
    
</style>
@endpush

@section('content')
<form method="GET">
    <div class="filter-bar">
        <select name="status" class="filter-control">
            <option value="">All Status</option>
            <option value="active"    {{ request('status')==='active'?'selected':'' }}>Active</option>
            <option value="expired"   {{ request('status')==='expired'?'selected':'' }}>Expired</option>
            <option value="cancelled" {{ request('status')==='cancelled'?'selected':'' }}>Cancelled</option>
            <option value="trial"     {{ request('status')==='trial'?'selected':'' }}>Trial</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>

<div class="card">
    <div class="card-h"><span class="card-title">Subscription Records</span></div>
    <div class="tbl"><table>
        <thead>
            <tr><th>School</th><th>Plan</th><th>Cycle</th><th>Amount</th><th>Status</th><th>Expires</th><th>Method</th></tr>
        </thead>
        <tbody>
            @forelse($subscriptions as $sub)
            <tr>
                <td><strong>{{ $sub->school_name }}</strong></td>
                <td><span class="badge badge-blue">{{ $sub->plan_name }}</span></td>
                <td style="font-size:11px;text-transform:capitalize">{{ $sub->billing_cycle }}</td>
                <td style="font-weight:700;color:#059669">₦{{ number_format($sub->amount_paid) }}</td>
                <td>
                    <span class="badge {{ $sub->status==='active'?'badge-green':($sub->status==='expired'?'badge-red':'badge-amber') }}">
                        {{ ucfirst($sub->status) }}
                    </span>
                </td>
                <td style="font-size:11px;color:{{ \Carbon\Carbon::parse($sub->expires_at)->isPast()?'#DC2626':'#64748B' }}">
                    {{ \Carbon\Carbon::parse($sub->expires_at)->format('d M Y') }}
                </td>
                <td style="font-size:11px">{{ $sub->payment_method ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;padding:40px;color:#94A3B8">No subscriptions found</td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>
{{ $subscriptions->links() }}
@endsection
