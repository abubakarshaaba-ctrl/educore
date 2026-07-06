@extends('layouts.app')
@section('title','Staff Attendance')
@section('page-title','Staff Attendance')

@push('styles')
<style>
.top-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px}
.actions{display:flex;gap:8px;flex-wrap:wrap}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.stat-card{background:white;border:1px solid var(--border);border-radius:10px;padding:16px;text-align:center;border-top:3px solid transparent}
.stat-card.early{border-top-color:#0284C7}.stat-card.present{border-top-color:var(--emerald)}.stat-card.late{border-top-color:var(--amber)}.stat-card.absent{border-top-color:var(--crimson)}
.stat-val{font-size:28px;font-weight:800;color:var(--midnight)}.stat-lbl{font-size:11px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.06em;margin-top:3px}
.two-col{display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse}
thead th{font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:9px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:#F8FAFC}
.badge{display:inline-flex;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px}
.b-early{background:#E0F2FE;color:#0284C7}.b-present{background:#ECFDF5;color:var(--emerald)}.b-late{background:#FFFBEB;color:var(--amber)}.b-absent{background:#FEF2F2;color:var(--crimson)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12.5px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-g{background:#F1F5F9;color:var(--midnight);border:1px solid var(--border)}.btn-sm{padding:4px 10px;font-size:11px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:var(--emerald);margin-bottom:14px}
.fg{margin-bottom:12px}.fl{display:block;font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}
.fc{width:100%;padding:8px 10px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;transition:border-color 150ms}
.fc:focus{border-color:var(--indigo)}
.offline-badge{background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;border-radius:6px;padding:3px 8px;font-size:11px;font-weight:700}
@media(max-width:1024px){.two-col{grid-template-columns:1fr}.stats-row{grid-template-columns:1fr 1fr}}
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
    <a href="{{ route('staff-attendance.proxy-review') }}"
       class="nav-tab {{ request()->routeIs('staff-attendance.proxy-review') ? 'active':'' }}">
        🪪 Proxy Review
    </a>
    @endif
</div>

<div class="top-row">
    <div>
        <div style="font-size:15px;font-weight:700;color:var(--midnight)">Today — {{ \Carbon\Carbon::parse($today)->format('l, d F Y') }}</div>
        <div style="font-size:12px;color:var(--slate-light);margin-top:2px">
            Resumption: <strong>{{ substr($settings->resumption_time,0,5) }}</strong> ·
            Grace: <strong>{{ $settings->grace_minutes }}min</strong> ·
            Closing: <strong>{{ substr($settings->closing_time,0,5) }}</strong>
            @if($settings->geo_enabled) · 📍 Geo-fence active @endif
        </div>
    </div>
    <div class="actions">
        @if($pendingOffline > 0)
        <a href="{{ route('staff-attendance.offline-queue') }}" class="btn btn-g">
            <span class="offline-badge">{{ $pendingOffline }}</span> Offline Queue
        </a>
        @endif
        <a href="{{ route('staff-attendance.qr') }}" class="btn btn-p">📱 Display QR Code</a>
        <a href="{{ route('staff-attendance.report') }}" class="btn btn-g">📊 Monthly Report</a>
        <a href="{{ route('staff-attendance.settings') }}" class="btn btn-g">⚙️ Settings</a>
    </div>
</div>

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif

{{-- Today summary --}}
<div class="stats-row">
    <div class="stat-card early"><div class="stat-val" style="color:#0284C7">{{ $summary['early'] }}</div><div class="stat-lbl">🔵 Early</div></div>
    <div class="stat-card present"><div class="stat-val" style="color:var(--emerald)">{{ $summary['present'] }}</div><div class="stat-lbl">🟢 Present</div></div>
    <div class="stat-card late"><div class="stat-val" style="color:var(--amber)">{{ $summary['late'] }}</div><div class="stat-lbl">🟡 Late</div></div>
    <div class="stat-card absent"><div class="stat-val" style="color:var(--crimson)">{{ $summary['absent'] }}</div><div class="stat-lbl">🔴 Absent</div></div>
</div>

<div class="two-col">
    {{-- Left: Today's records --}}
    <div>
        <div class="card">
            <div class="ch">
                Today's Clock-ins ({{ $todayRecords->count() }}/{{ $staffTotal }})
                <a href="{{ route('staff-attendance.report') }}" class="btn btn-g btn-sm">View Monthly →</a>
            </div>
            <div class="tbl"><table>
                <thead><tr><th>Staff</th><th>Clock In</th><th>Clock Out</th><th>Status</th><th>Method</th><th>Photo</th></tr></thead>
                <tbody>
                @forelse($todayRecords->sortBy('clock_in_time') as $rec)
                <tr>
                    <td>
                        <div style="font-weight:600">{{ optional($rec->staff)->name }}</div>
                        <div style="font-size:11px;color:var(--slate-light)">{{ optional($rec->staff)->roleLabel() }}</div>
                    </td>
                    <td style="font-weight:600;font-family:monospace">
                        {{ $rec->clock_in_time ? \Carbon\Carbon::parse($rec->clock_in_time)->format('H:i') : '—' }}
                    </td>
                    <td style="font-family:monospace;color:var(--slate-light)">
                        {{ $rec->clock_out_time ? \Carbon\Carbon::parse($rec->clock_out_time)->format('H:i') : '—' }}
                    </td>
                    <td><span class="badge b-{{ $rec->status }}">{{ $rec->statusLabel() }}</span></td>
                    <td style="font-size:11px;color:var(--slate-light)">
                        {{ match($rec->clock_in_method) {
                            'qr'      => '📱 QR',
                            'proxy'   => '👥 Proxy',
                            'manual'  => '✏️ Manual',
                            'offline' => '📡 Offline',
                            default   => '—'
                        } }}
                        @if($rec->geo_verified) <span style="color:var(--emerald)">📍</span> @endif
                        @if($rec->proxy_verified) <span style="color:var(--amber)" title="Proxy verified">🔒</span> @endif
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px">
                        @if($rec->clock_in_photo)
                            <img src="{{ Storage::url($rec->clock_in_photo) }}" alt="Selfie"
                                 style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--emerald);cursor:pointer"
                                 onclick="showPhoto(this.src,'{{ addslashes(optional($rec->staff)->name) }} — Clock-in selfie')"
                                 title="Clock-in selfie — click to enlarge">
                        @elseif($rec->proxy_photo)
                            <img src="{{ Storage::url($rec->proxy_photo) }}" alt="Proxy photo"
                                 style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--amber);cursor:pointer"
                                 onclick="showPhoto(this.src,'Proxy photo — {{ addslashes(optional($rec->staff)->name) }}')"
                                 title="Proxy photo — click to enlarge">
                        @else
                            <span style="font-size:10px;color:var(--slate-light)">—</span>
                        @endif
                        @if($rec->user_id)
                        <a href="{{ route('staff-attendance.id-card', $rec->user_id) }}" target="_blank"
                           title="Print ID Card" style="color:var(--slate-light);font-size:14px;line-height:1;text-decoration:none">
                            🪪
                        </a>
                        @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--slate-light)">No clock-ins yet today.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>

    {{-- Right: Manual override + proxy --}}
    <div>
        <div class="card">
            <div class="ch">✏️ Manual Override</div>
            <div style="padding:16px">
                <form method="POST" action="{{ route('staff-attendance.manual') }}">
                @csrf
                <div class="fg"><label class="fl">Staff Member</label>
                    <select name="user_id" class="fc" required>
                        <option value="">Select staff...</option>
                        @foreach($allStaff as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select></div>
                <div class="fg"><label class="fl">Date</label>
                    <input type="date" name="attendance_date" class="fc" value="{{ $today }}" required></div>
                <div class="fg"><label class="fl">Status</label>
                    <select name="status" class="fc" required>
                        <option value="early">🔵 Early</option>
                        <option value="present" selected>🟢 Present</option>
                        <option value="late">🟡 Late</option>
                        <option value="absent">🔴 Absent</option>
                    </select></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div class="fg"><label class="fl">Clock In</label><input type="time" name="clock_in_time" class="fc"></div>
                    <div class="fg"><label class="fl">Clock Out</label><input type="time" name="clock_out_time" class="fc"></div>
                </div>
                <div class="fg"><label class="fl">Notes</label><input type="text" name="notes" class="fc" placeholder="Reason for override..."></div>
                <button type="submit" class="btn btn-p" style="width:100%;justify-content:center">Save Override</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="ch">👥 Proxy Clock-In (with Verification)</div>
            <div style="padding:16px">
                <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;padding:10px 12px;font-size:12px;color:#92400E;margin-bottom:14px;line-height:1.5">
                    🔒 <strong>Secure proxy:</strong> After you select a staff member, they must confirm via their
                    <strong>4-digit Attendance PIN</strong> or a one-time SMS code.
                    This prevents unauthorised clock-ins.
                </div>

                {{-- Step 1: Initiate --}}
                <div id="proxyStep1">
                    <div class="fg"><label class="fl">Staff Member to Clock In</label>
                        <select id="proxyStaffId" class="fc">
                            <option value="">Select staff...</option>
                            @foreach($allStaff as $s)
                            <option value="{{ $s->id }}" data-name="{{ $s->name }}" data-pin="{{ $s->attendance_pin ? '1':'0' }}">
                                {{ $s->name }} {{ $s->attendance_pin ? '🔒':'' }}
                            </option>
                            @endforeach
                        </select>
                        <div style="font-size:10px;color:var(--slate-light);margin-top:3px">🔒 = has attendance PIN set</div>
                    </div>
                    <div class="fg"><label class="fl">Today's QR Token</label>
                        <input type="text" id="proxyToken" class="fc" placeholder="Paste QR token from display screen...">
                    </div>
                    <button onclick="initiateProxy()" class="btn btn-g" style="width:100%;justify-content:center">
                        Request Proxy Clock-In →
                    </button>
                    <div id="proxyInitError" style="display:none;margin-top:8px;font-size:12px;color:var(--crimson);background:#FEF2F2;border:1px solid #FECACA;border-radius:7px;padding:8px 10px"></div>
                </div>

                {{-- Step 2: Verify PIN / OTP --}}
                <div id="proxyStep2" style="display:none">
                    <div id="proxyVerifyInfo" style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px;font-size:13px;color:var(--indigo);margin-bottom:14px;line-height:1.6"></div>
                    <div class="fg">
                        <label class="fl" id="proxyCodeLabel">Enter PIN / Code</label>
                        <input type="password" id="proxyCode" class="fc" maxlength="6"
                               placeholder="4-digit PIN or 6-digit OTP"
                               style="font-size:20px;letter-spacing:.3em;text-align:center;font-weight:700"
                               oninput="if(this.value.length>=4) document.getElementById('proxyVerifyBtn').focus()">
                        <div style="font-size:11px;color:var(--slate-light);margin-top:4px" id="proxyAttemptInfo"></div>
                    </div>
                    {{-- Proxy person selfie (proves THEY are physically present) --}}
                    <div id="proxySelfieSection" style="display:none;margin-bottom:14px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px">
                        <div style="font-size:12px;font-weight:700;color:#92400E;margin-bottom:6px">
                            📸 Take a Photo (required)
                        </div>
                        <div style="font-size:11px;color:#B45309;margin-bottom:10px;line-height:1.5">
                            A photo of <strong>you</strong> (the person holding this phone) is required to verify you are physically on school premises.
                        </div>
                        <video id="proxySelfieVideo" style="width:100%;border-radius:8px;margin-bottom:8px;display:none" autoplay playsinline></video>
                        <canvas id="proxySelfieCanvas" style="display:none"></canvas>
                        <div id="proxySelfiePreviewWrap" style="display:none;margin-bottom:8px">
                            <img id="proxySelfiePreview" style="width:100%;border-radius:8px;border:2px solid #059669">
                        </div>
                        <div style="display:flex;gap:6px">
                            <button id="proxyCaptureBtn" onclick="captureProxySelfie()" style="display:none;padding:8px 14px;background:#2563EB;color:white;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer">📸 Capture</button>
                            <button id="proxyRetakeBtn" onclick="retakeProxySelfie()" style="display:none;padding:8px 14px;background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:7px;font-size:12px;cursor:pointer">🔄 Retake</button>
                        </div>
                    </div>

                    <div style="display:flex;gap:8px">
                        <button onclick="startProxySelfie();verifyProxy()" id="proxyVerifyBtn" class="btn btn-p" style="flex:1;justify-content:center">✓ Verify & Clock In</button>
                        <button onclick="cancelProxy()" class="btn btn-g">Cancel</button>
                    </div>
                    <div id="proxyVerifyError" style="display:none;margin-top:8px;font-size:12px;color:var(--crimson);background:#FEF2F2;border:1px solid #FECACA;border-radius:7px;padding:8px 10px"></div>
                    <div id="proxyVerifySuccess" style="display:none;margin-top:8px;font-size:13px;font-weight:600;color:var(--emerald);background:#ECFDF5;border:1px solid #A7F3D0;border-radius:7px;padding:10px 12px"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Photo lightbox --}}
<div id="photoModal" onclick="this.style.display='none'"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:2000;align-items:center;justify-content:center;flex-direction:column;cursor:zoom-out">
    <img id="modalImg" style="max-width:85vw;max-height:80vh;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.5)">
    <div id="modalCaption" style="color:white;font-size:13px;margin-top:12px;font-weight:600"></div>
    <div style="color:rgba(255,255,255,0.5);font-size:11px;margin-top:6px">Click anywhere to close</div>
</div>

@push('scripts')
<script>
function showPhoto(src, caption) {
    document.getElementById('photoModal').style.display='flex';
    document.getElementById('modalImg').src=src;
    document.getElementById('modalCaption').textContent=caption;
}
const PROXY_INITIATE_URL = '{{ route("staff-attendance.api.proxy.initiate") }}';
const PROXY_VERIFY_URL   = '{{ route("staff-attendance.api.proxy.verify") }}';
const CSRF = '{{ csrf_token() }}';
let currentRequestId = null;

async function initiateProxy() {
    const staffSel = document.getElementById('proxyStaffId');
    const staffId  = staffSel.value;
    const token    = document.getElementById('proxyToken').value.trim();
    const errEl    = document.getElementById('proxyInitError');
    errEl.style.display = 'none';

    if (!staffId) { errEl.style.display='block'; errEl.textContent='Please select a staff member.'; return; }
    if (!token)   { errEl.style.display='block'; errEl.textContent='Please paste the QR token.'; return; }

    try {
        const resp = await fetch(PROXY_INITIATE_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ staff_id: parseInt(staffId), token })
        });
        const data = await resp.json();

        if (!data.ok) {
            errEl.style.display='block'; errEl.textContent=data.message; return;
        }

        // Move to step 2
        currentRequestId = data.request_id;
        document.getElementById('proxyStep1').style.display = 'none';
        document.getElementById('proxyStep2').style.display = 'block';

        const infoEl = document.getElementById('proxyVerifyInfo');
        const lblEl  = document.getElementById('proxyCodeLabel');

        if (data.method === 'pin') {
            infoEl.innerHTML = `<strong>Step 2 of 2 — ${data.target_name}</strong><br>
                Ask ${data.target_name} to <strong>tell you their 4-digit Attendance PIN</strong>. 
                Enter it below to confirm they authorise this clock-in.<br>
                <span style="font-size:11px;color:#60A5FA">⚠️ The PIN is secret — only the staff member knows it.</span>`;
            lblEl.textContent = '4-Digit Attendance PIN';
            document.getElementById('proxyCode').placeholder = '••••';
            document.getElementById('proxyCode').maxLength = 4;
        } else {
            infoEl.innerHTML = `<strong>Step 2 of 2 — ${data.target_name}</strong><br>
                An OTP has been sent to ${data.target_name}'s phone.
                Ask them to <strong>tell you the 6-digit code</strong> they received by SMS.<br>
                <span style="font-size:11px;color:#60A5FA">Code expires in 10 minutes.</span>`;
            lblEl.textContent = '6-Digit SMS OTP';
            document.getElementById('proxyCode').placeholder = '••••••';
            document.getElementById('proxyCode').maxLength = 6;
        }

        document.getElementById('proxyCode').focus();
    } catch(e) {
        errEl.style.display='block'; errEl.textContent='Network error. Please try again.';
    }
}

// Proxy selfie state
let proxySelfieStream = null, proxyCapturedPhoto = null;

async function startProxySelfie() {
    document.getElementById('proxySelfieSection').style.display = 'block';
    try {
        proxySelfieStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        const v = document.getElementById('proxySelfieVideo');
        v.style.display = 'block'; v.srcObject = proxySelfieStream; v.play();
        document.getElementById('proxyCaptureBtn').style.display = 'block';
    } catch(e) {
        document.getElementById('proxySelfieSection').innerHTML =
            '<div style="font-size:12px;color:#D97706;padding:8px">⚠️ Camera unavailable — proceeding without photo.</div>';
        proxyCapturedPhoto = null;
    }
}

function captureProxySelfie() {
    const v = document.getElementById('proxySelfieVideo');
    const c = document.getElementById('proxySelfieCanvas');
    c.width = v.videoWidth||320; c.height = v.videoHeight||240;
    c.getContext('2d').drawImage(v, 0, 0, c.width, c.height);
    proxyCapturedPhoto = c.toDataURL('image/jpeg', 0.75);
    document.getElementById('proxySelfiePreview').src = proxyCapturedPhoto;
    document.getElementById('proxySelfiePreviewWrap').style.display = 'block';
    document.getElementById('proxySelfieVideo').style.display = 'none';
    document.getElementById('proxyCaptureBtn').style.display = 'none';
    document.getElementById('proxyRetakeBtn').style.display = 'block';
    if (proxySelfieStream) proxySelfieStream.getTracks().forEach(t=>t.stop());
}

function retakeProxySelfie() {
    proxyCapturedPhoto = null;
    document.getElementById('proxySelfiePreviewWrap').style.display = 'none';
    document.getElementById('proxyRetakeBtn').style.display = 'none';
    navigator.mediaDevices.getUserMedia({video:{facingMode:'user'}}).then(s => {
        proxySelfieStream = s;
        const v = document.getElementById('proxySelfieVideo');
        v.style.display='block'; v.srcObject=s; v.play();
        document.getElementById('proxyCaptureBtn').style.display='block';
    });
}

async function verifyProxy() {
    const code   = document.getElementById('proxyCode').value.trim();
    const errEl  = document.getElementById('proxyVerifyError');
    const succEl = document.getElementById('proxyVerifySuccess');
    errEl.style.display='none'; succEl.style.display='none';

    if (!code) { errEl.style.display='block'; errEl.textContent='Please enter the PIN or OTP.'; return; }

    const btn = document.getElementById('proxyVerifyBtn');
    btn.disabled = true; btn.textContent = 'Verifying...';

    try {
        const resp = await fetch(PROXY_VERIFY_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ request_id: currentRequestId, code, photo_data: proxyCapturedPhoto })
        });
        const data = await resp.json();

        if (data.ok) {
            succEl.style.display='block';
            succEl.textContent = '✓ ' + data.message;
            document.getElementById('proxyStep2').querySelector('button:not(#proxyVerifyBtn)').style.display='none';
            btn.style.display='none';
            setTimeout(() => location.reload(), 1800);
        } else {
            errEl.style.display='block'; errEl.textContent = data.message;
            document.getElementById('proxyCode').value = '';
            document.getElementById('proxyCode').focus();
            btn.disabled = false; btn.textContent = '✓ Verify & Clock In';
        }
    } catch(e) {
        errEl.style.display='block'; errEl.textContent='Network error. Please try again.';
        btn.disabled=false; btn.textContent='✓ Verify & Clock In';
    }
}

function cancelProxy() {
    currentRequestId = null;
    document.getElementById('proxyStep1').style.display = 'block';
    document.getElementById('proxyStep2').style.display = 'none';
    document.getElementById('proxyCode').value = '';
    document.getElementById('proxyInitError').style.display = 'none';
}

// Allow Enter key in PIN field
document.addEventListener('keydown', e => {
    if (e.key === 'Enter' && document.getElementById('proxyStep2').style.display !== 'none') {
        verifyProxy();
    }
});
</script>
@endpush

@push('styles')
<style>
.nav-tab{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;font-size:12.5px;font-weight:600;border:1.5px solid var(--border);background:white;color:var(--slate);text-decoration:none;transition:all 150ms}
.nav-tab:hover{background:#F1F5F9;color:var(--midnight)}
.nav-tab.active{background:var(--indigo);border-color:var(--indigo);color:white}
</style>
@endpush
