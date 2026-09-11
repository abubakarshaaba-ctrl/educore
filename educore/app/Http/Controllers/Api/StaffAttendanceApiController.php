<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffAttendanceController;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use App\Models\StaffOfflineClockIn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Staff self-attendance for the mobile app.
 *
 * Live clock-in/out reuse the existing StaffAttendanceController JSON endpoints.
 * Offline self clock-ins are accepted through the same authenticated clock-in
 * route, but are queued for administrator review instead of being trusted as a
 * live attendance record.
 */
class StaffAttendanceApiController extends Controller
{
    /**
     * Clock-in from the mobile app.
     *
     * Normal requests are delegated to the proven web clock-in logic. Durable
     * offline replay requests include client_uuid/action/local_timestamp and are
     * handled separately so the server always derives the staff identity from
     * the authenticated API token rather than trusting a client-supplied ID.
     */
    public function clockIn(Request $request, StaffAttendanceController $web)
    {
        if ($request->filled('client_uuid') || $request->filled('local_timestamp')) {
            return $this->replayOfflineClockIn($request);
        }

        $this->normaliseScannedToken($request, 'token');

        return $web->clockInQr($request);
    }

    /**
     * Replay one self clock-in captured while the phone was offline.
     *
     * Security invariants:
     * - user/tenant identity comes only from the authenticated bearer token;
     * - only self clock-in is accepted (offline clock-out is not fabricated);
     * - the school QR and geo-fence are validated again on the server;
     * - the replay is queued for the existing offline-attendance review flow;
     * - duplicate WorkManager retries are idempotent for the same user/date/time.
     */
    private function replayOfflineClockIn(Request $request)
    {
        $data = $request->validate([
            'client_uuid'     => ['required', 'uuid'],
            'action'          => ['required', 'in:clock_in'],
            'attendance_date' => ['required', 'date_format:Y-m-d'],
            'local_timestamp' => ['required', 'date'],
            'latitude'        => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'       => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy'        => ['nullable', 'numeric', 'min:0'],
            'qr_token'        => ['required', 'string'],
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'status'  => 'rejected',
                'message' => 'Your session has expired. Sign in again.',
            ], 401);
        }

        $attendanceDate = Carbon::createFromFormat('Y-m-d', $data['attendance_date'])->startOfDay();
        $capturedAt = Carbon::parse($data['local_timestamp']);

        if ($capturedAt->toDateString() !== $attendanceDate->toDateString()) {
            return response()->json([
                'success' => false,
                'status'  => 'rejected',
                'message' => 'The offline attendance timestamp does not match its attendance date.',
            ], 422);
        }

        if ($attendanceDate->isFuture() || $attendanceDate->lt(today()->subDays(7))) {
            return response()->json([
                'success' => false,
                'status'  => 'rejected',
                'message' => 'Offline attendance can only be replayed for the current day or the previous 7 days.',
            ], 422);
        }

        $eligible = User::attendanceEligibleOn($user->tenant_id, $attendanceDate->toDateString())
            ->whereKey($user->id)
            ->exists();
        if (!$eligible) {
            return response()->json([
                'success' => false,
                'status'  => 'rejected',
                'message' => 'Your account is not eligible for staff attendance on this date.',
            ], 403);
        }

        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $qrToken = $this->normalisedTokenValue((string) $data['qr_token']);
        $validQr = $settings->verifyStaticQrToken($qrToken) || $settings->verifyQrToken($qrToken);
        if (!$validQr) {
            return response()->json([
                'success' => false,
                'status'  => 'rejected',
                'message' => 'The school attendance QR is invalid or has been reset.',
            ], 422);
        }

        if ($settings->geo_enabled) {
            if (!isset($data['latitude'], $data['longitude'])) {
                return response()->json([
                    'success' => false,
                    'status'  => 'rejected',
                    'message' => 'Location was not captured with this offline clock-in.',
                ], 422);
            }

            $distance = $settings->distanceTo((float) $data['latitude'], (float) $data['longitude']);
            if ($distance > (int) $settings->geo_radius_meters) {
                return response()->json([
                    'success' => false,
                    'status'  => 'rejected',
                    'message' => round($distance) . "m from school. Must be within {$settings->geo_radius_meters}m.",
                ], 422);
            }
        }

        $clockInTime = $capturedAt->format('H:i:s');
        $existing = StaffOfflineClockIn::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $attendanceDate->toDateString())
            ->where('clock_in_time', $clockInTime)
            ->first();

        if ($existing) {
            return response()->json([
                'success'    => true,
                'idempotent' => true,
                'status'     => $existing->status,
                'message'    => 'Offline clock-in was already received.',
                'record_id'  => $existing->id,
            ]);
        }

        $alreadyClockedIn = StaffAttendanceRecord::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $attendanceDate->toDateString())
            ->whereNotNull('clock_in_time')
            ->first();
        if ($alreadyClockedIn) {
            return response()->json([
                'success'    => true,
                'idempotent' => true,
                'status'     => 'already_recorded',
                'message'    => 'Attendance for this date is already recorded.',
                'record_id'  => $alreadyClockedIn->id,
            ]);
        }

        $queued = StaffOfflineClockIn::create([
            'tenant_id'       => $user->tenant_id,
            'user_id'         => $user->id,
            'clocked_by'      => $user->id,
            'attendance_date' => $attendanceDate->toDateString(),
            'clock_in_time'   => $clockInTime,
            'qr_token'        => $qrToken,
            'lat'             => $data['latitude'] ?? null,
            'lng'             => $data['longitude'] ?? null,
            'status'          => 'pending',
        ]);

        return response()->json([
            'success'    => true,
            'idempotent' => false,
            'status'     => 'pending',
            'message'    => 'Offline clock-in uploaded and is pending attendance review.',
            'record_id'  => $queued->id,
        ]);
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

    private function normaliseScannedToken(Request $request, string $field): void
    {
        $value = (string) $request->input($field, '');
        $request->merge([$field => $this->normalisedTokenValue($value)]);
    }

    private function normalisedTokenValue(string $value): string
    {
        if (str_contains($value, 'qr_token=')) {
            $query = parse_url($value, PHP_URL_QUERY) ?: '';
            parse_str($query, $parts);
            if (!empty($parts['qr_token'])) {
                return (string) $parts['qr_token'];
            }
        }

        return $value;
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
                'present' => $records->whereIn('status', ['early', 'present'])->count(),
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
