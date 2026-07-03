<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff ID Card — {{ $staff->name }}</title>
<link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif;background:#F1F5F9;display:flex;flex-direction:column;align-items:center;min-height:100vh;padding:28px 20px;gap:18px}

.ctrl{display:flex;align-items:center;gap:10px;background:white;border:1px solid #E2E8F0;border-radius:10px;padding:12px 20px;flex-wrap:wrap}
.ctrl h2{font-size:14px;font-weight:700;color:#1E293B;margin-right:8px}
.btn{padding:8px 16px;font-size:12px;font-weight:700;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all 150ms}
.btn-primary{background:var(--brand-gold,#D79A21);color:#071E45}
.btn-ghost{background:#F1F5F9;color:#475569;border:1px solid #E2E8F0}
.btn-proxy{background:#EFF6FF;color:#2563EB;border:1px solid #BFDBFE}
.btn-proxy:hover{background:#2563EB;color:white}

/* ID Card — CR80: 85.6 × 54mm → 338 × 213px at 100dpi */
.card-wrap{display:flex;flex-direction:column;align-items:center;gap:20px}
.face-label{font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.1em}

/* ── FRONT ─────────────────────────────────────────── */
.id-card{width:338px;height:213px;border-radius:12px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.18);position:relative;background:white;page-break-inside:avoid;display:flex;flex-direction:column}

.ch{
    padding:9px 12px 8px;
    display:flex;align-items:center;justify-content:space-between;
    background:var(--brand-navy,#071E45);
    flex-shrink:0;
}
.ch-left{}
.school-name{font-size:10px;font-weight:800;color:white;letter-spacing:.07em;text-transform:uppercase;line-height:1.3}
.school-tag{font-size:7px;color:rgba(255,255,255,.55);font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin-top:1px}

/* QR — top right corner of the card */
.qr-corner{
    position:absolute;
    top:7px;right:10px;
    display:flex;flex-direction:column;align-items:center;gap:2px;
}
.qr-corner img{width:52px;height:52px;border:1.5px solid rgba(255,255,255,.25);border-radius:5px;background:white;padding:2px}
.qr-corner-label{font-size:6px;font-weight:700;color:rgba(255,255,255,.6);letter-spacing:.06em;text-transform:uppercase}

/* Body */
.cb{flex:1;display:flex;padding:10px 12px;gap:11px;align-items:flex-start;overflow:hidden}

/* Passport photo — rectangular */
.photo-wrap{flex-shrink:0;width:68px}
.staff-photo{
    width:68px;height:88px;
    object-fit:cover;object-position:top;
    border:2px solid #E2E8F0;
    background:#F1F5F9;
    display:block;
    /* rectangular — no border-radius */
}
.staff-photo-init{
    width:68px;height:88px;
    background:var(--brand-navy,#071E45);
    display:flex;align-items:center;justify-content:center;
    font-size:26px;font-weight:900;color:white;
    border:2px solid rgba(7,30,69,.3);
    letter-spacing:-.02em;
}

/* Staff info */
.si{flex:1;min-width:0;padding-top:2px}
.si-name{font-size:15px;font-weight:900;color:var(--brand-navy,#071E45);letter-spacing:-.03em;line-height:1.15;margin-bottom:4px}
.si-role{font-size:9px;font-weight:800;color:var(--brand-gold,#D79A21);text-transform:uppercase;letter-spacing:.09em;margin-bottom:6px}
.si-id{display:inline-flex;align-items:center;gap:3px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:4px;padding:2px 7px;font-size:9px;font-weight:700;color:#475569;font-family:monospace;letter-spacing:.07em;margin-bottom:5px}
.si-dept{font-size:9px;color:#94A3B8;font-weight:500}

/* Footer */
.cf{background:#0F172A;padding:5px 12px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.cf-left{font-size:7px;font-weight:700;color:rgba(255,255,255,.5);letter-spacing:.08em;text-transform:uppercase}
.cf-right{font-size:7px;font-weight:700;color:rgba(255,255,255,.65);letter-spacing:.04em}

/* Accent stripe */
.accent-stripe{height:3px;background:var(--brand-gold,#D79A21);flex-shrink:0}

/* ── BACK ──────────────────────────────────────────── */
.card-back{width:338px;height:213px;border-radius:12px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.18);background:var(--brand-navy,#071E45);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:14px 20px;gap:8px;page-break-inside:avoid}
.back-qr-wrap{background:white;border-radius:10px;padding:9px 9px 6px;display:inline-flex;flex-direction:column;align-items:center;gap:4px}
.back-qr-img{width:96px;height:96px}
.back-qr-lbl{font-size:7px;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.08em;text-align:center}
.back-inst{text-align:center}
.back-inst p{font-size:8px;color:rgba(255,255,255,.5);line-height:1.7}
.back-inst strong{color:rgba(255,255,255,.85);font-weight:700}

/* Proxy clockin button (screen only) */
.proxy-section{background:white;border:1px solid #E2E8F0;border-radius:10px;padding:14px 18px;max-width:338px;width:100%;text-align:center}
.proxy-title{font-size:13px;font-weight:700;color:#1E293B;margin-bottom:4px}
.proxy-desc{font-size:11px;color:#64748B;margin-bottom:12px;line-height:1.5}

@media print{
    body{background:white;padding:0}
    .ctrl,.face-label,.proxy-section{display:none!important}
    .card-wrap{gap:0}
    .id-card,.card-back{box-shadow:none;width:85.6mm;height:54mm;border-radius:4mm;margin:0}
    .id-card{page-break-after:always}
    @page{size:A4;margin:12mm}
}
</style>
</head>
<body>

{{-- Controls --}}
<div class="ctrl">
    <h2>Staff ID Card — {{ $staff->name }}</h2>
    <button onclick="window.print()" class="btn btn-primary">🖨 Print Card</button>
    <a href="{{ route('staff-attendance.my') }}" class="btn btn-ghost">← Back</a>
</div>

<div class="card-wrap">

{{-- FRONT --}}
<div class="face-label">FRONT</div>
<div class="id-card">

    {{-- Gold accent stripe at top --}}
    <div class="accent-stripe"></div>

    {{-- Header --}}
    <div class="ch">
        <div class="ch-left">
            <div class="school-name">{{ optional(auth()->user()->tenant)->name ?? 'School Name' }}</div>
            <div class="school-tag">Staff Identification Card</div>
        </div>
        {{-- Logo top-left of header --}}
        <img src="/brand/educore-icon.svg" style="width:22px;height:22px;border-radius:4px;opacity:.9">
    </div>

    {{-- QR code fixed top-right (absolute) — overlays the header area from outside --}}
    <div style="position:relative;flex:1;overflow:hidden">
        {{-- QR badge pinned to top-right --}}
        <div style="position:absolute;top:6px;right:8px;z-index:2;display:flex;flex-direction:column;align-items:center;gap:2px">
            <div style="background:white;border:1.5px solid #E2E8F0;border-radius:6px;padding:3px">
                @if($qrBase64)
                <img src="{{ $qrBase64 }}" alt="Clock-in QR" style="width:54px;height:54px;display:block">
                @else
                <div id="qr-front" style="width:54px;height:54px"></div>
                @endif
            </div>
            <span style="font-size:6px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.06em">Scan to clock in</span>
        </div>

        {{-- Body --}}
        <div class="cb">
            {{-- Rectangular passport photo --}}
            <div class="photo-wrap">
                @if($staff->passport_photo)
                    <img src="{{ Storage::url($staff->passport_photo) }}" class="staff-photo" alt="{{ $staff->name }}">
                @else
                    <div class="staff-photo-init">{{ strtoupper(substr($staff->name,0,1)) }}</div>
                @endif
            </div>

            {{-- Staff info --}}
            <div class="si">
                <div class="si-name">{{ $staff->name }}</div>
                <div class="si-role">{{ $staff->roleLabel() }}</div>
                @if($staff->staff_id)
                <div class="si-id">ID · {{ $staff->staff_id }}</div>
                @endif
                @if($staff->department ?? null)
                <div class="si-dept">{{ $staff->department }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="cf">
        <div class="cf-left">EduCore</div>
        <div class="cf-right">Staff Digital Attendance</div>
    </div>
</div>

{{-- BACK --}}
<div class="face-label" style="margin-top:6px">BACK</div>
<div class="card-back">
    <div class="back-qr-wrap">
        @if($qrBase64)
        <img src="{{ $qrBase64 }}" class="back-qr-img" alt="Clock-in QR">
        @else
        <div id="qr-back" class="back-qr-img" style="display:flex;align-items:center;justify-content:center"></div>
        @endif
        <div class="back-qr-lbl">Scan to Clock In / Out</div>
    </div>
    <div class="back-inst">
        <p>Open <strong>EduCore</strong> on your phone<br>
        and scan this QR code to record your attendance.<br>
        <strong>Keep this card safe — do not share.</strong></p>
    </div>
</div>

{{-- Secure colleague clock-in entry point (screen only — not printed) --}}
<div class="proxy-section">
    <div class="proxy-title">Clock In a Colleague</div>
    <div class="proxy-desc">
        Return to My Attendance to capture a live photo, scan the staff ID card,
        and complete PIN or OTP verification.
    </div>
    <a href="{{ route('staff-attendance.my') }}" class="btn btn-proxy"
       style="width:100%;justify-content:center;padding:10px">
        Open Secure Clock-In Flow
    </a>
</div>

</div>
@if(!$qrBase64)
<script>
(function(){
    var url = {{ json_encode($url ?? '') }};
    if(!url) return;
    // Use Google Charts QR API as reliable fallback (public, no key needed)
    var src = 'https://chart.googleapis.com/chart?cht=qr&chl=' + encodeURIComponent(url) + '&choe=UTF-8';
    var front = document.getElementById('qr-front');
    var back  = document.getElementById('qr-back');
    if(front){ front.innerHTML = '<img src="'+src+'&chs=54x54" style="width:54px;height:54px;display:block">'; }
    if(back) { back.innerHTML  = '<img src="'+src+'&chs=96x96"  style="width:96px;height:96px;display:block">'; }
})();
</script>
@endif
</body>
</html>
