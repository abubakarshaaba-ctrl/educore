@extends('layouts.app')
@section('title','Staff Attendance Settings')
@section('page-title','Staff Attendance')

@push('styles')
<style>
.settings-wrap{width:100%}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.ch{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;gap:8px}
.cb{padding:20px}
.two{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.fg{margin-bottom:14px}
.fl{display:block;font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}
.fc{width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms}
.fc:focus{border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,.1);background:white}
.hint{font-size:11px;color:var(--slate-light);margin-top:4px;line-height:1.5}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-g{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
.map-preview{height:220px;border-radius:8px;border:1.5px solid var(--border);overflow:hidden;background:#F1F5F9;display:flex;align-items:center;justify-content:center;color:var(--slate-light);font-size:13px;margin-top:10px}
.toggle-row{display:flex;align-items:center;gap:10px;margin-bottom:14px}
input[type=checkbox]{width:16px;height:16px;accent-color:var(--indigo)}
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:18px}
.breadcrumb a{color:var(--indigo);text-decoration:none;font-weight:500}
.nav-tab{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;font-size:12.5px;font-weight:600;border:1.5px solid var(--border);background:white;color:var(--slate);text-decoration:none;transition:all 150ms}
.nav-tab:hover{background:#F1F5F9;color:var(--midnight)}
.nav-tab.active{background:var(--indigo);border-color:var(--indigo);color:white}

@media (max-width: 1024px) {
    .two-col { grid-template-columns: 1fr !important; }
    .stats-row, .stat-row { grid-template-columns: repeat(2, 1fr) !important; }
    .kpi { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 640px) {
    .two, .fr { grid-template-columns: 1fr !important; }
}
@media (max-width: 480px) {
    .fr3 { grid-template-columns: 1fr !important; }
}
</style>
@endpush

@section('content')
{{-- Staff Attendance Nav --}}
<div style="display:flex;gap:4px;margin-bottom:20px;flex-wrap:wrap">
    <a href="{{ route('staff-attendance.my') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.my') ? 'active':'' }}">
        👤 My Attendance
    </a>
    @if(auth()->user()->canManage('staff-attendance'))
    <a href="{{ route('staff-attendance.index') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.index') ? 'active':'' }}">
        📋 Today's Dashboard
    </a>
    <a href="{{ route('staff-attendance.report') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.report') ? 'active':'' }}">
        📊 Monthly Report
    </a>
    <a href="{{ route('staff-attendance.qr') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.qr') ? 'active':'' }}">
        📱 QR Display
    </a>
    <a href="{{ route('staff-attendance.settings') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.settings*') ? 'active':'' }}">
        ⚙️ Settings
    </a>
    @if($hasPendingOffline)
    <a href="{{ route('staff-attendance.offline-queue') }}"
       class="nav-tab" style="color:var(--amber)">
        📡 Offline Queue
    </a>
    @endif
    @endif
</div>

<div class="breadcrumb">
    <a href="{{ route('staff-attendance.index') }}">Staff Attendance</a>
    <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    Settings
</div>

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif

<div class="settings-wrap">
<form method="POST" action="{{ route('staff-attendance.settings.save') }}">
@csrf

<div class="card">
    <div class="ch">⏰ Time Settings</div>
    <div class="cb">
        <div class="two">
            <div class="fg">
                <label class="fl">Resumption Time *</label>
                <input type="time" name="resumption_time" class="fc"
                       value="{{ substr($settings->resumption_time, 0, 5) }}" required>
                <div class="hint">Staff should clock in on or before this time.</div>
            </div>
            <div class="fg">
                <label class="fl">Grace Period (minutes)</label>
                <input type="number" name="grace_minutes" class="fc" min="0" max="120"
                       value="{{ $settings->grace_minutes }}">
                <div class="hint">Late arrivals within this window are marked <strong>Present</strong>. Beyond = <strong>Late</strong>.</div>
            </div>
        </div>
        <div class="fg">
            <label class="fl">Closing Time *</label>
            <input type="time" name="closing_time" class="fc"
                   value="{{ substr($settings->closing_time, 0, 5) }}" required style="max-width:200px">
            <div class="hint">Used for reports and clock-out reference.</div>
        </div>

        <div style="background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:14px;font-size:12px;color:var(--slate)">
            <strong>Classification rules:</strong><br>
            🔵 <strong>Early</strong> — clocks in before {{ substr($settings->resumption_time, 0, 5) }}<br>
            🟢 <strong>Present</strong> — clocks in from {{ substr($settings->resumption_time, 0, 5) }} up to {{ \Carbon\Carbon::parse($settings->resumption_time)->addMinutes($settings->grace_minutes)->format('H:i') }}<br>
            🟡 <strong>Late</strong> — clocks in after grace period<br>
            🔴 <strong>Absent</strong> — no clock-in recorded by end of day
        </div>
    </div>
</div>

<div class="card">
    <div class="ch">📍 Geo-Fence (Location Lock)</div>
    <div class="cb">
        <div class="toggle-row">
            <input type="checkbox" name="geo_enabled" value="1" id="geoToggle"
                   {{ $settings->geo_enabled ? 'checked':'' }}
                   onchange="document.getElementById('geoFields').style.display=this.checked?'block':'none'">
            <label for="geoToggle" style="font-size:13px;font-weight:600;cursor:pointer">
                Enable Geo-fence — staff must be within school grounds to clock in
            </label>
        </div>

        <div id="geoFields" style="{{ $settings->geo_enabled ? '':'display:none' }}">
            <div class="two">
                <div class="fg">
                    <label class="fl">School Latitude</label>
                    <input type="number" name="geo_lat" class="fc" step="0.0000001"
                           id="geoLat" value="{{ $settings->geo_lat }}" placeholder="e.g. 9.0579">
                </div>
                <div class="fg">
                    <label class="fl">School Longitude</label>
                    <input type="number" name="geo_lng" class="fc" step="0.0000001"
                           id="geoLng" value="{{ $settings->geo_lng }}" placeholder="e.g. 7.4951">
                </div>
            </div>
            <div class="fg">
                <label class="fl">Allowed Radius (metres)</label>
                <input type="number" name="geo_radius_meters" class="fc" min="10" max="2000"
                       value="{{ $settings->geo_radius_meters ?? 100 }}" style="max-width:200px">
                <div class="hint">Staff must be within this radius to clock in. 100m recommended for most schools.</div>
            </div>
            <button type="button" class="btn btn-g" style="font-size:12px;padding:7px 14px" onclick="useMyLocation()">
                📍 Use My Current Location
            </button>
            <div id="locStatus" style="font-size:12px;color:var(--slate-light);margin-top:6px"></div>
        </div>
    </div>
</div>

<div style="display:flex;gap:10px;margin-bottom:30px">
    <button type="submit" class="btn btn-p">💾 Save Settings</button>
    <a href="{{ route('staff-attendance.index') }}" class="btn btn-g">Cancel</a>
</div>
</form>
</div>
@endsection

@push('scripts')
<script>
function useMyLocation() {
    const s = document.getElementById('locStatus');
    s.textContent = 'Getting location...';
    navigator.geolocation.getCurrentPosition(
        p => {
            document.getElementById('geoLat').value = p.coords.latitude.toFixed(7);
            document.getElementById('geoLng').value = p.coords.longitude.toFixed(7);
            s.textContent = '✓ Location set: ' + p.coords.latitude.toFixed(5) + ', ' + p.coords.longitude.toFixed(5);
            s.style.color = 'var(--emerald)';
        },
        () => { s.textContent = 'Could not get location. Enter manually.'; s.style.color='var(--crimson)'; }
    );
}
</script>
@endpush

@push('styles')
<style>
.nav-tab{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;font-size:12.5px;font-weight:600;border:1.5px solid var(--border);background:white;color:var(--slate);text-decoration:none;transition:all 150ms}
.nav-tab:hover{background:#F1F5F9;color:var(--midnight)}
.nav-tab.active{background:var(--indigo);border-color:var(--indigo);color:white}
</style>
@endpush
