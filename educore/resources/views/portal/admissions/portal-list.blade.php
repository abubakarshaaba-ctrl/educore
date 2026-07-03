@extends('layouts.app')
@section('title','Portal Applications')
@section('page-title','Online Applications')
@push('styles')
<style>
.ph{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px}
.sg{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:16px}
.sc{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px;text-align:center}
.sv{font-size:22px;font-weight:800}.sl{font-size:10px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:2px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#F8FAFC}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-pending{background:#FFFBEB;color:#92400E}.b-shortlisted{background:#EFF6FF;color:var(--indigo)}.b-admitted{background:#ECFDF5;color:var(--emerald)}.b-rejected{background:#FEF2F2;color:var(--crimson)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-g{background:var(--emerald);color:white}
.portal-box{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.portal-box .url{font-family:monospace;font-size:12px;color:var(--indigo);word-break:break-all}
@media(max-width:1024px){.sg{grid-template-columns:repeat(3,1fr)}}
</style>
@endpush
@section('content')
<div class="portal-box">
    <div>
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--indigo);margin-bottom:4px">&#127760; Your Public Portal URL</div>
        <div class="url">{{ $portalUrl }}</div>
    </div>
    <div style="display:flex;gap:8px;flex-shrink:0">
        <a href="{{ $portalUrl }}" target="_blank" class="btn btn-p">Preview &#8599;</a>
        <a href="{{ route('admissions.portal.settings') }}" class="btn" style="background:white;border:1px solid var(--border);color:var(--midnight)">&#9881; Settings</a>
    </div>
</div>

@php
$stats = [
    'total'       => $applications->total(),
    'pending'     => $portalStats['pending']     ?? 0,
    'shortlisted' => $portalStats['shortlisted'] ?? 0,
    'admitted'    => $portalStats['admitted']    ?? 0,
    'rejected'    => $portalStats['rejected']    ?? 0,
];
@endphp

<div class="sg">
    <div class="sc"><div class="sv" style="color:var(--indigo)">{{ $stats['total'] }}</div><div class="sl">Total</div></div>
    <div class="sc"><div class="sv" style="color:var(--amber)">{{ $stats['pending'] }}</div><div class="sl">Pending</div></div>
    <div class="sc"><div class="sv" style="color:var(--indigo)">{{ $stats['shortlisted'] }}</div><div class="sl">Shortlisted</div></div>
    <div class="sc"><div class="sv" style="color:var(--emerald)">{{ $stats['admitted'] }}</div><div class="sl">Admitted</div></div>
    <div class="sc"><div class="sv" style="color:var(--crimson)">{{ $stats['rejected'] }}</div><div class="sl">Rejected</div></div>
</div>

<div class="card">
    <div class="ch">
        Online Applications
        <div style="display:flex;gap:8px">
            <a href="{{ route('admissions.export') }}?source=portal" class="btn" style="background:white;border:1px solid var(--border);color:var(--midnight)">&#11015; Export CSV</a>
            <a href="{{ route('admissions.index') }}" class="btn" style="background:white;border:1px solid var(--border);color:var(--midnight)">All Applications</a>
        </div>
    </div>
    <div class="tbl"><table>
        <thead><tr><th>App No</th><th>Applicant</th><th>Class</th><th>Guardian</th><th>Phone</th><th>Applied</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($applications as $adm)
        <tr>
            <td style="font-family:monospace;font-size:11px">{{ $adm->application_number }}</td>
            <td><strong>{{ $adm->last_name }}, {{ $adm->first_name }}</strong></td>
            <td style="font-size:11px">{{ optional($adm->applyingForClassLevel)->name ?? '—' }}</td>
            <td style="font-size:12px">{{ $adm->guardian_name }}</td>
            <td style="font-size:12px">{{ $adm->guardian_phone }}</td>
            <td style="font-size:11px;color:var(--slate-light)">{{ \Carbon\Carbon::parse($adm->application_date)->format('d M Y') }}</td>
            <td><span class="badge b-{{ $adm->status }}">{{ ucfirst($adm->status) }}</span></td>
            <td><a href="{{ route('admissions.show',$adm) }}" class="btn btn-p">Review</a></td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--slate-light)">No online applications yet. Share your portal URL with parents.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $applications->links() }}
</div>
@endsection
