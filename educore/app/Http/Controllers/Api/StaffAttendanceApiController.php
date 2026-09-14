<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffAttendanceController;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use App\Models\StaffOfflineClockIn;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Staff self-attendance for the mobile app.
 *
 * Online clock-in/out reuse the existing StaffAttendanceController JSON
 * endpoints. A self clock-in captured while offline is uploaded later through
 * the same endpoint with captured_at; the server stores it in the existing
 * offline review queue instead of pretending it was verified live.
 */
class StaffAttendanceApiController extends Controller
{
    /**
     * Clock-in from the mobile app.
     *
     * The school display/ID-card QR encodes a URL like
     * ".../staff-attendance/my?qr_token=<payload>". The web PWA reads the
     * qr_token from the query string when the browser opens that URL, but the
     * app scans the raw string — so we normalise it to the bare token here.
     *
     * When captured_at is supplied this is a delayed/offline self clock-in.
     * It is validated against the authenticated staff member, QR evidence and
     * the school's geofence, then placed in StaffOfflineClockIn for admin
     * review. Proxy attendance is deliberately not accepted through this path.
     */
    public function clockIn(Request $request, StaffAttendanceController $web)
    {
        $this->normaliseScannedToken($request, 'token');

        if ($request->filled('captured_at')) {
            return $this->storeOfflineClockIn($request);
        }

        return $web->clockInQr($request);
    }

    /**
     * Clock in a colleague from the mobile app, verified by a live photo
     * captured at the moment of clock-in — pick the colleague, scan the
     * school QR, capture their photo.
     */
    public function proxyClockIn(Request $request, StaffAttendanceController $web)
    {
        $this->normaliseScannedToken($request, 'token');

        return $web->proxyClockInWithPhoto($request);
    }

    private function storeOfflineClockIn(Request $request)
    {
        $data = $request->validate([
            'token'       => ['required', 'string', 'max:4096'],
            'lat'         => ['nullable', 'numeric', 'between:-90,90'],
            'lng'         => ['nullable', 'numeric', 'between:-180,180'],
            'captured_at' => ['required', 'date'],
            'request_id'  => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $capturedAt = Carbon::parse($data['captured_at']);

        // Offline attendance is intended for short connectivity outages, not
        // historical reconstruction or future-dated clock-ins.
        if ($capturedAt->gt(now()->addMinutes(5)) || $capturedAt->lt(now()->subDays(2))) {
            return response()->json([
                'ok' => false,
                'message' => 'The offline attendance timestamp is outside the allowed upload window.',
            ], 422);
        }

        $settings = StaffAttendanceSetting::firstOrCreate(['tenant_id' => $user->tenant_id]);
        $token = (string) $data['token'];
        $personalUser = $settings->verifyPersonalQrToken($token);
        $validPersonal = $personalUser && (int) $personalUser->id === (int) $user->id;
        $validSchoolQr = $settings->verifyStaticQrToken($token) || $settings->verifyQrToken($token);

        if (!$validPersonal && !$validSchoolQr) {
            return response()->json([
                'ok' => false,
                'message' => 'The saved QR evidence is no longer valid for this staff member.',
            ], 422);
        }

        if ($settings->geo_enabled) {
            if (!isset($data['lat'], $data['lng'])) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Location evidence is required for this school\'s offline attendance.',
                ], 422);
            }

            $distance = $settings->distanceTo((float) $data['lat'], (float) $data['lng']);
            if ($distance > $settings->geo_radius_meters) {
                return response()->json([
                    'ok' => false,
                    'message' => round($distance) . "m from school. Must be within {$settings->geo_radius_meters}m.",
                ], 422);
            }
        }

        $date = $capturedAt->toDateString();
        $time = $capturedAt->format('H:i:s');

        // Natural idempotency for retries from WorkManager. A reconnect may
        // replay the same locally queued operation more than once.
        StaffOfflineClockIn::firstOrCreate(
            [
                'tenant_id'       => $user->tenant_id,
                'user_id'         => $user->id,
                'clocked_by'      => $user->id,
                'attendance_date' => $date,
                'clock_in_time'   => $time,
            ],
            [
                'qr_token' => $token,
                'lat'      => $data['lat'] ?? null,
                'lng'      => $data['lng'] ?? null,
                'status'   => 'pending',
            ],
        );

        return response()->json([
            'ok' => true,
            'message' => 'Offline attendance uploaded and is pending verification.',
            'queued' => 1,
            'request_id' => $data['request_id'] ?? null,
        ]);
    }

    /**
     * The display/ID-card QR encodes a URL like
     * ".../staff-attendance/my?qr_token=<payload>". The web PWA reads the
     * qr_token from the query string when the browser opens that URL, but the
     * app scans the raw string — normalise it to the bare token so the shared
     * verification logic accepts it unchanged.
     */
    private function normaliseScannedToken(Request $request, string $field): void
    {
        $value = (string) $request->input($field, '');

        if (str_contains($value, 'qr_token=')) {
            $query = parse_url($value, PHP_URL_QUERY) ?: '';
            parse_str($query, $parts);
            if (!empty($parts['qr_token'])) {
                $request->merge([$field => $parts['qr_token']]);
            }
        }
    }

    public function me(Request $request)
    {
        $user  = $request->user();
        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year', now()->year);

        $start = Carbon::createFromDate($year, $month, 1);
        $end   = (clone $start)->endOfMonth();

        $records = StaffAttendanceRecord::where('user_id', $user->id)
            ->whereBetween('attendance_date', [$start, $end])
            ->orderByDesc('attendance_date')
            ->get()
            ->map(fn ($r) => [
                'date'      => $r->attendance_date instanceof \DateTimeInterface
                    ? $r->attendance_date->format('Y-m-d')
                    : (string) $r->attendance_date,
                'status'    => $r->status,
                'clock_in'  => $r->clock_in_time,
                'clock_out' => $r->clock_out_time,
                'method'    => $r->clock_in_method,
            ]);

        $today = StaffAttendanceRecord::where('user_id', $user->id)
            ->whereDate('attendance_date', today())
            ->first();

        $settings = StaffAttendanceSetting::firstOrCreate(['tenant_id' => $user->tenant_id]);

        return response()->json([
            'month'  => $month,
            'year'   => $year,
            'counts' => [
                'early'   => $records->where('status', 'early')->count(),
                'present' => $records->where('status', 'present')->count(),
                'late'    => $records->where('status', 'late')->count(),
                'absent'  => $records->where('status', 'absent')->count(),
            ],
            'today' => $today ? [
                'status'    => $today->status,
                'clock_in'  => $today->clock_in_time,
                'clock_out' => $today->clock_out_time,
            ] : null,
            'settings' => [
                'geo_enabled'       => (bool) $settings->geo_enabled,
                'geo_radius_meters' => (int) ($settings->geo_radius_meters ?? 0),
            ],
            'records' => $records,
        ]);
    }
}
