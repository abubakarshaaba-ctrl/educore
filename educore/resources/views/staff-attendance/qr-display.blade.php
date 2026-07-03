<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Clock-In QR — {{ optional(auth()->user()->tenant)->name }}</title>
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body {
    font-family: 'Inter', system-ui, sans-serif;
    background: linear-gradient(135deg, #0F172A 0%, #1E3A5F 60%, #0F172A 100%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    padding: 20px;
    gap: 20px;
}

/* Admin controls bar (hidden in fullscreen / print) */
.admin-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 10px 16px;
    flex-wrap: wrap;
}
.admin-bar span {
    font-size: 12px;
    color: rgba(255,255,255,0.5);
    margin-right: 6px;
}
.btn {
    padding: 7px 14px;
    font-size: 12px;
    font-weight: 600;
    font-family: inherit;
    border-radius: 7px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 150ms;
}
.btn-white  { background: white; color: #1E293B; }
.btn-ghost  { background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); }
.btn-danger { background: rgba(220,38,38,0.2); color: #FCA5A5; border: 1px solid rgba(220,38,38,0.3); }
.btn-danger:hover { background: #DC2626; color: white; }

/* Clock display */
.live-clock {
    font-size: 56px;
    font-weight: 900;
    letter-spacing: -0.03em;
    font-variant-numeric: tabular-nums;
    text-shadow: 0 2px 20px rgba(0,0,0,0.3);
}
.live-date {
    font-size: 14px;
    color: rgba(255,255,255,0.6);
    margin-top: 4px;
    letter-spacing: 0.03em;
}

/* QR card */
.qr-card {
    background: white;
    border-radius: 20px;
    padding: 28px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    box-shadow: 0 30px 80px rgba(0,0,0,0.4), 0 4px 12px rgba(0,0,0,0.2);
    max-width: 380px;
    width: 100%;
}

.qr-school { font-size: 12px; font-weight: 800; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.08em; }
.qr-title  { font-size: 20px; font-weight: 900; color: #1E293B; text-align: center; letter-spacing: -0.02em; }

.qr-img-wrap {
    border: 3px solid #E2E8F0;
    border-radius: 14px;
    padding: 10px;
    background: white;
}
.qr-img { width: 260px; height: 260px; display: block; }

/* Time rules */
.rules {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}
.rule-pill {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}
.r-early   { background: #E0F2FE; color: #0284C7; }
.r-present { background: #DCFCE7; color: #15803D; }
.r-late    { background: #FEF9C3; color: #A16207; }

.qr-instruction {
    font-size: 12px;
    color: #64748B;
    text-align: center;
    line-height: 1.6;
}

/* Static badge */
.static-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #ECFDF5;
    color: #059669;
    border: 1px solid #A7F3D0;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 11px;
    font-weight: 700;
}

/* Fullscreen hint */
.fullscreen-hint {
    font-size: 11px;
    color: rgba(255,255,255,0.3);
    text-align: center;
}

/* No-phone instruction */
.no-phone {
    font-size: 12px;
    color: rgba(255,255,255,0.45);
    text-align: center;
    max-width: 360px;
    line-height: 1.6;
}

@media print {
    .admin-bar, .fullscreen-hint, .no-phone { display: none; }
    body { background: white; color: #1E293B; }
    .live-clock { color: #1E293B; font-size: 36px; }
    .live-date  { color: #64748B; }
    .qr-card    { box-shadow: none; border: 2px solid #E2E8F0; }
}
</style>
</head>
<body>

{{-- Admin controls --}}
<div class="admin-bar">
    <span>Admin controls:</span>
    <a href="{{ route('staff-attendance.index') }}" class="btn btn-ghost">← Dashboard</a>
    <button onclick="document.documentElement.requestFullscreen()" class="btn btn-white">⛶ Fullscreen</button>
    <button onclick="window.print()" class="btn btn-ghost">🖨 Print QR</button>
    <form method="POST" action="{{ route('staff-attendance.reset-qr') }}" style="display:inline"
          onsubmit="return confirm('Reset the static QR?\n\nThis will invalidate ALL previously printed QR posters and ID cards that use the screen QR. Staff will not be able to clock in until you display the new QR.\n\nOnly do this if the QR has been compromised.')">
        @csrf
        <button type="submit" class="btn btn-danger">🔄 Reset QR</button>
    </form>
</div>

{{-- Live clock --}}
<div style="text-align:center">
    <div class="live-clock" id="clock">--:--:--</div>
    <div class="live-date">{{ \Carbon\Carbon::now()->format('l, d F Y') }}</div>
</div>

{{-- QR Card --}}
<div class="qr-card">
    <div class="qr-school">{{ optional(auth()->user()->tenant)->name ?? 'School' }}</div>
    <div class="qr-title">Scan to Clock In / Out</div>

    <div class="qr-img-wrap">
        @if($qrBase64)
            <img src="{{ $qrBase64 }}" class="qr-img" alt="Clock-In QR Code">
        @else
            {{-- Fallback: Google Charts QR API (no library needed) --}}
            <div style="width:260px;height:260px;background:white;display:flex;align-items:center;justify-content:center;border-radius:8px;padding:10px">
                <img src="https://chart.googleapis.com/chart?cht=qr&chl={{ urlencode($url ?? '') }}&chs=240x240&choe=UTF-8"
                     style="width:240px;height:240px;display:block" alt="QR Code">
            </div>
        @endif
    </div>

    {{-- Static badge --}}
    <div class="static-badge">
        ✓ Permanent QR — does not expire
    </div>

    {{-- Time rules --}}
    <div class="rules">
        <span class="rule-pill r-early">
            🔵 Early — before {{ \Carbon\Carbon::parse($settings->resumption_time)->format('H:i') }}
        </span>
        <span class="rule-pill r-present">
            🟢 Present — within {{ $settings->grace_minutes }}min grace
        </span>
        <span class="rule-pill r-late">
            🟡 Late — after {{ \Carbon\Carbon::parse($settings->resumption_time)->addMinutes($settings->grace_minutes)->format('H:i') }}
        </span>
    </div>

    <div class="qr-instruction">
        📱 Open <strong>EduCore</strong> on your phone and scan this QR.<br>
        Or scan your <strong>Staff ID Card</strong> QR to clock in.
    </div>
</div>

<div class="no-phone">
    No phone? Ask a colleague at the admin desk to <strong>proxy clock-in</strong> for you using your Staff ID Card QR or your Attendance PIN.
</div>

<div class="fullscreen-hint">Press F11 for fullscreen · This QR is permanent and can be printed and displayed</div>

<script>
function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent =
        String(now.getHours()).padStart(2,'0') + ':' +
        String(now.getMinutes()).padStart(2,'0') + ':' +
        String(now.getSeconds()).padStart(2,'0');
}
updateClock();
setInterval(updateClock, 1000);
</script>
</body>
</html>
