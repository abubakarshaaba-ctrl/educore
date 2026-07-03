@extends('layouts.app')
@section('title','Transport Management')
@section('page-title','Transport')

@push('styles')
<style>
.tabs{display:flex;gap:6px;margin-bottom:20px}
.tab{padding:8px 18px;font-size:13px;font-weight:600;border-radius:8px;border:1.5px solid var(--border);background:white;color:var(--slate);text-decoration:none;transition:all 150ms}
.tab.active,.tab:hover{background:var(--indigo);border-color:var(--indigo);color:white}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}
.card-title{font-size:13px;font-weight:700}
table{width:100%;border-collapse:collapse;font-size:12.5px;min-width:680px}
th{padding:8px 10px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);text-align:left}
td{padding:9px 10px;border-bottom:1px solid var(--border);color:var(--midnight);vertical-align:middle}
tr:hover td{background:#FAFBFF}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;font-weight:600;font-family:inherit;border:none;border-radius:8px;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-primary{background:var(--indigo);color:white}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.btn-danger{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
.badge{display:inline-flex;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-active{background:#ECFDF5;color:#059669}.b-inactive{background:#FEF2F2;color:#DC2626}
.staff-chip{display:flex;align-items:center;gap:5px;font-size:12px}
.staff-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.tbl-wrap{overflow-x:auto;width:100%}
@media(max-width:960px) { .pg-2col-lg { grid-template-columns:1fr !important; } }
@media(max-width:640px) { .form-row { grid-template-columns:1fr; } }
@media(max-width:480px) { .tabs { flex-wrap:wrap; } }
</style>
@endpush

@section('content')
<div class="tabs">
    <a href="{{ route('transport.routes') }}"      class="tab active">🛣 Routes</a>
    <a href="{{ route('transport.buses') }}"        class="tab">🚌 Buses</a>
    <a href="{{ route('transport.assignments') }}"  class="tab">👦 Student Assignments</a>
</div>

@if(session('success'))<div class="alert-success">✓ {{ session('success') }}</div>@endif

<div class="pg-2col-lg">

    {{-- Routes table --}}
    <div class="card">
        <div class="card-head">
            <span class="card-title">🛣 Transport Routes ({{ $routes->count() }})</span>
        </div>
        <div class="tbl-wrap">
        <div class="tbl"><table>
            <thead>
                <tr>
                    <th>Route</th>
                    <th>Bus</th>
                    <th>Driver</th>
                    <th>Bus Assistant</th>
                    <th>Fare</th>
                    <th>Times</th>
                    <th>Students</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($routes as $route)
            <tr>
                <td style="font-weight:600">{{ $route->name }}</td>
                <td style="font-size:12px">{{ optional($route->bus)->plate_number ?? '—' }}</td>
                <td>
                    @if($route->driver)
                    <div class="staff-chip">
                        <div class="staff-dot" style="background:#2563EB"></div>
                        {{ $route->driver->name }}
                    </div>
                    @else <span style="color:var(--slate-light);font-size:12px">Unassigned</span> @endif
                </td>
                <td>
                    @if($route->assistant)
                    <div class="staff-chip">
                        <div class="staff-dot" style="background:#059669"></div>
                        {{ $route->assistant->name }}
                    </div>
                    @else <span style="color:var(--slate-light);font-size:12px">—</span> @endif
                </td>
                <td>₦{{ number_format($route->fare) }}</td>
                <td style="font-size:11px;color:var(--slate)">
                    @if($route->morning_time)<div>🌅 {{ $route->morning_time }}</div>@endif
                    @if($route->evening_time)<div>🌇 {{ $route->evening_time }}</div>@endif
                    @if(!$route->morning_time && !$route->evening_time)—@endif
                </td>
                <td style="font-weight:600">{{ $route->assignments->count() }}</td>
                <td><span class="badge {{ $route->is_active ? 'b-active':'b-inactive' }}">{{ $route->is_active ? 'Active':'Inactive' }}</span></td>
                <td>
                    <div style="display:flex;gap:5px">
                        <a href="{{ route('transport.manifest', $route) }}" class="btn btn-ghost" style="padding:4px 8px;font-size:11px">📋 Manifest</a>
                        <button class="btn btn-ghost" style="padding:4px 8px;font-size:11px"
                                onclick="editRoute({{ json_encode($route) }})">Edit</button>
                        <form method="POST" action="{{ route('transport.routes.destroy', $route) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger" style="padding:4px 8px;font-size:11px"
                                    onclick="return confirm('Remove route?')">✕</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;padding:50px;color:var(--slate-light)">No routes added yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        </div>
    </div>

    {{-- Add/Edit Route Form --}}
    <div>
        <div class="card">
            <div class="card-head"><span class="card-title" id="formTitle">➕ Add Route</span></div>
            <div style="padding:18px">
            <form method="POST" id="routeForm" action="{{ route('transport.routes.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div class="fg"><label class="fl">Route Name *</label>
                    <input name="name" id="fName" class="fc" required placeholder="e.g. Maitama – School"></div>
                <div class="form-row">
                    <div class="fg"><label class="fl">Morning Time</label>
                        <input name="morning_time" id="fMorning" type="time" class="fc"></div>
                    <div class="fg"><label class="fl">Evening Time</label>
                        <input name="evening_time" id="fEvening" type="time" class="fc"></div>
                </div>
                <div class="form-row">
                    <div class="fg"><label class="fl">Fare (₦) *</label>
                        <input name="fare" id="fFare" type="number" class="fc" min="0" required value="0"></div>
                    <div class="fg"><label class="fl">Bus</label>
                        <select name="bus_id" id="fBus" class="fc">
                            <option value="">— None —</option>
                            @foreach($buses as $bus)
                            <option value="{{ $bus->id }}">{{ $bus->plate_number }} ({{ $bus->capacity }} seats)</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Driver --}}
                <div class="fg"><label class="fl">🚗 Driver</label>
                    <select name="driver_id" id="fDriver" class="fc">
                        <option value="">— Unassigned —</option>
                        @foreach($drivers as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} <span style="color:var(--slate-light)">({{ $d->roleLabel() }})</span></option>
                        @endforeach
                    </select>
                </div>

                {{-- Bus Assistant --}}
                <div class="fg"><label class="fl">👤 Bus Assistant / Conductor</label>
                    <select name="assistant_id" id="fAssistant" class="fc">
                        <option value="">— Unassigned —</option>
                        @foreach($assistants as $a)
                        <option value="{{ $a->id }}">{{ $a->name }} <span style="color:var(--slate-light)">({{ $a->roleLabel() }})</span></option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex;gap:8px;margin-top:4px">
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center" id="submitBtn">+ Add Route</button>
                    <button type="button" class="btn btn-ghost" onclick="resetForm()" id="cancelBtn" style="display:none">Cancel</button>
                </div>
            </form>
            </div>
        </div>

        {{-- Staff on routes summary --}}
        @php
            $unassignedDrivers = $drivers->whereNotIn('id', $routes->pluck('driver_id')->filter()->toArray());
        @endphp
        @if($drivers->isNotEmpty())
        <div class="card">
            <div class="card-head"><span class="card-title">👨‍✈️ Transport Staff</span></div>
            <div style="padding:14px">
            @foreach($drivers->merge($assistants)->unique('id') as $d)
            @php
                $onRoute = $routes->where('driver_id', $d->id)->first()
                        ?? $routes->where('assistant_id', $d->id)->first();
                $asRole = $routes->where('driver_id', $d->id)->first() ? 'Driver' : ($routes->where('assistant_id', $d->id)->first() ? 'Assistant' : null);
            @endphp
            <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border)">
                <div>
                    <div style="font-size:13px;font-weight:600">{{ $d->name }}</div>
                    <div style="font-size:11px;color:var(--slate-light)">{{ $d->roleLabel() }}</div>
                </div>
                <div style="text-align:right">
                    @if($onRoute)
                    <span class="badge b-active">{{ $asRole }}: {{ Str::limit($onRoute->name,20) }}</span>
                    @else
                    <span class="badge" style="background:#F1F5F9;color:#94A3B8">Unassigned</span>
                    @endif
                </div>
            </div>
            @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function editRoute(route) {
    document.getElementById('formTitle').textContent = '✏️ Edit Route: ' + route.name;
    document.getElementById('routeForm').action = '/transport/routes/' + route.id;
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('fName').value    = route.name || '';
    document.getElementById('fMorning').value = route.morning_time || '';
    document.getElementById('fEvening').value = route.evening_time || '';
    document.getElementById('fFare').value    = route.fare || 0;
    document.getElementById('fBus').value       = route.bus_id || '';
    document.getElementById('fDriver').value    = route.driver_id || '';
    document.getElementById('fAssistant').value = route.assistant_id || '';
    document.getElementById('submitBtn').textContent = '✓ Save Changes';
    document.getElementById('cancelBtn').style.display = '';
    document.getElementById('routeForm').scrollIntoView({behavior:'smooth'});
}
function resetForm() {
    document.getElementById('formTitle').textContent = '➕ Add Route';
    document.getElementById('routeForm').action = '{{ route('transport.routes.store') }}';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('routeForm').reset();
    document.getElementById('submitBtn').textContent = '+ Add Route';
    document.getElementById('cancelBtn').style.display = 'none';
}
</script>
@endpush
