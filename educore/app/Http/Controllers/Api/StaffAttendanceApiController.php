<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffAttendanceController;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Staff self-attendance for the mobile app.
 *
 * Live requests reuse the existing StaffAttendanceController endpoints. Durable
 * offline replay requests are authenticated, idempotent and written through the
 * same attendance record model plus the staff_attendance_sync_events ledger.
 */
class StaffAttendanceApiController extends Controller
{
    public function clockIn(Request $request, StaffAttendanceController $web)
    {
        if ($this->isOfflineReplay($request)) {
            return $this->replayOfflineAttendance($request);
        }

        $this->normaliseScannedToken($request, 'token');

        return $web->clockInQr($request);
    }

    /**
     * Replay one clock-in or clock-out captured by My Attendance while offline.
     * The caller cannot select a staff member: tenant and user identity are
     * derived exclusively from the authenticated bearer token.
     */
    private function replayOfflineAttendance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_uuid'     => ['required', 'uuid'],
            'action'          => ['required', Rule::in(['clock_in', 'clock_out'])],
            'attendance_date' => ['required', 'date_format:Y-m-d'],
            'local_timestamp' => ['required', 'date'],
            'latitude'        => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'       => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy'        => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'qr_token'        => ['nullable', 'string', 'max:4096'],
        ]);

        $user = $request->user();
        if (! $user || ! $user->tenant_id) {
            return response()->json([
                'success' => false,
                'status' => 'rejected',
                'message' => 'Your session has expired. Sign in again.',
            ], 401);
        }

        if ($existing = $this->existingSyncEvent((int) $user->tenant_id, $data['client_uuid'])) {
            return $this->idempotentSyncResponse($existing);
        }

        $attendanceDate = Carbon::createFromFormat('Y-m-d', $data['attendance_date'])->startOfDay();
        $timestamp = Carbon::parse($data['local_timestamp']);

        if ($timestamp->toDateString() !== $attendanceDate->toDateString()) {
            return $this->rejectOfflineReplay($user, $data, 'The offline attendance timestamp does not match its attendance date.');
        }

        if ($attendanceDate->isFuture() || $attendanceDate->lt(today()->subDays(7))) {
            return $this->rejectOfflineReplay($user, $data, 'Offline attendance can only be replayed for the current day or the previous 7 days.');
        }

        $eligible = User::attendanceEligibleOn($user->tenant_id, $attendanceDate->toDateString())
            ->whereKey($user->id)
            ->exists();
        if (! $eligible) {
            return $this->rejectOfflineReplay($user, $data, 'Your account is not eligible for staff attendance on this date.', 403);
        }

        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);

        if ($data['action'] === 'clock_in') {
            $token = trim((string) ($data['qr_token'] ?? ''));
            if ($token === '') {
                return $this->rejectOfflineReplay($user, $data, 'A school attendance QR is required for clock-in.');
            }

            $token = $this->normalisedTokenValue($token);
            $data['qr_token'] = $token;
            $validQr = $settings->verifyStaticQrToken($token) || $settings->verifyQrToken($token);
            if (! $validQr) {
                return $this->rejectOfflineReplay($user, $data, 'The school attendance QR is invalid or has been reset.');
            }

            if ($settings->geo_enabled) {
                if (! isset($data['latitude'], $data['longitude'])) {
                    return $this->rejectOfflineReplay($user, $data, 'Location was not captured with this offline clock-in.');
                }

                if ($settings->geo_lat === null || $settings->geo_lng === null || (int) $settings->geo_radius_meters <= 0) {
                    return $this->rejectOfflineReplay($user, $data, 'School location has not been configured.');
                }

                $distance = $settings->distanceTo((float) $data['latitude'], (float) $data['longitude']);
                if ($distance > (int) $settings->geo_radius_meters) {
                    return $this->rejectOfflineReplay(
                        $user,
                        $data,
                        'Attendance was recorded outside the permitted school location.'
                    );
                }
            }
        }

        try {
            $record = DB::transaction(function () use ($user, $settings, $data, $timestamp) {
                $record = StaffAttendanceRecord::query()
                    ->where('tenant_id', $user->tenant_id)
                    ->where('user_id', $user->id)
                    ->whereDate('attendance_date', $data['attendance_date'])
                    ->lockForUpdate()
                    ->first();

                if (! $record) {
                    $record = new StaffAttendanceRecord([
                        'tenant_id' => $user->tenant_id,
                        'user_id' => $user->id,
                        'attendance_date' => $data['attendance_date'],
                    ]);
                }

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
                    if (! $record->exists || ! $record->clock_in_time) {
                        throw new HttpException(422, 'A clock-in record is required before clock-out.');
                    }
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
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() === 422) {
                return $this->rejectOfflineReplay(
                    $user,
                    $data,
                    $exception->getMessage() ?: 'The offline attendance action was rejected.'
                );
            }
            throw $exception;
        } catch (QueryException $exception) {
            if ($existing = $this->existingSyncEvent((int) $user->tenant_id, $data['client_uuid'])) {
                return $this->idempotentSyncResponse($existing);
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

    private function rejectOfflineReplay(User $user, array $data, string $reason, int $httpStatus = 422): JsonResponse
    {
        try {
            DB::table('staff_attendance_sync_events')->updateOrInsert(
                ['tenant_id' => $user->tenant_id, 'client_uuid' => $data['client_uuid']],
                [
                    'staff_user_id' => $user->id,
                    'submitted_by' => $user->id,
                    'attendance_record_id' => null,
                    'action' => $data['action'],
                    'attendance_date' => $data['attendance_date'],
                    'local_timestamp' => Carbon::parse($data['local_timestamp']),
                    'status' => 'rejected',
                    'rejection_reason' => $reason,
                    'payload' => json_encode($data, JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        } catch (QueryException $exception) {
            if ($existing = $this->existingSyncEvent((int) $user->tenant_id, $data['client_uuid'])) {
                return $this->idempotentSyncResponse($existing);
            }
            throw $exception;
        }

        return response()->json([
            'success' => false,
            'idempotent' => false,
            'status' => 'rejected',
            'message' => $reason,
        ], $httpStatus);
    }

    private function existingSyncEvent(int $tenantId, string $clientUuid): ?object
    {
        return DB::table('staff_attendance_sync_events')
            ->where('tenant_id', $tenantId)
            ->where('client_uuid', $clientUuid)
            ->first();
    }

    private function idempotentSyncResponse(object $event): JsonResponse
    {
        if ($event->status === 'rejected') {
            return response()->json([
                'success' => false,
                'idempotent' => true,
                'status' => 'rejected',
                'message' => $event->rejection_reason ?: 'This offline attendance action was rejected.',
                'record_id' => $event->attendance_record_id,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'idempotent' => true,
            'status' => 'synced',
            'message' => 'Attendance already synchronized.',
            'record_id' => $event->attendance_record_id,
        ]);
    }

    private function isOfflineReplay(Request $request): bool
    {
        return $request->filled('client_uuid') || $request->filled('local_timestamp');
    }

    /**
     * Clock in a colleague from the mobile app, verified by a live photo
     * captured at the moment of clock-in.
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
            if (! empty($parts['qr_token'])) {
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
