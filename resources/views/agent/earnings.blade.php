@extends('agent.layout')
@section('title','Earnings & Payouts')
@section('content')
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px">
    <div style="background:white;border:1px solid #E2E8F0;border-radius:10px;padding:16px;text-align:center">
        <div style="font-size:22px;font-weight:800;color:#D97706">₦{{ number_format($agent->total_earned) }}</div>
        <div style="font-size:11px;font-weight:600;color:#94A3B8;text-transform:uppercase;letter-spacing:.05em;margin-top:3px">Total Earned</div>
    </div>
    <div style="background:white;border:1px solid #E2E8F0;border-radius:10px;padding:16px;text-align:center">
        <div style="font-size:22px;font-weight:800;color:#059669">₦{{ number_format($agent->total_paid) }}</div>
        <div style="font-size:11px;font-weight:600;color:#94A3B8;text-transform:uppercase;letter-spacing:.05em;margin-top:3px">Total Paid Out</div>
    </div>
    <div style="background:white;border:1px solid #E2E8F0;border-radius:10px;padding:16px;text-align:center">
        <div style="font-size:22px;font-weight:800;color:#DC2626">₦{{ number_format($agent->unpaidBalance()) }}</div>
        <div style="font-size:11px;font-weight:600;color:#94A3B8;text-transform:uppercase;letter-spacing:.05em;margin-top:3px">Pending Payout</div>
    </div>
</div>

{{-- Commission history --}}
<div style="background:white;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;margin-bottom:16px">
    <div style="padding:13px 18px;border-bottom:1px solid #E2E8F0;background:#F8FAFC;font-size:13px;font-weight:700">Commission History</div>
    <div class="tbl"><table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr>
            @foreach(['School','Subscription Date','Sale Amount','Commission ({{ $agent->commission_rate }}%)','Status'] as $h)
            <th style="padding:8px 14px;background:#F8FAFC;border-bottom:1px solid #E2E8F0;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;text-align:left">{{ $h }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @forelse($referrals as $r)
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9;font-weight:600">{{ optional($r->tenant)->name ?? '—' }}</td>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9;font-size:12px;color:#64748B">{{ optional($r->sale_date)->format('d M Y') ?? '—' }}</td>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9">₦{{ number_format($r->sale_amount) }}</td>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9;font-weight:700;color:#059669">₦{{ number_format($r->commission_amount) }}</td>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9"><span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:#ECFDF5;color:#059669">Approved</span></td>
        </tr>
        @empty
        <tr><td colspan="5" style="padding:40px;text-align:center;color:#94A3B8">No commissions earned yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:10px 16px">{{ $referrals->links() }}</div>
</div>

{{-- Payouts --}}
@if($payouts->count())
<div style="background:white;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden">
    <div style="padding:13px 18px;border-bottom:1px solid #E2E8F0;background:#F8FAFC;font-size:13px;font-weight:700">Payout History</div>
    <div class="tbl"><table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr>
            @foreach(['Date','Amount','Bank','Reference','Status'] as $h)
            <th style="padding:8px 14px;background:#F8FAFC;border-bottom:1px solid #E2E8F0;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;text-align:left">{{ $h }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @foreach($payouts as $p)
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9;font-size:12px;color:#64748B">{{ $p->created_at->format('d M Y') }}</td>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9;font-weight:700">₦{{ number_format($p->amount) }}</td>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9;font-size:12px">{{ $p->bank_name }}</td>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9;font-family:monospace;font-size:11px">{{ $p->reference ?? '—' }}</td>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9"><span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:#EFF6FF;color:#2563EB">Paid</span></td>
        </tr>
        @endforeach
        </tbody>
    </table></div>
</div>
@endif
@endsection
