@extends('layouts.app')
@section('title','Route Manifest — '.$route->name)
@section('page-title','Transport Manifest')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse;font-size:13px}th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);text-align:left}td{padding:9px 14px;border-bottom:1px solid var(--border)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12px;font-weight:600;font-family:inherit;border:none;border-radius:8px;cursor:pointer;text-decoration:none}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
</style>
@endpush
@section('content')
<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px">
    <a href="{{ route('transport.routes') }}" class="btn btn-ghost">← Routes</a>
    <div>
        <div style="font-size:16px;font-weight:700">{{ $route->name }}</div>
        <div style="font-size:12px;color:var(--slate-light)">{{ $assignments->count() }} students · Fare: ₦{{ number_format($route->fare) }} · {{ optional($route->bus)->plate_number ?? 'No bus' }}</div>
    </div>
    <button onclick="window.print()" class="btn" style="margin-left:auto;background:var(--indigo);color:white">🖨 Print</button>
</div>
<div class="card">
    <div class="card-head"><span style="font-size:13px;font-weight:700">Passenger Manifest</span><span style="font-size:12px;color:var(--slate-light)">{{ now()->format('d M Y') }}</span></div>
    <div class="tbl"><table><thead><tr><th>#</th><th>Student</th><th>Class</th><th>Pickup Stop</th><th>Direction</th><th>Guardian</th><th>Guardian Phone</th></tr></thead>
    <tbody>
    @forelse($assignments as $i => $a)
    @php $g = $a->student?->guardians?->first(); @endphp
    <tr>
        <td>{{ $i+1 }}</td>
        <td style="font-weight:600">{{ optional($a->student)->full_name }}<div style="font-size:11px;color:var(--slate-light)">{{ optional($a->student)->admission_number }}</div></td>
        <td>{{ optional(optional(optional($a->student)->currentClassArm)->classLevel)->name }}</td>
        <td>{{ $a->pickup_stop ?? '—' }}</td>
        <td>{{ ucfirst($a->direction) }}</td>
        <td>{{ optional($g)->full_name ?? '—' }}</td>
        <td>{{ optional($g)->phone ?? '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--slate-light)">No students assigned to this route.</td></tr>
    @endforelse
    </tbody></table></div>
</div>
@endsection
