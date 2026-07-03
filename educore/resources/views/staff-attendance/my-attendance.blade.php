@extends('layouts.app')
@section('title','My Attendance')
@section('page-title','My Attendance')

@push('styles')
<style>
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.stat-card{background:white;border:1px solid var(--border);border-radius:10px;padding:16px;text-align:center;border-top:3px solid transparent}
.stat-card.early{border-top-color:#0284C7}.stat-card.present{border-top-color:var(--emerald)}.stat-card.late{border-top-color:var(--amber)}.stat-card.absent{border-top-color:var(--crimson)}
.stat-val{font-size:28px;font-weight:800}.stat-lbl{font-size:11px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.06em;margin-top:3px}
.two-col{display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse}
thead th{font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:9px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}
.badge{display:inline-flex;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px}
.b-early{background:#E0F2FE;color:#0284C7}.b-present{background:#ECFDF5;color:var(--emerald)}.b-late{background:#FFFBEB;color:var(--amber)}.b-absent{background:#FEF2F2;color:var(--crimson)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12.5px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-g{background:#F1F5F9;color:var(--midnight);border:1px solid var(--border)}.btn-sm{padding:5px 10px;font-size:11px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:var(--emerald);margin-bottom:14px}
.alert-e{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:var(--crimson);margin-bottom:14px}
.today-box{background:linear-gradient(135deg,#1E3A5F,#2563EB);border-radius:12px;padding:22px;color:white;margin-bottom:16px;text-align:center}
.today-status{font-size:42px;font-weight:900;margin-bottom:4px}
.today-time{font-size:14px;opacity:.85}
.month-nav{display:flex;align-items:center;gap:10px}
.progress-bar{height:8px;background:#F1F5F9;border-radius:20px;overflow:hidden;margin-top:6px}
.progress-fill{height:100%;border-radius:20px;transition:width 500ms}
#scanModal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;align-items:center;justify-content:center}
#scanBox{background:white;border-radius:16px;padding:28px;width:360px;max-width:90vw;text-align:center}
#qrVideo{width:100%;border-radius:10px;margin:14px 0}
@media(max-width:900px){.two-col{grid-template-columns:1fr}.stats-row{grid-template-columns:1fr 1fr}}
.nav-tab{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;font-size:12.5px;font-weight:600;border:1.5px solid var(--border);background:white;color:var(--slate);text-decoration:none;transition:all 150ms}
.nav-tab:hover{background:#F1F5F9;color:var(--midnight)}
.nav-tab.active{background:var(--indigo);border-color:var(--indigo);color:white}
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

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif

{{-- Today's status box --}}
<div class="today-box">
    @if($todayRecord && $todayRecord->clock_in_time)
        @php $statusIcons = ['early'=>'🔵','present'=>'🟢','late'=>'🟡','absent'=>'🔴']; @endphp
        <div class="today-status">{{ $statusIcons[$todayRecord->status] ?? '—' }}</div>
        <div style="font-size:18px;font-weight:700">{{ $todayRecord->statusLabel() }}</div>
        <div class="today-time">
            Clocked in at <strong>{{ \Carbon\Carbon::parse($todayRecord->clock_in_time)->format('g:i A') }}</strong>
            @if($todayRecord->clock_out_time)
                · Out: <strong>{{ \Carbon\Carbon::parse($todayRecord->clock_out_time)->format('g:i A') }}</strong>
            @endif
        </div>
        <div class="today-time" style="margin-top:4px;font-size:12px;opacity:.7">
            {{ match($todayRecord->clock_in_method) { 'qr'=>'Via QR scan','proxy'=>'Proxy by colleague','manual'=>'Manual entry','offline'=>'Offline upload', default=>'' } }}
            @if($todayRecord->geo_verified) · 📍 Location verified @endif
        </div>
        @if(!$todayRecord->clock_out_time)
        <button onclick="clockOut()" style="margin-top:14px;padding:9px 20px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:white;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer">
            Clock Out Now
        </button>
        @endif
    @else
        <div class="today-status">⏳</div>
        <div style="font-size:18px;font-weight:700">Not Clocked In Yet</div>
        <div class="today-time">{{ \Carbon\Carbon::now()->format('g:i A') }} · {{ today()->format('D d M Y') }}</div>
        <div style="display:flex;justify-content:center;gap:10px;margin-top:14px;flex-wrap:wrap">
            <button onclick="openScanner()" style="padding:10px 18px;background:white;color:#2563EB;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer">
                📱 Scan QR to Clock In
            </button>
            <button onclick="openProxyFlow()" style="padding:10px 18px;background:rgba(255,255,255,.15);color:white;border:1.5px solid rgba(255,255,255,.4);border-radius:8px;font-size:13px;font-weight:700;cursor:pointer">
                👥 Clock In for a Friend
            </button>
        </div>
    @endif
</div>

{{-- My ID Card quick link --}}
<div style="display:flex;justify-content:flex-end;margin-bottom:14px">
    <a href="{{ route('staff-attendance.id-card', auth()->id()) }}" target="_blank"
       class="btn btn-g btn-sm" style="gap:7px">
        🪪 Print My ID Card
    </a>
</div>

{{-- Pending proxy request notification --}}
@if($pendingProxies->count())
<div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:14px 16px;margin-bottom:16px;display:flex;align-items:flex-start;gap:12px">
    <div style="font-size:22px;flex-shrink:0">🔔</div>
    <div>
        <div style="font-size:13px;font-weight:700;color:#92400E;margin-bottom:4px">
            Proxy Clock-In Request — Action Required
        </div>
        @foreach($pendingProxies as $pr)
        <div style="font-size:12px;color:#B45309;margin-bottom:8px">
            <strong>{{ optional($pr->requestedBy)->name }}</strong> wants to clock you in for today.
            To authorise, <strong>tell them your 4-digit Attendance PIN</strong>.
            If you don't recognise this request, ignore it — it will expire.
        </div>
        @endforeach
        <div style="font-size:11px;color:#D97706;margin-top:4px">
            ⚠️ Only share your PIN with the person doing the clock-in on your behalf. Never share by text message.
        </div>
    </div>
</div>
@endif

{{-- Month navigation --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <div style="font-size:16px;font-weight:700;color:var(--midnight)">
        {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
    </div>
    <div class="month-nav">
        @php $prev = \Carbon\Carbon::createFromDate($year,$month,1)->subMonth(); $next = \Carbon\Carbon::createFromDate($year,$month,1)->addMonth(); @endphp
        <a href="?month={{ $prev->month }}&year={{ $prev->year }}" class="btn btn-g btn-sm">← Prev</a>
        <a href="?month={{ now()->month }}&year={{ now()->year }}" class="btn btn-g btn-sm">This Month</a>
        @if($next->lte(now()))<a href="?month={{ $next->month }}&year={{ $next->year }}" class="btn btn-g btn-sm">Next →</a>@endif
    </div>
</div>

{{-- Stats --}}
<div class="stats-row">
    <div class="stat-card early"><div class="stat-val" style="color:#0284C7">{{ $counts['early'] }}</div><div class="stat-lbl">🔵 Early</div></div>
    <div class="stat-card present"><div class="stat-val" style="color:var(--emerald)">{{ $counts['present'] }}</div><div class="stat-lbl">🟢 Present</div></div>
    <div class="stat-card late"><div class="stat-val" style="color:var(--amber)">{{ $counts['late'] }}</div><div class="stat-lbl">🟡 Late</div></div>
    <div class="stat-card absent"><div class="stat-val" style="color:var(--crimson)">{{ $counts['absent'] }}</div><div class="stat-lbl">🔴 Absent</div></div>
</div>

{{-- Punctuality bar --}}
@php
$worked = $counts['early'] + $counts['present'] + $counts['late'];
$totalDays = max(1, $worked + $counts['absent']);
$punctuality = $totalDays > 0 ? round((($counts['early']+$counts['present'])/$totalDays)*100) : 0;
@endphp
<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 18px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
            <span style="font-size:13px;font-weight:600;color:var(--midnight)">Punctuality Rate</span>
            <span style="font-size:16px;font-weight:800;color:{{ $punctuality >= 80 ? 'var(--emerald)' : ($punctuality >= 60 ? 'var(--amber)' : 'var(--crimson)') }}">{{ $punctuality }}%</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width:{{ $punctuality }}%;background:{{ $punctuality >= 80 ? 'var(--emerald)' : ($punctuality >= 60 ? 'var(--amber)' : 'var(--crimson)') }}"></div>
        </div>
        <div style="font-size:11px;color:var(--slate-light);margin-top:4px">On-time arrivals (Early + Present) ÷ total working days</div>
    </div>
</div>

{{-- Record table --}}
<div class="card">
    <div class="ch">Attendance History — {{ \Carbon\Carbon::createFromDate($year,$month,1)->format('F Y') }}</div>
    <div class="tbl"><table>
        <thead><tr><th>Date</th><th>Status</th><th>Clock In</th><th>Clock Out</th><th>Duration</th><th>Method</th></tr></thead>
        <tbody>
        @forelse($records as $rec)
        <tr>
            <td style="font-weight:600">{{ $rec->attendance_date->format('D d M') }}</td>
            <td><span class="badge b-{{ $rec->status }}">{{ $rec->statusLabel() }}</span></td>
            <td style="font-family:monospace">{{ $rec->clock_in_time ? \Carbon\Carbon::parse($rec->clock_in_time)->format('H:i') : '—' }}</td>
            <td style="font-family:monospace;color:var(--slate-light)">{{ $rec->clock_out_time ? \Carbon\Carbon::parse($rec->clock_out_time)->format('H:i') : '—' }}</td>
            <td style="font-size:12px;color:var(--slate-light)">
                @if($rec->clock_in_time && $rec->clock_out_time)
                    @php $diff = \Carbon\Carbon::parse($rec->clock_out_time)->diffInMinutes(\Carbon\Carbon::parse($rec->clock_in_time)) @endphp
                    {{ intdiv($diff,60) }}h {{ $diff%60 }}m
                @else —
                @endif
            </td>
            <td style="font-size:11px;color:var(--slate-light)">
                {{ match($rec->clock_in_method) { 'qr'=>'📱 QR','proxy'=>'👥 Proxy','manual'=>'✏️ Manual','offline'=>'📡 Offline', default=>'—' } }}
                @if($rec->geo_verified)<span style="color:var(--emerald)">📍</span>@endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--slate-light)">No attendance records for this month.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     CLOCK IN FOR A FRIEND — 3-step modal
     Step 1: Take live photo of friend (environment camera)
     Step 2: Scan friend's ID-card QR  (same or switch mode)
     Step 3: Enter friend's PIN / OTP to confirm
═══════════════════════════════════════════════════════════════════ --}}
<div id="proxyModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:1001;align-items:center;justify-content:center">
<div style="background:white;border-radius:18px;width:380px;max-width:93vw;max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,.35)">

  {{-- Header --}}
  <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px 14px;border-bottom:1px solid #E2E8F0">
    <div>
      <div style="font-size:15px;font-weight:800;color:#1E293B">👥 Clock In for a Friend</div>
      <div id="proxyStepLabel" style="font-size:11px;color:#64748B;margin-top:2px">Step 1 of 3 — Take their photo</div>
    </div>
    <button onclick="closeProxyFlow()" style="background:none;border:none;font-size:22px;color:#94A3B8;cursor:pointer;line-height:1">×</button>
  </div>

  {{-- Step indicator --}}
  <div style="display:flex;gap:0;padding:0 20px;margin-top:14px;margin-bottom:10px">
    @foreach([['1','📷','Photo'],['2','📱','Scan QR'],['3','🔐','Verify']] as [$n,$icon,$lbl])
    <div class="proxy-step-dot" id="stepDot{{$n}}" style="flex:1;text-align:center;padding:7px 0;font-size:10px;font-weight:700;color:#94A3B8;border-bottom:2px solid #E2E8F0;transition:all .2s">
      <div style="font-size:16px">{{$icon}}</div>{{$lbl}}
    </div>
    @endforeach
  </div>

  {{-- ── STEP 1: Live photo of friend ─────────────────────────────── --}}
  <div id="proxyStep1" style="padding:16px 20px 20px">
    <div style="font-size:13px;font-weight:700;color:#1E293B;margin-bottom:4px">📷 Take a live photo of your friend</div>
    <div style="font-size:12px;color:#64748B;margin-bottom:12px;line-height:1.5">
      Point the camera at the colleague you're clocking in. This photo is stored as attendance evidence.
    </div>

    <video id="friendVideo" style="width:100%;border-radius:10px;background:#0F172A;margin-bottom:8px;display:none" autoplay playsinline></video>
    <canvas id="friendCanvas" style="display:none"></canvas>
    <div id="friendPreview" style="display:none;margin-bottom:10px">
      <img id="friendImg" style="width:100%;border-radius:10px;border:2.5px solid #059669" alt="Friend photo">
      <div style="font-size:11px;color:#059669;text-align:center;margin-top:4px;font-weight:700">✓ Photo captured</div>
    </div>
    <div id="friendPhotoUnavailable" style="display:none;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:10px 12px;font-size:12px;color:#92400E;margin-bottom:10px;line-height:1.5">
      ⚠️ Camera unavailable (HTTPS required on mobile). Use <strong>Upload a photo</strong> instead.
    </div>
    <div id="friendCameraError" style="display:none;font-size:11px;color:#DC2626;margin-bottom:8px"></div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
      <button id="friendStartCamBtn" onclick="startFriendCamera()" style="flex:1;padding:10px;background:#2563EB;color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit">📷 Open Camera</button>
      <button id="friendCaptureBtn" onclick="captureFriendPhoto()" style="display:none;flex:1;padding:10px;background:#059669;color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit">📸 Capture</button>
      <button id="friendRetakeBtn" onclick="retakeFriendPhoto()" style="display:none;flex:1;padding:10px;background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px;font-size:13px;cursor:pointer;font-family:inherit">🔄 Retake</button>
    </div>

    <button id="step1NextBtn" onclick="goToProxyStep2()" disabled style="width:100%;padding:11px;background:#2563EB;color:white;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;opacity:.45">
      Next — Scan Friend's QR →
    </button>
  </div>

  {{-- ── STEP 2: Scan friend's ID card QR ────────────────────────── --}}
  <div id="proxyStep2" style="display:none;padding:16px 20px 20px">
    <div style="font-size:13px;font-weight:700;color:#1E293B;margin-bottom:4px">📱 Scan their ID card QR code</div>
    <div style="font-size:12px;color:#64748B;margin-bottom:12px;line-height:1.5">
      Point the camera at the QR code printed on your friend's staff ID card.
    </div>

    {{-- Mode tabs --}}
    <div style="display:flex;gap:6px;margin-bottom:12px">
      <button onclick="switchProxyQrMode('camera')" id="pqmCamera" style="flex:1;padding:7px;font-size:11px;font-weight:700;border-radius:7px;border:1.5px solid #2563EB;background:#2563EB;color:white;cursor:pointer;font-family:inherit">📷 Camera</button>
      <button onclick="switchProxyQrMode('manual')" id="pqmManual" style="flex:1;padding:7px;font-size:11px;font-weight:700;border-radius:7px;border:1.5px solid #E2E8F0;background:#F8FAFC;color:#475569;cursor:pointer;font-family:inherit">⌨️ Manual</button>
    </div>

    <div id="pqrCameraDiv">
      <video id="proxyQrVideo" style="width:100%;border-radius:10px;background:#0F172A;margin-bottom:6px" autoplay playsinline></video>
      <div id="proxyQrStatus" style="font-size:12px;color:#64748B;text-align:center;margin-bottom:8px">📷 Scanning for QR code...</div>
      <div id="proxyQrHttpWarn" style="display:none;font-size:11px;color:#D97706;background:#FFFBEB;border:1px solid #FDE68A;border-radius:7px;padding:8px 10px;margin-bottom:8px;line-height:1.5">
        ⚠️ Camera unavailable on HTTP. Use <strong>Upload</strong> or <strong>Manual</strong> mode.
      </div>
    </div>
    <div id="pqrManualDiv" style="display:none">
      <div style="font-size:12px;color:#64748B;margin-bottom:8px">Paste the QR token from the ID card:</div>
      <textarea id="proxyManualToken" rows="3" style="width:100%;padding:10px;border:1px solid #E2E8F0;border-radius:8px;font-size:12px;font-family:monospace;resize:none;margin-bottom:10px" placeholder="Paste QR token..."></textarea>
      <button onclick="processProxyManualToken()" style="width:100%;padding:11px;background:#2563EB;color:white;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit">→ Use This Token</button>
    </div>

    <div id="proxyQrDetected" style="display:none;background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 12px;font-size:12px;color:#059669;font-weight:700;margin-top:8px">
      ✓ QR code detected
    </div>
    <button onclick="goToProxyStep1()" style="width:100%;padding:9px;background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px;cursor:pointer;font-size:12px;font-family:inherit;margin-top:10px">← Back</button>
  </div>

  {{-- ── STEP 3: PIN / OTP verification ──────────────────────────── --}}
  <div id="proxyStep3" style="display:none;padding:16px 20px 20px">
    <div style="font-size:13px;font-weight:700;color:#1E293B;margin-bottom:4px" id="proxyStep3Title">🔐 Verify Identity</div>
    <div style="font-size:12px;color:#64748B;margin-bottom:14px;line-height:1.5" id="proxyStep3Desc"></div>

    <div id="proxyFriendPhotoThumb" style="display:none;text-align:center;margin-bottom:12px">
      <img id="proxyThumbImg" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #2563EB" alt="">
      <div id="proxyThumbName" style="font-size:12px;font-weight:700;color:#1E293B;margin-top:6px"></div>
    </div>

    <input type="text" id="proxyPinInput" inputmode="numeric" maxlength="6"
           style="width:100%;padding:14px;font-size:26px;letter-spacing:.45em;text-align:center;font-weight:700;border:1.5px solid #E2E8F0;border-radius:10px;margin-bottom:10px;font-family:monospace"
           placeholder="••••" autocomplete="off">

    <div id="proxyVerifyError" style="display:none;background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:10px;font-size:12px;color:#DC2626;margin-bottom:10px"></div>

    <button onclick="submitProxyVerify()" id="proxyVerifyBtn" style="width:100%;padding:12px;background:#059669;color:white;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit">
      ✓ Confirm Clock In
    </button>
    <button onclick="closeProxyFlow()" style="width:100%;padding:9px;background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px;cursor:pointer;font-size:12px;font-family:inherit;margin-top:8px">Cancel</button>
  </div>

  {{-- ── SUCCESS ───────────────────────────────────────────────────── --}}
  <div id="proxySuccess" style="display:none;padding:28px 20px;text-align:center">
    <div style="font-size:52px;margin-bottom:10px">✅</div>
    <div style="font-size:16px;font-weight:800;color:#059669;margin-bottom:6px" id="proxySuccessMsg"></div>
    <div style="font-size:12px;color:#64748B">This page will reload in a moment…</div>
  </div>

</div>
</div>

{{-- QR Scanner Modal --}}
<div id="scanModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;align-items:center;justify-content:center">
    <div id="scanBox" style="background:white;border-radius:16px;padding:28px;width:360px;max-width:90vw;text-align:center">
        <div style="font-size:16px;font-weight:800;color:#1E293B;margin-bottom:6px">📱 Scan QR Code</div>
        <div style="font-size:12px;color:#64748B;margin-bottom:14px">Point your camera at the QR displayed on screen</div>
        {{-- Mode tabs --}}
    <div style="display:flex;gap:6px;margin-bottom:14px">
        <button onclick="switchMode('camera')" id="modeCamera"
                style="flex:1;padding:8px;font-size:12px;font-weight:700;border-radius:8px;border:1.5px solid #2563EB;background:#2563EB;color:white;cursor:pointer;font-family:inherit">
            📷 Camera
        </button>
        <button onclick="switchMode('photo')" id="modePhoto"
                style="flex:1;padding:8px;font-size:12px;font-weight:700;border-radius:8px;border:1.5px solid #E2E8F0;background:#F8FAFC;color:#475569;cursor:pointer;font-family:inherit">
            🖼 Upload Photo
        </button>
        <button onclick="switchMode('manual')" id="modeManual"
                style="flex:1;padding:8px;font-size:12px;font-weight:700;border-radius:8px;border:1.5px solid #E2E8F0;background:#F8FAFC;color:#475569;cursor:pointer;font-family:inherit">
            ⌨️ Manual
        </button>
    </div>

    {{-- Camera mode --}}
    <div id="cameraModeDiv">
        <video id="qrVideo" style="width:100%;border-radius:10px;margin-bottom:8px" autoplay playsinline></video>
        <div id="scanStatus" style="font-size:12px;color:#64748B;margin-bottom:10px;text-align:center">📷 Point at the QR code to scan...</div>
        <div id="httpWarning" style="display:none;font-size:11px;color:#D97706;background:#FFFBEB;border:1px solid #FDE68A;border-radius:7px;padding:8px 10px;margin-bottom:8px;line-height:1.5">
            ⚠️ Camera blocked — your browser requires HTTPS for camera access on mobile.
            Use <strong>Upload Photo</strong> or <strong>Manual</strong> mode instead.
        </div>
    </div>

    {{-- Upload QR photo mode (works on HTTP!) --}}
    <div id="photoModeDiv" style="display:none;text-align:center;padding:8px 0">
        <div style="font-size:13px;color:#475569;margin-bottom:12px;line-height:1.6">
            Take a photo of the QR code displayed on screen,<br>then upload it here.
        </div>
        <label style="display:inline-flex;align-items:center;gap:7px;padding:12px 20px;background:#2563EB;color:white;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer">
            🖼 Select QR Photo
            <input type="file" id="qrPhotoInput" accept="image/*" capture="environment" style="display:none" onchange="readQrFromPhoto(this)">
        </label>
        <div id="photoScanStatus" style="margin-top:10px;font-size:13px;color:#64748B"></div>
        <canvas id="photoCanvas" style="display:none"></canvas>
    </div>

    {{-- Manual token mode --}}
    <div id="manualModeDiv" style="display:none">
        <div style="font-size:12px;color:#64748B;margin-bottom:10px;line-height:1.6">
            Find the QR token text from the display screen or your ID card QR, and paste it below.
        </div>
        <textarea id="manualToken" rows="3"
                  style="width:100%;padding:10px;border:1px solid #E2E8F0;border-radius:8px;font-size:12px;font-family:monospace;resize:none;margin-bottom:10px"
                  placeholder="Paste QR token here..."></textarea>
        <button onclick="processManualToken()"
                style="width:100%;padding:11px;background:#2563EB;color:white;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit">
            → Clock In
        </button>
    </div>

    {{-- Selfie step (shown after QR detected in any mode) --}}
    <div id="selfieStep" style="display:none;margin-top:12px">
        <div style="font-size:13px;font-weight:700;color:#1E293B;margin-bottom:6px">📸 Take a Selfie</div>
        <div style="font-size:11px;color:#64748B;margin-bottom:10px">Required for verification. Face the camera.</div>
        <video id="selfieVideo" style="width:100%;border-radius:10px;margin-bottom:8px;display:none" autoplay playsinline></video>
        <canvas id="selfieCanvas" style="display:none"></canvas>
        <div id="selfiePreview" style="display:none;margin-bottom:10px">
            <img id="selfieImg" style="width:100%;border-radius:10px;border:2px solid #059669" alt="Your selfie">
        </div>
        {{-- Selfie not available fallback --}}
        <div id="selfieUnavailable" style="display:none;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:10px;font-size:12px;color:#64748B;margin-bottom:10px">
            Camera unavailable for selfie — will clock in without photo.
        </div>
        <div style="display:flex;gap:8px">
            <button id="captureBtn" onclick="captureSelfie()" style="flex:1;padding:10px;background:#2563EB;color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;display:none">📸 Capture</button>
            <button id="retakeBtn" onclick="retakeSelfie()" style="flex:1;padding:10px;background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px;font-size:13px;cursor:pointer;display:none">🔄 Retake</button>
            <button id="confirmClockInBtn" onclick="confirmClockIn()" style="flex:1;padding:10px;background:#059669;color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;display:none">✓ Clock In</button>
            <button id="skipSelfieBtn" onclick="confirmClockIn()" style="flex:1;padding:10px;background:#059669;color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;display:none">✓ Clock In (no photo)</button>
        </div>
    </div>

    <div id="scanResult" style="display:none;padding:12px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:12px;margin-top:10px"></div>
    <button onclick="closeScanner()" id="cancelScanBtn" style="width:100%;padding:9px;background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px;cursor:pointer;font-size:13px;font-family:inherit;margin-top:10px">Cancel</button>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
let stream = null, scanInterval = null;
const CLOCKIN_URL = '{{ route("staff-attendance.api.clockin") }}';
const CLOCKOUT_URL = '{{ route("staff-attendance.api.clockout") }}';
const CSRF = '{{ csrf_token() }}';

// ── Scanner mode management ──────────────────────────────────────────
let currentMode = 'camera';
function switchMode(mode) {
    currentMode = mode;
    ['camera','photo','manual'].forEach(m => {
        document.getElementById(m+'ModeDiv').style.display = m === mode ? 'block' : 'none';
        const btn = document.getElementById('mode'+m.charAt(0).toUpperCase()+m.slice(1));
        btn.style.background  = m === mode ? '#2563EB' : '#F8FAFC';
        btn.style.color       = m === mode ? 'white'   : '#475569';
        btn.style.borderColor = m === mode ? '#2563EB' : '#E2E8F0';
    });
    if (mode === 'camera') startCameraScanner();
    else stopCamera();
}

// ── Camera mode ───────────────────────────────────────────────────────
async function openScanner() {
    document.getElementById('scanModal').style.display = 'flex';
    document.getElementById('selfieStep').style.display = 'none';
    document.getElementById('scanResult').style.display = 'none';
    switchMode('camera');
}

async function startCameraScanner() {
    const video = document.getElementById('qrVideo');
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        document.getElementById('httpWarning').style.display = 'block';
        document.getElementById('scanStatus').textContent = '';
        return;
    }
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment', width:{ideal:1280}, height:{ideal:720} } });
        video.srcObject = stream;
        await video.play();
        document.getElementById('scanStatus').textContent = '📷 Scanning...';
        startScanning(video);
    } catch(e) {
        document.getElementById('httpWarning').style.display = 'block';
        document.getElementById('scanStatus').textContent = '';
        console.warn('Camera error:', e.name, e.message);
    }
}

function startScanning(video) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    scanInterval = setInterval(() => {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(img.data, img.width, img.height);
            if (code) {
                clearInterval(scanInterval);
                processQrScan(code.data);
            }
        }
    }, 250);
}

// ── Upload QR photo mode (works on HTTP!) ─────────────────────────────
async function readQrFromPhoto(input) {
    const file = input.files[0];
    if (!file) return;
    const statusEl = document.getElementById('photoScanStatus');
    statusEl.textContent = 'Reading QR code from photo...';
    statusEl.style.color = '#64748B';

    const img = new Image();
    img.onload = function() {
        const canvas = document.getElementById('photoCanvas');
        canvas.width  = img.width;
        canvas.height = img.height;
        canvas.getContext('2d').drawImage(img, 0, 0);
        const imageData = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });
        if (code) {
            statusEl.textContent = '✓ QR code found!';
            statusEl.style.color = '#059669';
            processQrScan(code.data);
        } else {
            statusEl.textContent = '✗ Could not read QR code. Try a clearer photo.';
            statusEl.style.color = '#DC2626';
        }
    };
    img.src = URL.createObjectURL(file);
}

// ── Manual token mode ─────────────────────────────────────────────────
function processManualToken() {
    const token = document.getElementById('manualToken').value.trim();
    if (!token) { alert('Please paste a token first.'); return; }
    processQrScan(token);
}

// State for the 2-step QR → Selfie flow
let pendingToken = null, pendingLat = null, pendingLng = null;
let selfieStream = null, capturedPhoto = null;

async function processQrScan(data) {
    document.getElementById('scanStatus').textContent = '✓ QR detected! Starting camera for selfie...';
    stopCamera(); // stop QR scanner camera

    // Extract token from URL if full URL was encoded
    pendingToken = data;
    try { const u = new URL(data); pendingToken = u.searchParams.get('qr_token') || u.searchParams.get('token') || data; } catch(e) {}

    // Get GPS in background
    pendingLat = null; pendingLng = null;
    try {
        const pos = await new Promise((res,rej) => navigator.geolocation.getCurrentPosition(res,rej,{timeout:5000}));
        pendingLat = pos.coords.latitude; pendingLng = pos.coords.longitude;
    } catch(e) {}

    // Save offline queue entry
    saveOffline({ user_id: {{ auth()->id() }}, attendance_date: new Date().toISOString().slice(0,10),
                  clock_in_time: new Date().toTimeString().slice(0,8), qr_token: pendingToken, lat: pendingLat, lng: pendingLng });

    // Switch to selfie step
    document.getElementById('selfieStep').style.display = 'block';
    // Hide mode tabs while in selfie step
    document.getElementById('cameraModeDiv').style.display = 'none';
    document.getElementById('photoModeDiv').style.display  = 'none';
    document.getElementById('manualModeDiv').style.display = 'none';

    // Try selfie camera (front-facing)
    const selfieAvailable = navigator.mediaDevices && navigator.mediaDevices.getUserMedia;
    if (selfieAvailable) {
        try {
            selfieStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
            const sv = document.getElementById('selfieVideo');
            sv.style.display = 'block';
            sv.srcObject = selfieStream;
            await sv.play();
            document.getElementById('captureBtn').style.display = 'block';
        } catch(e) {
            // Camera blocked (HTTP) or unavailable — offer skip
            document.getElementById('selfieUnavailable').style.display = 'block';
            document.getElementById('skipSelfieBtn').style.display = 'block';
        }
    } else {
        document.getElementById('selfieUnavailable').style.display = 'block';
        document.getElementById('skipSelfieBtn').style.display = 'block';
    }
}

function captureSelfie() {
    const video  = document.getElementById('selfieVideo');
    const canvas = document.getElementById('selfieCanvas');
    canvas.width  = video.videoWidth  || 320;
    canvas.height = video.videoHeight || 240;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    capturedPhoto = canvas.toDataURL('image/jpeg', 0.75);

    // Show preview
    document.getElementById('selfieImg').src = capturedPhoto;
    document.getElementById('selfiePreview').style.display = 'block';
    document.getElementById('selfieVideo').style.display   = 'none';
    document.getElementById('captureBtn').style.display    = 'none';
    document.getElementById('retakeBtn').style.display     = 'block';
    document.getElementById('confirmClockInBtn').style.display = 'block';

    // Stop selfie stream preview (keep captured frame)
    if (selfieStream) selfieStream.getTracks().forEach(t => t.stop());
}

function retakeSelfie() {
    capturedPhoto = null;
    document.getElementById('selfiePreview').style.display = 'none';
    document.getElementById('retakeBtn').style.display     = 'none';
    document.getElementById('confirmClockInBtn').style.display = 'none';

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } }).then(s => {
        selfieStream = s;
        const sv = document.getElementById('selfieVideo');
        sv.style.display = 'block'; sv.srcObject = s; sv.play();
        document.getElementById('captureBtn').style.display = 'block';
    });
}

async function confirmClockIn() {
    document.getElementById('confirmClockInBtn').disabled = true;
    document.getElementById('confirmClockInBtn').textContent = 'Clocking in...';
    await submitClockIn(capturedPhoto);
}

async function submitClockIn(photo) {
    try {
        const resp = await fetch(CLOCKIN_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ token: pendingToken, lat: pendingLat, lng: pendingLng, photo_data: photo })
        });
        const d = await resp.json();
        const box = document.getElementById('scanResult');
        box.style.display = 'block';
        box.style.background = d.ok ? '#ECFDF5' : '#FEF2F2';
        box.style.color = d.ok ? '#059669' : '#DC2626';
        box.textContent = d.message;
        document.getElementById('selfieStep').style.display = 'none';
        if (d.ok) setTimeout(() => location.reload(), 1800);
    } catch(e) {
        const box = document.getElementById('scanResult');
        box.style.display = 'block'; box.style.background = '#FFFBEB'; box.style.color = '#D97706';
        box.textContent = 'Saved offline. Will sync when connected.';
    }
}

function closeScanner() {
    stopCamera();
    if (selfieStream) { selfieStream.getTracks().forEach(t=>t.stop()); selfieStream = null; }
    clearInterval(scanInterval);
    document.getElementById('scanModal').style.display     = 'none';
    document.getElementById('selfieStep').style.display    = 'none';
    document.getElementById('scanResult').style.display    = 'none';
    document.getElementById('httpWarning').style.display   = 'none';
    document.getElementById('selfieUnavailable').style.display = 'none';
    document.getElementById('selfiePreview').style.display = 'none';
    document.getElementById('selfieVideo').style.display   = 'none';
    ['captureBtn','retakeBtn','confirmClockInBtn','skipSelfieBtn'].forEach(id => {
        document.getElementById(id).style.display = 'none';
    });
    document.getElementById('manualToken').value         = '';
    document.getElementById('photoScanStatus').textContent = '';
    document.getElementById('scanStatus').textContent    = '📷 Point at the QR code to scan...';
    capturedPhoto = null; pendingToken = null;
    // Reset to camera mode UI
    switchMode('camera');
}
function stopCamera() {
    clearInterval(scanInterval);
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
}

// ── Clock Out ─────────────────────────────────────────────────────────
async function clockOut() {
    if (!confirm('Clock out now?')) return;
    const resp = await fetch(CLOCKOUT_URL, {
        method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','Content-Type':'application/json'}
    });
    const data = await resp.json();
    alert(data.message);
    if (data.ok) location.reload();
}

// ── Offline Queue (IndexedDB) ─────────────────────────────────────────
const OFFLINE_DB_NAME  = 'smsAttendance';
const OFFLINE_DB_VER   = 2;
const OFFLINE_STORE    = 'queue';

function openOfflineDb() {
    return new Promise((resolve, reject) => {
        if (!window.indexedDB) {
            reject(new Error('IndexedDB is not supported in this browser.'));
            return;
        }

        const req = indexedDB.open(OFFLINE_DB_NAME, OFFLINE_DB_VER);

        req.onupgradeneeded = e => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains(OFFLINE_STORE)) {
                db.createObjectStore(OFFLINE_STORE, { keyPath: 'id', autoIncrement: true });
            }
        };

        req.onsuccess = e => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains(OFFLINE_STORE)) {
                db.close();
                indexedDB.deleteDatabase(OFFLINE_DB_NAME);
                reject(new Error('Offline store was missing. Database reset; reload the page.'));
                return;
            }
            resolve(db);
        };

        req.onerror = e => reject(e.target.error || new Error('Could not open offline database.'));
        req.onblocked = () => reject(new Error('Offline database upgrade is blocked. Close other tabs and reload.'));
    });
}

async function saveOffline(record) {
    try {
        const db = await openOfflineDb();
        const tx = db.transaction(OFFLINE_STORE, 'readwrite');
        tx.objectStore(OFFLINE_STORE).add(record);
        tx.oncomplete = () => db.close();
        tx.onerror = () => db.close();
    } catch (e) {
        console.warn('Offline save failed:', e);
    }
}

// Upload offline queue when online
async function syncOffline() {
    try {
        const db = await openOfflineDb();
        const tx = db.transaction(OFFLINE_STORE, 'readwrite');
        const store = tx.objectStore(OFFLINE_STORE);
        const getAll = store.getAll();

        getAll.onsuccess = async () => {
            const records = getAll.result || [];
            if (!records.length) {
                db.close();
                return;
            }

            try {
                const resp = await fetch('{{ route("staff-attendance.api.offline") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ records })
                });

                const data = await resp.json().catch(() => ({}));
                if (resp.ok && data.ok) {
                    store.clear();
                    console.log('Offline records synced');
                }
            } catch (e) {
                console.warn('Offline sync failed:', e);
            } finally {
                db.close();
            }
        };

        getAll.onerror = () => db.close();
    } catch (e) {
        console.warn('Offline queue unavailable:', e);
    }
}

window.addEventListener('online', syncOffline);
window.addEventListener('load', () => {
    if (navigator.onLine) syncOffline();
});

// Auto-process QR token if embedded in URL (from QR display scan)
(function() {
    const params = new URLSearchParams(window.location.search);
    const token = params.get('qr_token');
    if (token) {
        // Remove param from URL without reload
        const clean = window.location.pathname;
        window.history.replaceState({}, '', clean);
        // Process the scan
        processQrScan(token);
        openScanner(); // show modal with result
    }
})();

// ══════════════════════════════════════════════════════════════════════
// CLOCK IN FOR A FRIEND — 3-step proxy flow
// Step 1: live photo of friend  (environment camera or file upload)
// Step 2: scan friend's ID card QR  (camera / upload / manual)
// Step 3: friend's PIN or OTP  → POST /api/proxy/initiate → /verify
// ══════════════════════════════════════════════════════════════════════
const PROXY_INITIATE_URL = '{{ route("staff-attendance.api.proxy.initiate") }}';
const PROXY_VERIFY_URL   = '{{ route("staff-attendance.api.proxy.verify") }}';

let friendPhotoData  = null;   // captured base64 jpeg of the friend
let proxyQrToken     = null;   // token from friend's ID card QR
let proxyRequestId   = null;   // returned by initiateProxy
let proxyTargetName  = null;   // friend's name, returned by initiateProxy
let proxyMethod      = null;   // 'pin' or 'otp'
let friendCamStream  = null;   // live camera for friend photo step
let proxyQrStream    = null;   // camera for QR scan step
let proxyQrInterval  = null;   // setInterval handle for QR scan loop

// ── Step indicator ────────────────────────────────────────────────────
function setProxyStep(n) {
    [1,2,3].forEach(i => {
        const d = document.getElementById('stepDot' + i);
        d.style.color       = i === n ? '#2563EB' : (i < n ? '#059669' : '#94A3B8');
        d.style.borderColor = i === n ? '#2563EB' : (i < n ? '#059669' : '#E2E8F0');
        d.style.fontWeight  = i === n ? '800' : '700';
    });
    const labels = ['Step 1 of 3 — Take their photo','Step 2 of 3 — Scan friend\'s QR code','Step 3 of 3 — Enter PIN or OTP'];
    document.getElementById('proxyStepLabel').textContent = labels[n-1];
    ['proxyStep1','proxyStep2','proxyStep3','proxySuccess'].forEach((id, idx) => {
        document.getElementById(id).style.display = (idx + 1 === n) ? 'block' : 'none';
    });
}

// ── Open / close ──────────────────────────────────────────────────────
function openProxyFlow() {
    friendPhotoData = proxyQrToken = proxyRequestId = proxyTargetName = proxyMethod = null;
    document.getElementById('proxyModal').style.display = 'flex';
    setProxyStep(1);
    // Reset step 1 UI
    ['friendVideo','friendPreview','friendPhotoUnavailable','friendCameraError'].forEach(id => {
        document.getElementById(id).style.display = 'none';
    });
    ['friendCaptureBtn','friendRetakeBtn'].forEach(id => document.getElementById(id).style.display = 'none');
    document.getElementById('friendStartCamBtn').style.display = 'block';
    document.getElementById('step1NextBtn').disabled = true;
    document.getElementById('step1NextBtn').style.opacity = '.45';
    document.getElementById('proxyQrDetected').style.display = 'none';
    document.getElementById('proxyVerifyError').style.display = 'none';
    document.getElementById('proxyPinInput').value = '';
}

function closeProxyFlow() {
    stopFriendCamera();
    stopProxyQrCamera();
    document.getElementById('proxyModal').style.display = 'none';
}

// ── STEP 1: Live photo of friend ──────────────────────────────────────
async function startFriendCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        document.getElementById('friendPhotoUnavailable').style.display = 'block';
        document.getElementById('friendStartCamBtn').style.display = 'none';
        return;
    }
    try {
        friendCamStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
        });
        const v = document.getElementById('friendVideo');
        v.srcObject = friendCamStream;
        v.style.display = 'block';
        await v.play();
        document.getElementById('friendStartCamBtn').style.display = 'none';
        document.getElementById('friendCaptureBtn').style.display  = 'block';
        document.getElementById('friendCameraError').style.display = 'none';
    } catch(e) {
        document.getElementById('friendPhotoUnavailable').style.display = 'block';
        document.getElementById('friendStartCamBtn').style.display = 'none';
        document.getElementById('friendCameraError').textContent = e.name === 'NotAllowedError'
            ? 'Camera permission denied. Upload a photo instead.' : 'Camera unavailable: ' + e.message;
        document.getElementById('friendCameraError').style.display = 'block';
    }
}

function captureFriendPhoto() {
    const video  = document.getElementById('friendVideo');
    const canvas = document.getElementById('friendCanvas');
    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    friendPhotoData = canvas.toDataURL('image/jpeg', 0.82);
    stopFriendCamera();
    document.getElementById('friendImg').src = friendPhotoData;
    document.getElementById('friendPreview').style.display = 'block';
    document.getElementById('friendVideo').style.display   = 'none';
    document.getElementById('friendCaptureBtn').style.display = 'none';
    document.getElementById('friendRetakeBtn').style.display  = 'block';
    unlockStep1Next();
}

function retakeFriendPhoto() {
    friendPhotoData = null;
    document.getElementById('friendPreview').style.display   = 'none';
    document.getElementById('friendRetakeBtn').style.display = 'none';
    document.getElementById('step1NextBtn').disabled = true;
    document.getElementById('step1NextBtn').style.opacity = '.45';
    startFriendCamera();
}

function readFriendPhotoFromFile(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        friendPhotoData = e.target.result;
        document.getElementById('friendImg').src = friendPhotoData;
        document.getElementById('friendPreview').style.display   = 'block';
        document.getElementById('friendRetakeBtn').style.display = 'block';
        document.getElementById('friendStartCamBtn').style.display = 'none';
        document.getElementById('friendCaptureBtn').style.display  = 'none';
        unlockStep1Next();
    };
    reader.readAsDataURL(file);
}

function unlockStep1Next() {
    document.getElementById('step1NextBtn').disabled = false;
    document.getElementById('step1NextBtn').style.opacity = '1';
}

function stopFriendCamera() {
    if (friendCamStream) { friendCamStream.getTracks().forEach(t => t.stop()); friendCamStream = null; }
}

// ── STEP 2: Scan friend's ID card QR ─────────────────────────────────
function goToProxyStep2() {
    stopFriendCamera();
    setProxyStep(2);
    document.getElementById('proxyQrDetected').style.display = 'none';
    switchProxyQrMode('camera');
}

function goToProxyStep1() {
    stopProxyQrCamera();
    setProxyStep(1);
}

function switchProxyQrMode(mode) {
    stopProxyQrCamera();
    ['camera','upload','manual'].forEach(m => {
        const el = document.getElementById('pqr'+m.charAt(0).toUpperCase()+m.slice(1)+'Div');
        if(el) el.style.display = m === mode ? 'block' : 'none';
        const btn = document.getElementById('pqm'+m.charAt(0).toUpperCase()+m.slice(1));
        if(btn) { btn.style.background = m===mode?'#2563EB':'#F8FAFC'; btn.style.color=m===mode?'white':'#475569'; btn.style.borderColor=m===mode?'#2563EB':'#E2E8F0'; }
    });
    if (mode === 'camera') startProxyQrCamera();
}

async function startProxyQrCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        document.getElementById('proxyQrHttpWarn').style.display = 'block';
        document.getElementById('proxyQrStatus').textContent = '';
        return;
    }
    try {
        proxyQrStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
        });
        const v = document.getElementById('proxyQrVideo');
        v.srcObject = proxyQrStream; v.style.display = 'block';
        await v.play();
        document.getElementById('proxyQrStatus').textContent = '📷 Scanning ID card QR…';
        startProxyQrScan(v);
    } catch(e) {
        document.getElementById('proxyQrHttpWarn').style.display = 'block';
        document.getElementById('proxyQrStatus').textContent = '';
    }
}

function startProxyQrScan(video) {
    const canvas = document.createElement('canvas');
    const ctx    = canvas.getContext('2d');
    proxyQrInterval = setInterval(() => {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth; canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imgData.data, imgData.width, imgData.height);
            if (code) {
                stopProxyQrCamera();
                processProxyQrData(code.data);
            }
        }
    }, 250);
}

function readProxyQrFromPhoto(input) {
    const file = input.files[0];
    if (!file) return;
    const st = document.getElementById('proxyQrUploadStatus');
    st.textContent = 'Reading QR…'; st.style.color = '#64748B';
    const img = new Image();
    img.onload = () => {
        const c = document.getElementById('proxyQrCanvas');
        c.width = img.width; c.height = img.height;
        c.getContext('2d').drawImage(img, 0, 0);
        const d = c.getContext('2d').getImageData(0, 0, c.width, c.height);
        const code = jsQR(d.data, d.width, d.height, { inversionAttempts: 'dontInvert' });
        if (code) { st.textContent = '✓ QR found!'; st.style.color='#059669'; processProxyQrData(code.data); }
        else       { st.textContent = '✗ No QR code found — try a clearer photo.'; st.style.color='#DC2626'; }
    };
    img.src = URL.createObjectURL(file);
}

function processProxyManualToken() {
    const t = document.getElementById('proxyManualToken').value.trim();
    if (!t) { alert('Please paste a token first.'); return; }
    processProxyQrData(t);
}

function processProxyQrData(rawData) {
    // Extract token — the QR may encode a full URL or just the token string
    let token = rawData;
    try { const u = new URL(rawData); token = u.searchParams.get('qr_token') || u.searchParams.get('token') || rawData; } catch(e) {}

    // Parse staff uid from the personal QR payload.
    // Payload is base64(JSON) with {uid, tid, sid, sig} — this is a personal ID card QR.
    let staffId = null;
    try {
        const decoded = JSON.parse(atob(token));
        if (decoded && decoded.uid) staffId = parseInt(decoded.uid);
    } catch(e) {
        // Not base64-JSON — might be a URL-encoded screen token; that means it's NOT an ID card QR
    }

    if (!staffId) {
        document.getElementById('proxyQrStatus').textContent = '✗ Not a valid staff ID card QR. Make sure you\'re scanning the ID card, not the display screen.';
        document.getElementById('proxyQrStatus').style.color = '#DC2626';
        return;
    }

    proxyQrToken = token;
    document.getElementById('proxyQrDetected').style.display   = 'block';
    document.getElementById('proxyQrDetected').dataset.staffId = staffId;
    document.getElementById('proxyQrStatus').textContent = '✓ ID card QR detected';
    document.getElementById('proxyQrStatus').style.color = '#059669';

    // Auto-advance to step 3 after brief delay
    setTimeout(() => goToProxyStep3(token, staffId), 700);
}

function stopProxyQrCamera() {
    clearInterval(proxyQrInterval);
    if (proxyQrStream) { proxyQrStream.getTracks().forEach(t => t.stop()); proxyQrStream = null; }
}

// ── STEP 3: Initiate proxy (send photo + QR) then show PIN/OTP field ─
async function goToProxyStep3(token, staffId) {
    stopProxyQrCamera();
    setProxyStep(3);
    document.getElementById('proxyStep3Title').textContent = '🔐 Verifying…';
    document.getElementById('proxyStep3Desc').textContent  = 'Sending photo and QR token for verification…';
    document.getElementById('proxyVerifyBtn').style.display = 'none';
    document.getElementById('proxyVerifyError').style.display = 'none';
    document.getElementById('proxyPinInput').style.display = 'none';
    document.getElementById('proxyFriendPhotoThumb').style.display = 'none';

    try {
        const resp = await fetch(PROXY_INITIATE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ staff_id: staffId, token: token, friend_photo: friendPhotoData })
        });
        const data = await resp.json();

        if (!data.ok) {
            document.getElementById('proxyStep3Title').textContent = '⚠️ Cannot proceed';
            document.getElementById('proxyStep3Desc').textContent  = data.message || 'Unable to initiate proxy clock-in.';
            return;
        }

        proxyRequestId  = data.request_id;
        proxyMethod     = data.method;
        proxyTargetName = data.target_name;

        // Show friend photo thumbnail + name
        if (friendPhotoData) {
            document.getElementById('proxyThumbImg').src = friendPhotoData;
            document.getElementById('proxyThumbName').textContent = proxyTargetName;
            document.getElementById('proxyFriendPhotoThumb').style.display = 'block';
        }

        const typeLabel = proxyMethod === 'pin' ? '4-digit Attendance PIN' : '6-digit OTP';
        document.getElementById('proxyStep3Title').textContent = `🔐 Enter ${typeLabel}`;
        document.getElementById('proxyStep3Desc').textContent  = data.message;
        document.getElementById('proxyPinInput').placeholder   = proxyMethod === 'pin' ? '••••' : '• • • • • •';
        document.getElementById('proxyPinInput').maxLength     = proxyMethod === 'pin' ? 4 : 6;
        document.getElementById('proxyPinInput').style.display = 'block';
        document.getElementById('proxyVerifyBtn').style.display = 'block';
        document.getElementById('proxyVerifyBtn').textContent  = `✓ Confirm Clock In for ${proxyTargetName}`;
        document.getElementById('proxyPinInput').focus();

    } catch(e) {
        document.getElementById('proxyStep3Title').textContent = '⚠️ Network error';
        document.getElementById('proxyStep3Desc').textContent  = 'Could not connect. Please try again.';
    }
}

// ── Verify PIN / OTP and complete ─────────────────────────────────────
async function submitProxyVerify() {
    const code = document.getElementById('proxyPinInput').value.trim();
    if (!code || code.length < 4) { alert('Please enter the PIN or OTP first.'); return; }

    const btn = document.getElementById('proxyVerifyBtn');
    btn.disabled = true; btn.textContent = 'Verifying…';
    document.getElementById('proxyVerifyError').style.display = 'none';

    try {
        const resp = await fetch(PROXY_VERIFY_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ request_id: proxyRequestId, code: code })
        });
        const data = await resp.json();

        if (data.ok) {
            // Show success and reload
            setProxyStep(99); // hides all step panels
            ['proxyStep1','proxyStep2','proxyStep3'].forEach(id => document.getElementById(id).style.display='none');
            document.getElementById('proxySuccess').style.display = 'block';
            document.getElementById('proxySuccessMsg').textContent = `✓ ${proxyTargetName || 'Friend'} clocked in!`;
            setTimeout(() => { closeProxyFlow(); location.reload(); }, 2000);
        } else {
            const errEl = document.getElementById('proxyVerifyError');
            errEl.textContent = data.message || 'Incorrect code. Try again.';
            errEl.style.display = 'block';
            btn.disabled = false;
            btn.textContent = `✓ Confirm Clock In for ${proxyTargetName}`;
            document.getElementById('proxyPinInput').value = '';
            document.getElementById('proxyPinInput').focus();
        }
    } catch(e) {
        document.getElementById('proxyVerifyError').textContent = 'Network error. Please try again.';
        document.getElementById('proxyVerifyError').style.display = 'block';
        btn.disabled = false;
        btn.textContent = `✓ Confirm Clock In for ${proxyTargetName}`;
    }
}
</script>
@endpush
