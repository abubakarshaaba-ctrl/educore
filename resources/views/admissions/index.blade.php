@extends('layouts.app')
@section('title','Admissions')
@section('page-title','Admissions')
@push('styles')
<style>
.ph{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px}
.stats-row{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:16px}
.sc{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px;text-align:center}
.sv{font-size:22px;font-weight:800;color:var(--midnight)}
.sl{font-size:10px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:2px}
.sv-p{color:var(--indigo)}.sv-g{color:var(--emerald)}.sv-r{color:var(--crimson)}.sv-a{color:var(--amber)}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.filter-bar{background:white;border:1px solid var(--border);border-radius:10px;padding:12px 16px;margin-bottom:14px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap}
.fg{display:flex;flex-direction:column;gap:4px}
.fl{font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:8px 11px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:#F8FAFC}
.status{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.s-pending{background:#FFFBEB;color:var(--amber)}.s-admitted{background:#ECFDF5;color:var(--emerald)}.s-rejected{background:#FEF2F2;color:var(--crimson)}.s-shortlisted{background:#EFF6FF;color:var(--indigo)}.s-withdrawn{background:#F1F5F9;color:var(--slate)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12.5px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}
.btn-sm{padding:4px 10px;font-size:11px}
@media(max-width:1024px){.stats-row{grid-template-columns:repeat(3,1fr)}}
@media(max-width:768px){.stats-row{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush
@section('content')
<div class="ph">
    <div><h2 style="font-size:18px;font-weight:700">Admissions Management</h2></div>
    <a href="{{ route('admissions.create') }}" class="btn btn-p">+ New Application</a>
</div>
<div class="stats-row">
    <div class="sc"><div class="sv">{{ $stats['total'] }}</div><div class="sl">Total</div></div>
    <div class="sc"><div class="sv sv-a">{{ $stats['pending'] }}</div><div class="sl">Pending</div></div>
    <div class="sc"><div class="sv sv-p">{{ $stats['shortlisted'] }}</div><div class="sl">Shortlisted</div></div>
    <div class="sc"><div class="sv sv-g">{{ $stats['admitted'] }}</div><div class="sl">Admitted</div></div>
    <div class="sc"><div class="sv sv-r">{{ $stats['rejected'] }}</div><div class="sl">Rejected</div></div>
</div>
<form method="GET">
<div class="filter-bar">
    <div class="fg"><span class="fl">Search</span><input type="text" name="search" class="fc" value="{{ request('search') }}" placeholder="Name or App. No."></div>
    <div class="fg"><span class="fl">Status</span>
        <select name="status" class="fc">
            <option value="">All</option>
            @foreach(['pending','shortlisted','admitted','rejected','withdrawn'] as $s)
                <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-p">Filter</button>
</div>
</form>
<div class="card">
<div class="tbl"><table>
    <thead><tr><th>App. No.</th><th>Name</th><th>Gender</th><th>Class Applied</th><th>Guardian</th><th>Date</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($admissions as $adm)
    <tr>
        <td style="font-family:monospace;font-size:11px">{{ $adm->application_number }}</td>
        <td><strong>{{ $adm->last_name }}, {{ $adm->first_name }}</strong></td>
        <td>{{ ucfirst($adm->gender) }}</td>
        <td>{{ optional($adm->applyingForClassLevel)->name ?? '—' }}</td>
        <td>{{ $adm->guardian_name }}<br><span style="font-size:11px;color:var(--slate-light)">{{ $adm->guardian_phone }}</span></td>
        <td style="font-size:11px">{{ \Carbon\Carbon::parse($adm->application_date)->format('d M Y') }}</td>
        <td><span class="status s-{{ $adm->status }}">{{ ucfirst($adm->status) }}</span></td>
        <td><a href="{{ route('admissions.show',$adm) }}" class="btn btn-p btn-sm">Review</a></td>
    </tr>
    @empty
    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--slate-light)">No applications found</td></tr>
    @endforelse
    </tbody>
</table></div>
</div>
{{ $admissions->links() }}
@endsection