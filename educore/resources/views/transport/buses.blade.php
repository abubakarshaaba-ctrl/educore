@extends('layouts.app')
@section('title','Buses')
@section('page-title','Transport')
@push('styles')
<style>
.tabs{display:flex;align-items:flex-end;gap:2px;width:100%;max-width:100%;margin-bottom:20px;border-bottom:1px solid var(--border);overflow-x:auto;overflow-y:hidden;flex-wrap:nowrap;-webkit-overflow-scrolling:touch;scrollbar-width:thin;overscroll-behavior-x:contain;scroll-snap-type:x proximity}.tab{display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;min-height:42px;padding:0 15px;font-size:12.5px;font-weight:700;border:0;border-bottom:3px solid transparent;border-radius:8px 8px 0 0;background:transparent;color:var(--slate);text-decoration:none;white-space:nowrap;transition:all 150ms;scroll-snap-align:start}.tab:hover{background:#F8FAFC;color:var(--midnight)}.tab.active{background:var(--indigo-bg);color:var(--midnight);border-bottom-color:var(--indigo);font-weight:800}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}
.card-title{font-size:13px;font-weight:700}
table{width:100%;border-collapse:collapse;font-size:13px}th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);text-align:left}td{padding:10px 14px;border-bottom:1px solid var(--border)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12px;font-weight:600;font-family:inherit;border:none;border-radius:8px;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-primary{background:var(--indigo);color:white}.btn-danger{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}.fl{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
@media(max-width:640px){.tab{min-height:44px;padding:0 13px}}
</style>
@endpush
@section('content')
<div class="tabs" role="tablist" aria-label="Transport sections">
    <a href="{{ route('transport.routes') }}" class="tab">🛣 Routes</a>
    <a href="{{ route('transport.buses') }}" class="tab active" aria-current="page">🚌 Buses</a>
    <a href="{{ route('transport.assignments') }}" class="tab">👦 Student Assignments</a>
</div>
@if(session('success'))<div class="alert-success">✓ {{ session('success') }}</div>@endif
<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">
<div class="card"><div class="card-head"><span class="card-title">🚌 Fleet</span></div>
<div class="tbl"><table><thead><tr><th>Plate No.</th><th>Model</th><th>Capacity</th><th>Year</th><th>Routes</th><th></th></tr></thead>
<tbody>
@forelse($buses as $bus)
<tr>
    <td style="font-weight:700">{{ $bus->plate_number }}</td>
    <td>{{ $bus->model ?? '—' }}</td>
    <td>{{ $bus->capacity }} seats</td>
    <td>{{ $bus->year ?? '—' }}</td>
    <td>{{ $bus->routes->count() ?? 0 }}</td>
    <td><form method="POST" action="{{ route('transport.buses.destroy', $bus) }}">@csrf @method('DELETE')<button class="btn btn-danger" style="padding:5px 10px;font-size:11px" onclick="return confirm('Remove bus?')">✕</button></form></td>
</tr>
@empty
<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--slate-light)">No buses registered.</td></tr>
@endforelse
</tbody></table></div></div>
<div class="card"><div class="card-head"><span class="card-title">➕ Add Bus</span></div>
<div style="padding:18px">
<form method="POST" action="{{ route('transport.buses.store') }}">@csrf
    <div class="fg"><label class="fl">Plate Number *</label><input name="plate_number" class="fc" required placeholder="ABC-123-XY"></div>
    <div class="fg"><label class="fl">Model</label><input name="model" class="fc" placeholder="Toyota Coaster"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="fg"><label class="fl">Capacity *</label><input name="capacity" type="number" class="fc" required min="1" value="30"></div>
        <div class="fg"><label class="fl">Year</label><input name="year" type="number" class="fc" min="1990" placeholder="{{ date('Y') }}"></div>
    </div>
    <button class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px">+ Add Bus</button>
</form></div></div>
</div>
@endsection
