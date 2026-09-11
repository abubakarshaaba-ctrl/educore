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
 * Online clock-in/out continue to reuse the proven web attendance logic.
 * Offline replay is deliberately versioned and idempotent so WorkManager can
 * safely retry after a dropped connection or process restart.
 */
class StaffAttendanceApiController extends Controller
{
    public function clockIn(Request $request, StaffAttendanceController $web)
    {
        $this->normaliseScannedToken($request, 'token');

        return $web->clockInQr($request);
    }

    /**
     * Reconcile one locally queued My Attendance action.
     *
     * Idempotency is recorded in staff_attendance_sync_events rather than on
     * the daily attendance row itself. A single day can legitimately contain
     * two separately retried operations (clock_in and clock_out), so storing
     * only the latest client UUID on the daily row would lose idempotency for
     * the earlier operation.
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

        $existingEvent = DB::table('staff_attendance_sync_events')
            ->where('tenant_id', $user->tenant_id)
            ->where('client_uuid', $data['client_uuid'])
            ->first();

        if ($existingEvent) {
            if ($existingEvent->status === 'rejected') {
                return response()->json([
                    'success' => false,
                    'idempotent' => true,
                    'status' => 'rejected',
                    'message' => $existingEvent->rejection_reason ?: 'This offline attendance action was rejected.',
                    'record_id' => $existingEvent->attendance_record_id,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'idempotent' => true,
                'message' => 'Attendance already synchronized.',
                'record_id' => $existingEvent->attendance_record_id,
            ]);
        }

        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $token = $data['qr_token'] ?? null;

        if ($data['action'] === 'clock_in') {
            if (!$token) {
                return $this->rejectOfflineSync($user->tenant_id, $user->id, $data, 'A school attendance QR is required for clock-in.');
            }

            $this->normaliseScannedToken($request, 'qr_token');
            $token = (string) $request->input('qr_token', $token);
            if (! $settings->verifyStaticQrToken($token)) {
                return $this->rejectOfflineSync($user->tenant_id, $user->id, $data, 'The attendance QR is no longer valid.');
            }

            if ($settings->geo_enabled) {
                if (!isset($data['latitude'], $data['longitude'])) {
                    return $this->rejectOfflineSync($user->tenant_id, $user->id, $data, 'Location is required because the school geofence is enabled.');
                }
                if ($settings->geo_lat === null || $settings->geo_lng === null || (int) $settings->geo_radius_meters <= 0) {
                    return $this->rejectOfflineSync($user->tenant_id, $user->id, $data, 'School location has not been configured.');
                }

                $distance = $settings->distanceTo((float) $data['latitude'], (float) $data['longitude']);
                if ($distance > (int) $settings->geo_radius_meters) {
                    return $this->rejectOfflineSync(
                        $user->tenant_id,
                        $user->id,
                        $data,
                        'Attendance was recorded outside the permitted school location.'
                    );
                }
            }
        }

        $timestamp = Carbon::parse($data['local_timestamp']);

        try {
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

                DB::table('staff_attendance_sync_events')->insert([
                    'tenant_id' => $user->tenant_id,
                    'client_uuid' => $data['client_uuid'],
                    'staff_user_id' => $user->id,
                    'submitted_by' => $user->id,
                    'attendance_record_id' => $record->id,
                    'action' => $data['action'],
                    'attendance_date' => $data['attendance_date'],
                    'local_timestamp' => $timestamp,
                    'status' => 'synced',
                    'rejection_reason' => null,
                    'payload' => json_encode($data, JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $record;
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            if ($exception->getStatusCode() === 422) {
                $reason = $exception->getMessage() ?: 'The offline attendance action was rejected.';
                $this->recordRejectedSync($user->tenant_id, $user->id, $data, $reason);
            }
            throw $exception;
        }

        return response()->json([
            'success' => true,
            'idempotent' => false,
            'status' => 'synced',
            'message' => 'Offline attendance synchronized successfully.',
            'record_id' => $record->id,
        ]);
    }

    private function rejectOfflineSync(int $tenantId, int $userId, array $data, string $reason)
    {
        $this->recordRejectedSync($tenantId, $userId, $data, $reason);

        return response()->json([
            'success' => false,
            'idempotent' => false,
            'status' => 'rejected',
            'message' => $reason,
        ], 422);
    }

    private function recordRejectedSync(int $tenantId, int $userId, array $data, string $reason): void
    {
        DB::table('staff_attendance_sync_events')->updateOrInsert(
            ['tenant_id' => $tenantId, 'client_uuid' => $data['client_uuid']],
            [
                'staff_user_id' => $userId,
                'submitted_by' => $userId,
                'attendance_record_id' => null,
                'action' => $data['action'],
                'attendance_date' => $data['attendance_date'],
                'local_timestamp' => Carbon::parse($data['local_timestamp']),
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'payload' => json_encode($data, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function proxyClockIn(Request $request, StaffAttendanceController $web)
    {
        $this->normaliseScannedToken($request, 'token');

        return $web->proxyClockInWithPhoto($request);
    }

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
        $user = $request->user();
        abort_unless($user && $user->tenant_id, 401, 'Unauthenticated.');

        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);
        $month = (int) ($validated['month'] ?? now()->month);
        $year = (int) ($validated['year'] ?? now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth();

        $baseQuery = StaffAttendanceRecord::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id);

        $records = (clone $baseQuery)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('attendance_date')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->attendance_date instanceof \DateTimeInterface
                    ? $r->attendance_date->format('Y-m-d')
                    : (string) $r->attendance_date,
                'status' => $r->status,
                'clock_in' => $r->clock_in_time,
                'clock_out' => $r->clock_out_time,
                'method' => $r->clock_in_method,
            ]);

        $today = (clone $baseQuery)
            ->whereDate('attendance_date', today())
            ->first();

        $settings = StaffAttendanceSetting::firstOrCreate(['tenant_id' => $user->tenant_id]);

        return response()->json([
            'month' => $month,
            'year' => $year,
            'counts' => [
                'early' => $records->where('status', 'early')->count(),
                'present' => $records->where('status', 'present')->count(),
                'late' => $records->where('status', 'late')->count(),
                'absent' => $records->where('status', 'absent')->count(),
            ],
            'today' => $today ? [
                'status' => $today->status,
                'clock_in' => $today->clock_in_time,
                'clock_out' => $today->clock_out_time,
            ] : null,
            'settings' => [
                'geo_enabled' => (bool) $settings->geo_enabled,
                'geo_radius_meters' => (int) ($settings->geo_radius_meters ?? 0),
            ],
            'records' => $records,
        ]);
    }
}
