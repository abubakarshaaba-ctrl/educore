<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffAttendanceController;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Staff self-attendance for the mobile app.
 *
 * Clock-in/out reuse the existing StaffAttendanceController JSON endpoints
 * (QR + geo-fence rules identical to the web PWA); this controller only
 * adds the mobile summary feed and durable replay for offline mobile actions.
 */
class StaffAttendanceApiController extends Controller
{
    /**
     * Clock-in from the mobile app.
     *
     * The school display/ID-card QR encodes a URL like
     * ".../staff-attendance/my?qr_token=<payload>". The web PWA reads the
     * qr_token from the query string when the browser opens that URL, but the
     * app scans the raw string — so we normalise it to the bare token here,
     * then delegate to the proven web clock-in logic (QR + geo-fence rules).
     */
    public function clockIn(Request $request, StaffAttendanceController $web)
    {
        $this->normaliseScannedToken($request, 'token');

        return $web->clockInQr($request);
    }

    /**
     * Reconcile one locally queued self-attendance action.
     *
     * The client_uuid is tenant-scoped and idempotent so WorkManager can retry
     * safely after process death, flaky connectivity, or duplicate delivery.
     */
    public function syncOffline(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->tenant_id, 401, 'Unauthenticated.');

        $data = $request->validate([
            'client_uuid' => ['required', 'uuid'],
            'staff_id' => ['required', 'integer'],
            'action' => ['required', Rule::in(['clock_in', 'clock_out'])],
            'attendance_date' => ['required', 'date_format:Y-m-d'],
            'local_timestamp' => ['required', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'qr_token' => ['nullable', 'string', 'max:4096'],
        ]);

        abort_unless((int) $data['staff_id'] === (int) $user->id, 403, 'You can only synchronize your own attendance.');

        $existing = StaffAttendanceRecord::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('client_uuid', $data['client_uuid'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'idempotent' => true,
                'message' => 'Attendance already synchronized.',
                'record_id' => $existing->id,
            ]);
        }

        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $token = $data['qr_token'] ?? null;
        if ($data['action'] === 'clock_in' && $token) {
            $this->normaliseScannedToken($request, 'qr_token');
            $token = (string) $request->input('qr_token', $token);
            if (! $settings->verifyStaticQrToken($token)) {
                return response()->json([
                    'success' => false,
                    'status' => 'rejected',
                    'message' => 'The attendance QR is no longer valid.',
                ], 422);
            }
        }

        $timestamp = Carbon::parse($data['local_timestamp']);
        $record = DB::transaction(function () use ($data, $user, $settings, $timestamp) {
            $record = StaffAttendanceRecord::firstOrNew([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'attendance_date' => $data['attendance_date'],
            ]);

            if ($data['action'] === 'clock_in') {
                if (! $record->exists || ! $record->clock_in_time) {
                    $record->clock_in_time = $timestamp->format('H:i:s');
                    $record->status = $settings->classifyClockIn($timestamp->format('H:i:s'));
                }
                $record->clock_in_method = 'offline';
                $record->clocked_in_by = $user->id;
                $record->clock_in_lat = $data['latitude'] ?? null;
                $record->clock_in_lng = $data['longitude'] ?? null;
                $record->location_accuracy = $data['accuracy'] ?? null;
            } else {
                abort_unless($record->exists && $record->clock_in_time, 422, 'A clock-in record is required before clock-out.');
                if (! $record->clock_out_time) {
                    $record->clock_out_time = $timestamp->format('H:i:s');
                }
            }

            $record->client_uuid = $data['client_uuid'];
            $record->is_offline_upload = true;
            $record->save();

            return $record;
        });

        return response()->json([
            'success' => true,
            'idempotent' => false,
            'message' => 'Offline attendance synchronized successfully.',
            'record_id' => $record->id,
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

    /**
     * The display/ID-card QR encodes a URL like
     * ".../staff-attendance/my?qr_token=<payload>". The web PWA reads the
     * qr_token from the query string when the browser opens that URL, but
     * the app scans the raw string — normalise it to the bare token so the
     * shared web verification logic (QR + geo-fence) accepts it unchanged.
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
