@extends('agent.layout')
@section('title','My Referred Schools')
@section('content')
<div style="background:white;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden">
    <div style="padding:13px 18px;border-bottom:1px solid #E2E8F0;background:#F8FAFC;font-size:13px;font-weight:700">
        Schools I Referred ({{ $referrals->total() }})
    </div>
    <div class="tbl"><table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr>
            @foreach(['School Name','Subscription Date','Plan','Sale Amount','My Commission','Status'] as $h)
            <th style="padding:8px 14px;background:#F8FAFC;border-bottom:1px solid #E2E8F0;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;text-align:left">{{ $h }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @forelse($referrals as $r)
        @php $tenant = $r->tenant; @endphp
        <tr>
            <td style="padding:11px 14px;border-bottom:1px solid #F1F5F9">
                <div style="font-weight:700">{{ optional($tenant)->name ?? '—' }}</div>
                <div style="font-size:11px;color:#64748B">{{ optional($tenant)->email ?? '' }}</div>
            </td>
            <td style="padding:11px 14px;border-bottom:1px solid #F1F5F9;font-size:12px;color:#64748B">
                {{ optional($r->sale_date)->format('d M Y') ?? '—' }}
            </td>
            <td style="padding:11px 14px;border-bottom:1px solid #F1F5F9;font-size:12px">
                {{ optional($tenant)->plan ?? '—' }}
            </td>
            <td style="padding:11px 14px;border-bottom:1px solid #F1F5F9">₦{{ number_format($r->sale_amount) }}</td>
            <td style="padding:11px 14px;border-bottom:1px solid #F1F5F9;font-weight:700;color:#059669">₦{{ number_format($r->commission_amount) }}</td>
            <td style="padding:11px 14px;border-bottom:1px solid #F1F5F9">
                @php $colors = ['pending'=>['#FFFBEB','#D97706'],'approved'=>['#ECFDF5','#059669'],'paid'=>['#EFF6FF','#2563EB'],'rejected'=>['#FEF2F2','#DC2626']] @endphp
                <span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:{{ $colors[$r->status][0]??'#F1F5F9' }};color:{{ $colors[$r->status][1]??'#64748B' }}">{{ ucfirst($r->status) }}</span>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" style="padding:50px;text-align:center;color:#94A3B8">
            <div style="font-size:32px;margin-bottom:10px">🏫</div>
            No schools referred yet. Share your referral link to get started!
        </td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:12px 16px">{{ $referrals->links() }}</div>
</div>
@endsection
