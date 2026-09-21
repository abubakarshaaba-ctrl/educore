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
.work-hours-head,.work-hours-row{display:grid;grid-template-columns:minmax(110px,1.2fr) minmax(84px,.7fr) minmax(120px,1fr) minmax(120px,1fr) minmax(90px,.8fr);gap:10px;align-items:center}
.work-hours-head{padding:0 10px 8px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light)}
.work-hours-row{padding:10px;border:1px solid var(--border);border-radius:9px;background:#F8FAFC;margin-bottom:8px}
.work-day-name{font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;gap:8px}
.work-hours-row .fc{background:white}
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
    .work-hours-head{display:none}
    .work-hours-row{grid-template-columns:1fr 1fr;gap:8px}
    .work-day-name{grid-column:1 / -1}
    .work-hours-row .mobile-field::before{content:attr(data-label);display:block;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:var(--slate-light);margin-bottom:4px}
}
@media (max-width: 420px) {
    .work-hours-row{grid-template-columns:1fr}
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
    <div class="ch">⏰ Conventional Curriculum Work Hours</div>
    <div class="cb">
        <div class="hint" style="margin:0 0 14px;font-size:12px">
            Configure each conventional-curriculum working day independently. Resumption time, closing time and grace period may differ from one day to another.
        </div>

        <div class="work-hours-head">
            <div>Day</div>
            <div>Working</div>
            <div>Resumption</div>
            <div>Closing</div>
            <div>Grace</div>
        </div>

        @foreach($workingDays as $day)
        @php
            $key = (string) $day->day_of_week;
            $working = old("days.$key.is_working", $day->is_working ? '1' : '0') == '1';
        @endphp
        <div class="work-hours-row" data-work-row>
            <div class="work-day-name">
                <span>{{ ucfirst($key) }}</span>
            </div>
            <div class="mobile-field" data-label="Working">
                <input type="hidden" name="days[{{ $key }}][is_working]" value="0">
                <label style="display:flex;align-items:center;gap:7px;font-size:12px;font-weight:600;color:var(--slate);cursor:pointer">
                    <input type="checkbox"
                           name="days[{{ $key }}][is_working]"
                           value="1"
                           data-work-toggle
                           {{ $working ? 'checked' : '' }}>
                    Enabled
                </label>
            </div>
            <div class="mobile-field" data-label="Resumption">
                <input type="time"
                       name="days[{{ $key }}][resumption_time]"
                       class="fc"
                       data-work-input
                       value="{{ old("days.$key.resumption_time", $day->resumption_time ? substr((string) $day->resumption_time, 0, 5) : '') }}"
                       {{ $working ? '' : 'disabled' }}>
                @error("days.$key.resumption_time")<div class="hint" style="color:var(--crimson)">{{ $message }}</div>@enderror
            </div>
            <div class="mobile-field" data-label="Closing">
                <input type="time"
                       name="days[{{ $key }}][closing_time]"
                       class="fc"
                       data-work-input
                       value="{{ old("days.$key.closing_time", $day->closing_time ? substr((string) $day->closing_time, 0, 5) : '') }}"
                       {{ $working ? '' : 'disabled' }}>
                @error("days.$key.closing_time")<div class="hint" style="color:var(--crimson)">{{ $message }}</div>@enderror
            </div>
            <div class="mobile-field" data-label="Grace (min)">
                <input type="number"
                       name="days[{{ $key }}][grace_minutes]"
                       class="fc"
                       min="0"
                       max="180"
                       data-work-input
                       value="{{ old("days.$key.grace_minutes", (int) $day->grace_minutes) }}"
                       {{ $working ? '' : 'disabled' }}>
            </div>
        </div>
        @endforeach

        <div style="background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:14px;font-size:12px;color:var(--slate);margin-top:12px">
            <strong>Attendance rules:</strong><br>
            🔵 <strong>Early</strong> — before that day's resumption time<br>
            🟢 <strong>Present</strong> — from resumption time through the configured grace period<br>
            🟡 <strong>Late</strong> — after the grace period<br>
            🔴 <strong>Absent</strong> — no clock-in on an enabled conventional working day<br>
            ⚪ <strong>Not scheduled</strong> — a shared QR scan on a disabled conventional day; it can still feed an applicable parallel-curriculum attendance context without counting as conventional attendance.
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
            <button type="button" id="useLocationBtn" class="btn btn-g" style="font-size:12px;padding:7px 14px" onclick="useMyLocation()">
                📍 Use My Current Location
            </button>
            <div id="locStatus" role="status" aria-live="polite" style="font-size:12px;color:var(--slate-light);margin-top:6px"></div>
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
document.querySelectorAll('[data-work-row]').forEach(row => {
    const toggle = row.querySelector('[data-work-toggle]');
    const inputs = row.querySelectorAll('[data-work-input]');
    const sync = () => inputs.forEach(input => { input.disabled = !toggle.checked; });
    toggle.addEventListener('change', sync);
    sync();
});

function setLocationStatus(message, colour) {
    const status = document.getElementById('locStatus');
    if (!status) return;
    status.textContent = message;
    status.style.color = colour || 'var(--slate-light)';
}

function useMyLocation() {
    const button = document.getElementById('useLocationBtn');

    if (!window.isSecureContext) {
        setLocationStatus('Location access requires HTTPS. Open EduCore using the secure https:// address and try again.', 'var(--crimson)');
        return;
    }

    if (!('geolocation' in navigator)) {
        setLocationStatus('Location is not supported by this browser or device. Enter the coordinates manually.', 'var(--crimson)');
        return;
    }

    if (button) button.disabled = true;
    setLocationStatus('Requesting your current location… Keep Location/GPS enabled.', 'var(--slate)');

    navigator.geolocation.getCurrentPosition(
        position => {
            const lat = Number(position.coords.latitude);
            const lng = Number(position.coords.longitude);
            const accuracy = Number(position.coords.accuracy || 0);

            document.getElementById('geoLat').value = lat.toFixed(7);
            document.getElementById('geoLng').value = lng.toFixed(7);

            const accuracyText = accuracy > 0 ? ` (accuracy ±${Math.round(accuracy)} m)` : '';
            setLocationStatus(`✓ Location captured: ${lat.toFixed(5)}, ${lng.toFixed(5)}${accuracyText}. Save Settings to apply it.`, 'var(--emerald)');
            if (button) button.disabled = false;
        },
        error => {
            const messages = {
                1: 'Location permission was denied. Allow location permission for EduCore in your browser/app settings, then try again.',
                2: 'Your device could not determine its location. Turn on Location/GPS, move to an open area and try again.',
                3: 'Location request timed out. Check GPS and internet/location services, then try again.'
            };
            setLocationStatus(messages[error.code] || `Location failed: ${error.message || 'unknown error'}.`, 'var(--crimson)');
            if (button) button.disabled = false;
        },
        {
            enableHighAccuracy: true,
            timeout: 20000,
            maximumAge: 0
        }
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
