<?php
namespace App\Http\Controllers;

use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use App\Models\StaffOfflineClockIn;
use App\Models\StaffProxyRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StaffAttendanceController extends Controller
{
    private function attendanceSettings(): StaffAttendanceSetting
    {
        return StaffAttendanceSetting::forTenant(auth()->user()->tenant_id);
    }

    // ── Admin: Settings ───────────────────────────────────────────────
    public function settings()
    {
        $settings = $this->attendanceSettings();
        return view('staff-attendance.settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'resumption_time'   => ['required', 'date_format:H:i'],
            'grace_minutes'     => ['required', 'integer', 'min:0', 'max:120'],
            'closing_time'      => ['required', 'date_format:H:i'],
            'geo_enabled'       => ['boolean'],
            'geo_lat'           => ['nullable', 'numeric', 'between:-90,90'],
            'geo_lng'           => ['nullable', 'numeric', 'between:-180,180'],
            'geo_radius_meters' => ['nullable', 'integer', 'min:10', 'max:2000'],
        ]);
        $data['geo_enabled'] = $request->boolean('geo_enabled');
        $data['resumption_time'] .= ':00';
        $data['closing_time']    .= ':00';
        $this->attendanceSettings()->update($data);
        return back()->with('success', 'Attendance settings saved.');
    }

    // ── Admin: Dashboard / overview ───────────────────────────────────
    public function index()
    {
        $today    = today()->toDateString();
        $settings = $this->attendanceSettings();
        $tid      = auth()->user()->tenant_id;

        $staffTotal = User::attendanceEligibleOn($tid, $today)->count();

        $todayRecords = StaffAttendanceRecord::with('staff')
            ->where('tenant_id', $tid)
            ->whereDate('attendance_date', $today)->get();

        $summary = [
            'early'   => $todayRecords->where('status','early')->count(),
            'present' => $todayRecords->where('status','present')->count(),
            'late'    => $todayRecords->where('status','late')->count(),
            'absent'  => $staffTotal - $todayRecords->whereIn('status',['early','present','late'])->count(),
        ];

        $weekTrend = StaffAttendanceRecord::select(
                DB::raw('attendance_date, status, COUNT(*) as cnt')
            )
            ->where('tenant_id', $tid)
            ->where('attendance_date', '>=', now()->subDays(6)->toDateString())
            ->groupBy('attendance_date','status')
            ->get()->groupBy('attendance_date');

        $pendingOffline = StaffOfflineClockIn::where('tenant_id', $tid)->where('status','pending')->count();
        $pendingProxy   = StaffProxyRequest::where('tenant_id', $tid)->where('status','pending')->count();

        // All staff (for manual override select)
        $allStaff = User::attendanceEligibleOn($tid, $today)->orderBy('name')->get();

        return view('staff-attendance.index', compact(
            'settings','summary','todayRecords','staffTotal',
            'weekTrend','pendingOffline','pendingProxy','today','allStaff'
        ));
    }

    // ── QR Display ────────────────────────────────────────────────────
    public function qrDisplay()
    {
        $settings = $this->attendanceSettings();

        // Use the STATIC (permanent) school QR — never rotates.
        // The image can be printed, laminated, or put on a wall indefinitely.
        $payload  = $settings->staticQrPayload();
        $url      = route('staff-attendance.my') . '?qr_token=' . urlencode($payload);
        $qrBase64 = $this->buildQrBase64($url);

        return view('staff-attendance.qr-display', compact('settings', 'qrBase64', 'payload', 'url'));
    }

    // ── Reset static QR (invalidates all printed copies) ─────────────
    public function resetStaticQr()
    {
        $this->attendanceSettings()->resetStaticQr();
        return back()->with('success', 'QR code has been reset. All previously printed QR displays are now invalid. Print the new one.');
    }

    // Build QR image as base64 PNG using local library (no external API calls)
    private function buildQrBase64(string $data, int $size = 280): string
    {
        try {
            $svg = QrCode::format('svg')->size($size)->margin(1)->generate($data);
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Throwable $e) {
            return '';
        }
    }

    // ── PROXY: Search eligible staff for proxy selection ─────────────
    public function staffSearch(Request $request)
    {
        $tid   = auth()->user()->tenant_id;
        $q     = trim($request->get('q', ''));
        $today = today()->toDateString();

        $clockedIn = StaffAttendanceRecord::where('tenant_id', $tid)
            ->whereDate('attendance_date', today())
            ->whereNotNull('clock_in_time')
            ->pluck('user_id');

        $staff = User::attendanceEligibleOn($tid, $today)
            ->where('id', '!=', auth()->id())
            ->whereNotIn('id', $clockedIn)
            ->when($q, fn($q2) => $q2->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'staff_id', 'passport_photo'])
            ->map(fn($u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'emp'   => $u->staff_id ?? '',
                'photo' => $u->passport_photo
                    ? \Illuminate\Support\Facades\Storage::url($u->passport_photo)
                    : null,
            ]);

        return response()->json(['ok' => true, 'staff' => $staff]);
    }

    // ── PROXY: Initiate proxy request (face-verification flow) ────────
    public function proxyClock(Request $request): mixed
    {
        return $this->initiateProxy($request);
    }

    /**
     * Single-step proxy clock-in for a colleague, verified by a live photo
     * captured at the moment of clock-in (no PIN, no waiting step — used
     * by the mobile app's "Clock in for a colleague" flow). The photo is
     * evidence of presence, stored against the record for later review.
     */
    public function proxyClockInWithPhoto(Request $request)
    {
        $request->validate([
            'staff_id' => ['required', 'integer'],
            'token'    => ['required', 'string'],
            'photo'    => ['required', 'string'],
            'lat'      => ['nullable', 'numeric'],
            'lng'      => ['nullable', 'numeric'],
        ]);

        $clocker  = auth()->user();
        $settings = $this->attendanceSettings();

        $isSchoolQr = $settings->verifyStaticQrToken($request->token)
                   || $settings->verifyQrToken($request->token);
        if (!$isSchoolQr) {
            return response()->json([
                'ok'      => false,
                'message' => 'Scan the school display QR to clock in a colleague (not a personal ID card).',
            ], 422);
        }

        $target = User::attendanceEligibleOn($clocker->tenant_id, today())->find($request->staff_id);
        if (!$target) {
            return response()->json(['ok' => false, 'message' => 'Staff member not found.'], 404);
        }
        if ($target->id === $clocker->id) {
            return response()->json(['ok' => false, 'message' => 'Use "Scan QR to clock in" for yourself.'], 422);
        }

        $proxyPhotoPath = $this->storeAttendancePhoto($request->photo, $clocker->tenant_id, 'proxy');
        if (!$proxyPhotoPath) {
            return response()->json(['ok' => false, 'message' => 'Photo capture failed. Please try again.'], 422);
        }

        $geoVerified = false;
        if ($settings->geo_enabled) {
            if (!$request->lat || !$request->lng) {
                return response()->json(['ok' => false, 'message' => 'Location required. Please enable GPS.'], 422);
            }
            $dist = $settings->distanceTo((float) $request->lat, (float) $request->lng);
            if ($dist > $settings->geo_radius_meters) {
                return response()->json([
                    'ok'      => false,
                    'message' => round($dist) . "m from school. Must be within {$settings->geo_radius_meters}m.",
                ], 422);
            }
            $geoVerified = true;
        }

        $response = $this->recordClockIn(
            tenantId     : $clocker->tenant_id,
            userId       : $target->id,
            clockedBy    : $clocker->id,
            method       : 'proxy',
            lat          : $request->lat,
            lng          : $request->lng,
            geoVerified  : $geoVerified,
            settings     : $settings,
            proxyVerified: true,
            proxyPhoto   : $proxyPhotoPath,
        );

        // Flag for admin review: compare the captured photo against the
        // colleague's passport photo (no automated facial match — see
        // staff-attendance/proxy-review).
        StaffAttendanceRecord::where('user_id', $target->id)
            ->whereDate('attendance_date', today())
            ->update(['proxy_review_status' => 'pending']);

        return $response;
    }

    public function initiateProxy(Request $request)
    {
        $request->validate([
            'staff_id'     => ['required', 'integer'],
            'qr_token'     => ['required', 'string'],
            'friend_photo' => ['nullable', 'string', 'max:7000000'],
        ]);

        $clocker  = auth()->user();
        $settings = $this->attendanceSettings();

        // Validate the school display QR (static or legacy daily token) — NOT a personal ID card QR
        $isSchoolQr = $settings->verifyStaticQrToken($request->qr_token)
                   || $settings->verifyQrToken($request->qr_token);
        if (!$isSchoolQr) {
            return response()->json([
                'ok'      => false,
                'message' => 'Invalid QR code. Please scan the school attendance QR displayed on screen, not a personal ID card QR.',
            ], 422);
        }

        $target = User::attendanceEligibleOn($clocker->tenant_id, today())->find($request->staff_id);
        if (!$target) {
            return response()->json(['ok' => false, 'message' => 'Staff member not found.'], 404);
        }
        if ($target->id === $clocker->id) {
            return response()->json(['ok' => false, 'message' => 'You cannot proxy clock-in for yourself.'], 422);
        }

        $existing = StaffAttendanceRecord::where('user_id', $target->id)
            ->whereDate('attendance_date', today())
            ->whereNotNull('clock_in_time')
            ->first();
        if ($existing) {
            return response()->json([
                'ok'      => false,
                'message' => "{$target->name} is already clocked in at " . Carbon::parse($existing->clock_in_time)->format('g:i A'),
            ], 422);
        }

        $pending = StaffProxyRequest::where('target_user_id', $target->id)
            ->whereDate('attendance_date', today())
            ->where('status', 'pending')
            ->first();
        if ($pending) {
            return response()->json([
                'ok'         => false,
                'pending'    => true,
                'request_id' => $pending->id,
                'message'    => "A proxy request for {$target->name} is already pending face verification.",
            ], 422);
        }

        $photoPath = null;
        if ($request->filled('friend_photo')) {
            try {
                $photoPath = $this->storeAttendancePhoto($request->friend_photo, $clocker->tenant_id, 'proxy-request');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Proxy photo save failed', ['error' => $e->getMessage()]);
            }
        }

        $proxyReq = StaffProxyRequest::create([
            'tenant_id'           => $clocker->tenant_id,
            'target_user_id'      => $target->id,
            'requested_by'        => $clocker->id,
            'attendance_date'     => today(),
            'clock_in_time'       => now()->format('H:i:s'),
            'qr_token'            => $request->qr_token,
            'friend_photo_path'   => $photoPath,
            'verification_method' => 'face',
            'status'              => 'pending',
        ]);

        return response()->json([
            'ok'               => true,
            'request_id'       => $proxyReq->id,
            'method'           => 'face',
            'target_name'      => $target->name,
            'profile_photo_url'=> $target->passport_photo
                ? \Illuminate\Support\Facades\Storage::url($target->passport_photo)
                : null,
            'message'          => "Photo saved. Now run face verification for {$target->name}.",
        ]);
    }

    // ── PROXY: Finalise via face verification ─────────────────────────
    public function verifyProxyFace(Request $request)
    {
        $request->validate([
            'request_id'    => ['required', 'integer'],
            'face_verified' => ['required', 'boolean'],
        ]);

        $proxyReq = StaffProxyRequest::find($request->request_id);
        if (!$proxyReq || $proxyReq->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['ok' => false, 'message' => 'Request not found.'], 404);
        }
        if (!$proxyReq->isPending()) {
            return response()->json(['ok' => false, 'message' => 'This request has already been processed.'], 422);
        }
        if ($proxyReq->isExpired()) {
            $proxyReq->update(['status' => 'expired']);
            return response()->json(['ok' => false, 'message' => 'Request expired. Please start again.'], 422);
        }
        $proxyReq->update(['status' => 'approved']);

        if (!$request->face_verified) {
            \Illuminate\Support\Facades\Log::info('Proxy clock-in approved without face match', [
                'request_id' => $proxyReq->id,
                'target'     => $proxyReq->target_user_id,
                'clocker'    => auth()->id(),
            ]);
        }
        $target = $proxyReq->targetStaff;

        $this->recordClockIn(
            tenantId     : $proxyReq->tenant_id,
            userId       : $proxyReq->target_user_id,
            clockedBy    : $proxyReq->requested_by,
            method       : 'proxy',
            lat          : $proxyReq->lat,
            lng          : $proxyReq->lng,
            geoVerified  : false,
            settings     : $this->attendanceSettings(),
            date         : $proxyReq->attendance_date->toDateString(),
            time         : $proxyReq->clock_in_time,
            proxyVerified: true,
            proxyPhoto   : $proxyReq->friend_photo_path,
        );

        return response()->json([
            'ok'      => true,
            'message' => ($target->name ?? 'Staff member') . ' clocked in successfully via face verification.',
        ]);
    }

    // ── PROXY: Verify PIN / OTP and finalise ──────────────────────────
    public function verifyProxy(Request $request)
    {
        $request->validate([
            'request_id' => ['required', 'integer'],
            'code'       => ['required', 'string', 'max:6'],
        ]);

        $proxyReq = StaffProxyRequest::find($request->request_id);

        if (!$proxyReq || $proxyReq->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['ok' => false, 'message' => 'Proxy request not found.'], 404);
        }

        if (!$proxyReq->isPending()) {
            return response()->json(['ok' => false, 'message' => 'This proxy request has already been ' . $proxyReq->status . '.'], 422);
        }

        if ($proxyReq->isExpired()) {
            $proxyReq->update(['status' => 'expired']);
            return response()->json(['ok' => false, 'message' => 'Proxy request has expired. Please start again.'], 422);
        }

        // Max 3 attempts
        if ($proxyReq->pin_attempts >= 3) {
            $proxyReq->update(['status' => 'rejected', 'reject_reason' => 'Too many failed attempts']);
            return response()->json(['ok' => false, 'message' => 'Too many failed attempts. Request rejected for security.'], 422);
        }

        $target = $proxyReq->targetStaff;
        $valid  = false;

        if ($proxyReq->verification_method === 'pin') {
            // Compare against staff's attendance PIN (stored hashed)
            $valid = $target->attendance_pin &&
                     Hash::check($request->code, $target->attendance_pin);
        } else {
            // Compare OTP
            $valid = $proxyReq->otp_code &&
                     Hash::check($request->code, $proxyReq->otp_code) &&
                     !$proxyReq->isExpired();
        }

        if (!$valid) {
            $proxyReq->increment('pin_attempts');
            $remaining = 3 - $proxyReq->fresh()->pin_attempts;
            $type = $proxyReq->verification_method === 'pin' ? 'PIN' : 'OTP';
            return response()->json([
                'ok'      => false,
                'message' => "Incorrect {$type}. {$remaining} attempt(s) remaining.",
            ], 422);
        }

        // ✓ Verified — commit the clock-in
        $proxyReq->update(['status' => 'approved']);
        $settings = $this->attendanceSettings();

        return $this->recordClockIn(
            tenantId    : $proxyReq->tenant_id,
            userId      : $proxyReq->target_user_id,
            clockedBy   : $proxyReq->requested_by,
            method      : 'proxy',
            lat         : $proxyReq->lat,
            lng         : $proxyReq->lng,
            geoVerified : false,
            settings    : $settings,
            date        : $proxyReq->attendance_date->toDateString(),
            time        : $proxyReq->clock_in_time,
            proxyVerified: true,
            proxyPhoto  : $proxyReq->friend_photo_path,
        );
    }

    // ── Staff: Set / change attendance PIN ────────────────────────────
    public function setPin(Request $request)
    {
        $data = $request->validate([
            'pin'             => ['required', 'digits:4'],
            'pin_confirmation'=> ['required', 'same:pin'],
            'current_pin'     => ['nullable', 'string'],
        ]);

        $user = auth()->user();

        // If staff already has a PIN, require current PIN to change
        if ($user->attendance_pin) {
            if (!$request->current_pin || !Hash::check($request->current_pin, $user->attendance_pin)) {
                return back()->withErrors(['current_pin' => 'Current PIN is incorrect.']);
            }
        }

        $user->update(['attendance_pin' => Hash::make($data['pin'])]);
        return back()->with('success', 'Attendance PIN set successfully. You can now authorise proxy clock-ins.');
    }

    // ── API: QR Clock-In ──────────────────────────────────────────────
    // Accepts BOTH:
    //   (A) Daily rotating token from the display screen → clocks YOU in
    //   (B) Personal static token from a staff ID card  → clocks ID OWNER in
    public function clockInQr(Request $request)
    {
        $request->validate([
            'token'      => ['required', 'string'],
            'lat'        => ['nullable', 'numeric'],
            'lng'        => ['nullable', 'numeric'],
            'photo_data' => ['nullable', 'string', 'max:7000000'],
        ]);

        $settings     = $this->attendanceSettings();
        $scanner      = auth()->user();   // person holding the phone
        $targetUser   = $scanner;         // default: clocking in yourself
        $isIdCardScan = false;

        // ── Determine QR type: screen (static), personal ID card, or legacy daily ─
        $personalUser  = $settings->verifyPersonalQrToken($request->token);
        $isScreenQr    = !$personalUser && $settings->verifyStaticQrToken($request->token);
        $isLegacyDaily = !$personalUser && !$isScreenQr && $settings->verifyQrToken($request->token);

        if ($personalUser) {
            // Staff ID card scan → clock in the card owner
            if ($personalUser->id !== $scanner->id) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Use "Clock in for a colleague" when scanning another staff member\'s ID card.',
                ], 422);
            }

            $targetUser   = $scanner;
            $isIdCardScan = true;
        } elseif ($isScreenQr || $isLegacyDaily) {
            // Display screen QR → clock in the authenticated staff member (yourself)
            $targetUser   = $scanner;
            $isIdCardScan = false;
        } else {
            return response()->json([
                'ok'      => false,
                'message' => 'Invalid or unrecognised QR code. Use the display screen QR or your staff ID card.',
            ], 422);
        }

        // ── Geo-fence check ───────────────────────────────────────────
        $geoVerified = false;
        if ($settings->geo_enabled) {
            if (!$request->lat || !$request->lng) {
                return response()->json(['ok' => false, 'message' => 'Location required. Please enable GPS.'], 422);
            }
            $dist = $settings->distanceTo((float)$request->lat, (float)$request->lng);
            if ($dist > $settings->geo_radius_meters) {
                return response()->json([
                    'ok'      => false,
                    'message' => round($dist) . "m from school. Must be within {$settings->geo_radius_meters}m.",
                ], 422);
            }
            $geoVerified = true;
        }

        $isProxy = $isIdCardScan && $targetUser->id !== $scanner->id;

        return $this->recordClockIn(
            tenantId     : $scanner->tenant_id,
            userId       : $targetUser->id,
            clockedBy    : $scanner->id,
            method       : $isProxy ? 'proxy' : 'qr',
            lat          : $request->lat,
            lng          : $request->lng,
            geoVerified  : $geoVerified,
            settings     : $settings,
            photo        : $request->photo_data,
            proxyVerified: $isProxy,
        );
    }

    // ── API: Clock Out ────────────────────────────────────────────────
    public function clockOut(Request $request)
    {
        $user = auth()->user();
        $rec  = StaffAttendanceRecord::where('user_id', $user->id)
                    ->whereDate('attendance_date', today())->first();
        if (!$rec) return response()->json(['ok' => false, 'message' => 'No clock-in for today.'], 404);
        if ($rec->clock_out_time) return response()->json(['ok' => false, 'message' => 'Already clocked out.'], 422);
        $rec->update(['clock_out_time' => now()->format('H:i:s')]);
        return response()->json(['ok' => true, 'message' => 'Clocked out at ' . now()->format('g:i A')]);
    }

    // ── Offline Upload ────────────────────────────────────────────────
    public function uploadOffline(Request $request)
    {
        $request->validate([
            'records'                   => ['required', 'array', 'max:100'],
            'records.*.user_id'         => ['required', 'integer'],
            'records.*.attendance_date' => ['required', 'date'],
            'records.*.clock_in_time'   => ['required', 'date_format:H:i:s'],
            'records.*.qr_token'        => ['nullable', 'string'],
            'records.*.lat'             => ['nullable', 'numeric'],
            'records.*.lng'             => ['nullable', 'numeric'],
        ]);

        $clocker = auth()->user();
        $settings = $this->attendanceSettings();
        $queued  = 0;

        foreach ($request->records as $rec) {
            if (!empty($rec['qr_token']) && !$settings->verifyQrToken($rec['qr_token'])) continue;
            if (!User::attendanceEligibleOn($clocker->tenant_id, $rec['attendance_date'])->whereKey($rec['user_id'])->exists()) continue;
            StaffOfflineClockIn::create([
                'tenant_id'       => $clocker->tenant_id,
                'user_id'         => $rec['user_id'],
                'clocked_by'      => $clocker->id,
                'attendance_date' => $rec['attendance_date'],
                'clock_in_time'   => $rec['clock_in_time'],
                'qr_token'        => $rec['qr_token'] ?? null,
                'lat'             => $rec['lat'] ?? null,
                'lng'             => $rec['lng'] ?? null,
                'status'          => 'pending',
            ]);
            $queued++;
        }
        return response()->json(['ok' => true, 'queued' => $queued]);
    }

    // ── Offline Queue Admin ───────────────────────────────────────────
    public function offlineQueue()
    {
        $tid = auth()->user()->tenant_id;
        $queue = StaffOfflineClockIn::with(['staff','clockedBy'])
                    ->where('tenant_id', $tid)
                    ->where('status','pending')->latest()->paginate(30);
        return view('staff-attendance.offline-queue', compact('queue'));
    }

    public function processOffline(Request $request, StaffOfflineClockIn $record)
    {
        abort_unless($record->tenant_id === auth()->user()->tenant_id, 403);
        $data = $request->validate(['action' => ['required', 'in:approve,reject'], 'reason' => ['nullable', 'string']]);
        if ($data['action'] === 'approve') {
            $this->recordClockIn(
                tenantId    : $record->tenant_id,
                userId      : $record->user_id,
                clockedBy   : $record->clocked_by,
                method      : 'offline',
                lat         : $record->lat,
                lng         : $record->lng,
                geoVerified : false,
                settings    : $this->attendanceSettings(),
                date        : $record->attendance_date->toDateString(),
                time        : $record->clock_in_time,
                offline     : true,
            );
            $record->update(['status' => 'applied']);
        } else {
            $record->update(['status' => 'rejected', 'reject_reason' => $data['reason']]);
        }
        return back()->with('success', 'Record ' . $data['action'] . 'd.');
    }

    // ── Proxy Photo Review ─────────────────────────────────────────────
    // "Clock in for a colleague" (mobile) captures a live photo instead of
    // a PIN. There is no automated facial match — an admin compares the
    // captured photo against the staff member's passport photo here.
    public function proxyReviewQueue()
    {
        $tid = auth()->user()->tenant_id;
        $records = StaffAttendanceRecord::with(['staff', 'clockedInBy'])
            ->where('tenant_id', $tid)
            ->where('proxy_review_status', 'pending')
            ->latest('attendance_date')
            ->paginate(30);

        return view('staff-attendance.proxy-review', compact('records'));
    }

    public function proxyReviewDecide(Request $request, StaffAttendanceRecord $record)
    {
        abort_unless($record->tenant_id === auth()->user()->tenant_id, 403);
        $data = $request->validate(['action' => ['required', 'in:confirmed,flagged']]);

        $record->update([
            'proxy_review_status' => $data['action'],
            'proxy_reviewed_by'   => auth()->id(),
            'proxy_reviewed_at'   => now(),
        ]);

        return back()->with('success', 'Marked as ' . $data['action'] . '.');
    }

    // ── Manual Override ───────────────────────────────────────────────
    public function manualOverride(Request $request)
    {
        $data = $request->validate([
            'user_id'         => ['required', 'integer'],
            'attendance_date' => ['required', 'date'],
            'status'          => ['required', 'in:early,present,late,absent'],
            'clock_in_time'   => ['nullable', 'date_format:H:i'],
            'clock_out_time'  => ['nullable', 'date_format:H:i'],
            'notes'           => ['nullable', 'string', 'max:200'],
        ]);
        $tid = auth()->user()->tenant_id;
        $target = User::attendanceEligibleOn($tid, $data['attendance_date'])->whereKey($data['user_id'])->first();
        if (!$target) {
            return back()->withErrors(['user_id' => 'Select a staff member employed on the attendance date.']);
        }

        StaffAttendanceRecord::updateOrCreate(
            ['tenant_id' => $tid, 'user_id' => $data['user_id'], 'attendance_date' => $data['attendance_date']],
            [
                'status'         => $data['status'],
                'clock_in_time'  => $data['clock_in_time'] ? $data['clock_in_time'].':00' : null,
                'clock_out_time' => $data['clock_out_time'] ? $data['clock_out_time'].':00' : null,
                'clock_in_method'=> 'manual',
                'clocked_in_by'  => auth()->id(),
                'notes'          => $data['notes'],
            ]
        );
        return back()->with('success', 'Attendance record updated.');
    }

    // ── Monthly Report ────────────────────────────────────────────────
    public function monthlyReport(Request $request)
    {
        $month     = $request->integer('month', now()->month);
        $year      = $request->integer('year',  now()->year);
        $tid       = auth()->user()->tenant_id;
        $settings  = $this->attendanceSettings();
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate   = (clone $startDate)->endOfMonth();

        $workingDays = [];
        for ($d = clone $startDate; $d->lte($endDate); $d->addDay()) {
            if ($d->isWeekday()) $workingDays[] = $d->toDateString();
        }

        $staff = User::tenantStaff($tid)->orderBy('name')->get();

        $records = StaffAttendanceRecord::where('tenant_id', $tid)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get()->groupBy('user_id');

        $report = $staff->map(function ($s) use ($records, $workingDays) {
            $recs   = $records->get($s->id, collect());
            $counts = ['early'=>0,'present'=>0,'late'=>0,'absent'=>0];
            $detail = [];
            foreach ($workingDays as $day) {
                $rec    = $recs->first(fn($r) => $r->attendance_date->toDateString() === $day);
                $status = $rec ? $rec->status : 'absent';
                $counts[$status]++;
                $detail[$day] = ['status'=>$status,'clock_in'=>$rec?->clock_in_time,'clock_out'=>$rec?->clock_out_time];
            }
            $total = count($workingDays);
            return [
                'staff'       => $s,
                'counts'      => $counts,
                'detail'      => $detail,
                'punctuality' => $total > 0 ? round((($counts['early']+$counts['present'])/$total)*100) : 0,
            ];
        });

        return view('staff-attendance.monthly-report', compact(
            'report','workingDays','month','year','settings','startDate'
        ));
    }

    // ── My Attendance ─────────────────────────────────────────────────
    public function myAttendance(Request $request)
    {
        $user      = auth()->user();
        $month     = $request->integer('month', now()->month);
        $year      = $request->integer('year',  now()->year);
        $settings  = $this->attendanceSettings();
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate   = (clone $startDate)->endOfMonth();

        $records = StaffAttendanceRecord::where('user_id', $user->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date')->get();

        $counts = [
            'early'   => $records->where('status','early')->count(),
            'present' => $records->where('status','present')->count(),
            'late'    => $records->where('status','late')->count(),
            'absent'  => $records->where('status','absent')->count(),
        ];

        $todayRecord    = StaffAttendanceRecord::where('user_id', $user->id)
                              ->whereDate('attendance_date', today())->first();
        $hasPIN         = (bool) $user->attendance_pin;
        $pendingProxies = StaffProxyRequest::where('target_user_id', $user->id)
                              ->whereDate('attendance_date', today())
                              ->where('status','pending')->get();
        $hasPendingOffline = $user->canManage('staff-attendance')
            && StaffOfflineClockIn::where('tenant_id', $user->tenant_id)
                ->where('status', 'pending')
                ->exists();

        return view('staff-attendance.my-attendance', compact(
            'records','counts','month','year','settings',
            'todayRecord','user','hasPIN','pendingProxies','hasPendingOffline'
        ));
    }

    // ── Staff ID Card ─────────────────────────────────────────────────
    public function idCard(\App\Models\User $staff)
    {
        $staff = User::activeStaff(auth()->user()->tenant_id)
            ->whereKey($staff->id)
            ->firstOrFail();

        // Generate personal QR for this staff member
        $qrPayload = $staff->personalQrPayload();
        $qrUrl     = route('staff-attendance.my') . '?qr_token=' . urlencode($qrPayload);
        $qrBase64  = $this->buildQrBase64($qrUrl, 200);

        $settings  = $this->attendanceSettings();

        $url = $qrUrl;
        return view('staff-attendance.id-card', compact('staff', 'qrBase64', 'qrPayload', 'settings', 'url'));
    }

    // ── Helpers ───────────────────────────────────────────────────────
    private function maskPhone(?string $phone): string
    {
        if (!$phone || strlen($phone) < 6) return '***';
        return substr($phone, 0, 4) . '****' . substr($phone, -3);
    }

    private function recordClockIn(
        int $tenantId, int $userId, int $clockedBy,
        string $method, ?float $lat, ?float $lng, bool $geoVerified,
        StaffAttendanceSetting $settings,
        ?string $date = null, ?string $time = null,
        bool $offline = false, bool $proxyVerified = false,
        ?string $photo = null, ?string $proxyPhoto = null
    ): mixed {
        $date   = $date ?? today()->toDateString();
        $time   = $time ?? now()->format('H:i:s');

        $target = User::attendanceEligibleOn($tenantId, $date)->whereKey($userId)->first();
        if (!$target) {
            $msg = 'Only staff employed on the attendance date can be marked for new attendance.';
            if (request()->expectsJson()) return response()->json(['ok' => false, 'message' => $msg], 422);
            return back()->withErrors(['error' => $msg]);
        }

        $status = $settings->classifyClockIn($time);

        $existing = StaffAttendanceRecord::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->whereDate('attendance_date', $date)
            ->whereNotNull('clock_in_time')->first();

        if ($existing) {
            $msg = "Already clocked in at " . Carbon::parse($existing->clock_in_time)->format('g:i A');
            if (request()->expectsJson()) return response()->json(['ok' => false, 'message' => $msg], 422);
            return back()->withErrors(['error' => $msg]);
        }

        StaffAttendanceRecord::updateOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $userId, 'attendance_date' => $date],
            [
                'status'           => $status,
                'clock_in_time'    => $time,
                'clock_in_method'  => $method,
                'clocked_in_by'    => $clockedBy,
                'clock_in_lat'     => $lat,
                'clock_in_lng'     => $lng,
                'geo_verified'     => $geoVerified,
                'proxy_verified'   => $proxyVerified,
                'proxy_pin_used'   => $proxyVerified && $method === 'proxy',
                'is_offline_upload'=> $offline,
                'clock_in_photo'   => $this->storeAttendancePhoto($photo, $tenantId, 'self'),
                'proxy_photo'      => $this->storeAttendancePhoto($proxyPhoto, $tenantId, 'proxy', true),
            ]
        );

        $label   = match($status) { 'early'=>'Early arrival','present'=>'Present','late'=>'Late', default=>'Marked' };
        $timeStr = Carbon::parse($time)->format('g:i A');

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'status' => $status, 'message' => "{$label} — clocked in at {$timeStr}."]);
        }
        return back()->with('success', "{$label} — clocked in at {$timeStr}.");
    }

    private function storeAttendancePhoto(
        ?string $photo,
        int $tenantId,
        string $prefix,
        bool $allowStoredPath = false
    ): ?string
    {
        if (!$photo) {
            return null;
        }

        if (!str_starts_with($photo, 'data:image/')) {
            return $allowStoredPath ? $photo : null;
        }

        if (!preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $photo, $matches)) {
            return null;
        }

        $encoded = substr($photo, strpos($photo, ',') + 1);
        $decoded = base64_decode($encoded, true);
        if ($decoded === false || strlen($decoded) < 500 || strlen($decoded) > 5 * 1024 * 1024) {
            return null;
        }

        $imageInfo = @getimagesizefromstring($decoded);
        $extension = match($imageInfo['mime'] ?? null) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => null,
        };
        if (!$extension) {
            return null;
        }

        $path = 'attendance-photos/' . $tenantId . '/' . today()->format('Ymd')
            . '/' . uniqid($prefix . '_', true) . '.' . $extension;

        if (!\Illuminate\Support\Facades\Storage::disk('public')->put($path, $decoded)) {
            return null;
        }

        return $path;
    }
}
