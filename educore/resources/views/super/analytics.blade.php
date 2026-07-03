@extends('layouts.super')
@section('title','Platform Analytics')
@section('page-title','Platform Analytics')
@push('styles')
<style>
.sg{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.sc{background:white;border:1px solid var(--border);border-radius:12px;padding:16px 18px}
.sv{font-size:26px;font-weight:800;letter-spacing:-0.03em}.sl{font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:4px}
.two{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}
.bar{background:#F1F5F9;border-radius:4px;height:8px;overflow:hidden}
.bar-fill{height:8px;border-radius:4px;background:var(--indigo)}
</style>
@endpush
@section('content')
<div class="sg">
    <div class="sc"><div class="sv" style="color:var(--indigo)">{{ $tenants->count() }}</div><div class="sl">Total Schools</div></div>
    <div class="sc"><div class="sv" style="color:var(--emerald)">{{ $growth->sum('count') }}</div><div class="sl">Joined This Year</div></div>
    <div class="sc"><div class="sv">{{ $planDist->sum('count') }}</div><div class="sl">Active Subs</div></div>
    <div class="sc"><div class="sv">{{ optional($planDist->first())->plan ?? '—' }}</div><div class="sl">Top Plan</div></div>
</div>
<div class="two">
<div>
<div class="card">
    <div class="ch">Monthly Enrollment ({{ date('Y') }})</div>
    <div class="tbl"><table>
        <thead><tr><th>Month</th><th>New Schools</th><th>Bar</th></tr></thead>
        <tbody>
        @foreach($growth as $g)
        <tr>
            <td>{{ \Carbon\Carbon::create($g->year,$g->month)->format('M Y') }}</td>
            <td style="font-weight:700">{{ $g->count }}</td>
            <td><div class="bar"><div class="bar-fill" style="width:{{ min($g->count*20,100) }}%"></div></div></td>
        </tr>
        @endforeach
        </tbody>
    </table></div>
</div>
</div>
<div>
<div class="card">
    <div class="ch">Subscription Distribution</div>
    <div class="tbl"><table>
        <thead><tr><th>Plan</th><th>Schools</th><th>%</th></tr></thead>
        <tbody>
        @php $total=$planDist->sum('count'); @endphp
        @foreach($planDist as $pd)
        <tr>
            <td><strong>{{ $pd->plan }}</strong></td>
            <td>{{ $pd->count }}</td>
            <td>{{ $total > 0 ? round(($pd->count/$total)*100) : 0 }}%</td>
        </tr>
        @endforeach
        </tbody>
    </table></div>
</div>
</div>
</div>
@endsection