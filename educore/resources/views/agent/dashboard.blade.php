@extends('agent.layout')
@section('title','Agent Dashboard')
@section('content')
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
    @foreach([
        ['Schools Referred', $stats['total_schools'], '#2563EB', '🏫'],
        ['Approved',          $stats['approved'],      '#059669', '✅'],
        ['Total Earned',      '₦'.number_format($stats['total_earned']), '#D97706', '💰'],
        ['Unpaid Balance',    '₦'.number_format($stats['unpaid']),       '#DC2626', '🏦'],
    ] as [$lbl,$val,$clr,$ic])
    <div style="background:white;border:1px solid #E2E8F0;border-radius:10px;padding:16px">
        <div style="font-size:22px;margin-bottom:4px">{{ $ic }}</div>
        <div style="font-size:20px;font-weight:800;color:{{ $clr }}">{{ $val }}</div>
        <div style="font-size:11px;font-weight:600;color:#94A3B8;text-transform:uppercase;letter-spacing:.05em;margin-top:2px">{{ $lbl }}</div>
    </div>
    @endforeach
</div>

{{-- Referral link --}}
<div style="background:white;border:1px solid #E2E8F0;border-radius:12px;padding:20px;margin-bottom:20px">
    <div style="font-size:13px;font-weight:700;margin-bottom:6px">🔗 Your Referral Link</div>
    <div style="font-size:12px;color:#64748B;margin-bottom:10px">Share this link with schools. Every time a referred school pays their subscription, your % commission is <strong style="color:#059669">instantly credited to your balance</strong>.</div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <code style="flex:1;background:#F1F5F9;border:1px solid #E2E8F0;border-radius:7px;padding:9px 12px;font-size:12px;color:#2563EB;word-break:break-all">{{ $agent->referralLink() }}</code>
        <button onclick="navigator.clipboard.writeText('{{ $agent->referralLink() }}').then(()=>this.textContent='Copied!')"
                style="padding:9px 14px;background:#2563EB;color:white;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap">📋 Copy</button>
    </div>
</div>

{{-- Recent referrals --}}
<div style="background:white;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid #E2E8F0;background:#F8FAFC;font-size:13px;font-weight:700">Recent Referrals</div>
    <div class="tbl"><table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr>
            @foreach(['School','Date','Amount','Commission','Status'] as $h)
            <th style="padding:8px 14px;background:#F8FAFC;border-bottom:1px solid #E2E8F0;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.05em;text-align:left">{{ $h }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @forelse($referrals as $r)
        <tr>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9">{{ optional($r->tenant)->name ?? '—' }}</td>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9;font-size:12px;color:#64748B">{{ optional($r->sale_date)->format('d M Y') ?? '—' }}</td>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9">₦{{ number_format($r->sale_amount) }}</td>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9;font-weight:700;color:#059669">₦{{ number_format($r->commission_amount) }}</td>
            <td style="padding:10px 14px;border-bottom:1px solid #F1F5F9">
                <span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:{{ $r->status==='approved'?'#ECFDF5':($r->status==='paid'?'#EFF6FF':'#FFFBEB') }};color:{{ $r->status==='approved'?'#059669':($r->status==='paid'?'#2563EB':'#D97706') }}">{{ ucfirst($r->status) }}</span>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" style="padding:40px;text-align:center;color:#94A3B8">No referrals yet. Share your link to get started!</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:10px 16px"><a href="{{ route('agent.portal.schools') }}" style="font-size:12px;color:#2563EB;text-decoration:none">View all schools →</a></div>
</div>
@endsection
