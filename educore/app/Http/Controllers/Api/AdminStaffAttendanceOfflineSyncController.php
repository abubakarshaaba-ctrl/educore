<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
 * Replays one administrator-managed offline staff-attendance event.
 *
 * Idempotency belongs to staff_attendance_sync_events rather than the daily
 * attendance row. That allows clock-in and clock-out to carry independent
 * client UUIDs while WorkManager/network retries remain safe.
 */
class AdminStaffAttendanceOfflineSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $actor = $request->user();
        abort_unless(
            $actor && $actor->tenant_id && ($actor->isSuperAdmin() || $actor->canManage('staff-attendance')),
            403,
            'You do not have permission to manage staff attendance.'
        );

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

        if ($existing = $this->existingEvent((int) $actor->tenant_id, $data['client_uuid'])) {
            return $this->idempotentResponse($existing);
        }

        $staff = User::tenantStaff($actor->tenant_id)->whereKey($data['staff_id'])->first();
        if (! $staff) {
            return $this->reject($actor->tenant_id, $actor->id, $data, 'The selected staff member is not available for this school.');
        }

        $settings = StaffAttendanceSetting::forTenant($actor->tenant_id);

        if ($data['action'] === 'clock_in') {
            $token = trim((string) ($data['qr_token'] ?? ''));
            if ($token === '') {
                return $this->reject($actor->tenant_id, $actor->id, $data, 'A school attendance QR is required for clock-in.');
            }

            $token = $this->normaliseToken($token);
            $data['qr_token'] = $token;
            if (! $settings->verifyStaticQrToken($token)) {
                return $this->reject($actor->tenant_id, $actor->id, $data, 'The attendance QR is no longer valid.');
            }

            if ($settings->geo_enabled) {
                if (! isset($data['latitude'], $data['longitude'])) {
                    return $this->reject($actor->tenant_id, $actor->id, $data, 'Location is required because the school geofence is enabled.');
                }

                if ($settings->geo_lat === null || $settings->geo_lng === null || (int) $settings->geo_radius_meters <= 0) {
                    return $this->reject($actor->tenant_id, $actor->id, $data, 'School location has not been configured.');
                }

                $distance = $settings->distanceTo((float) $data['latitude'], (float) $data['longitude']);
                if ($distance > (int) $settings->geo_radius_meters) {
                    return $this->reject(
                        $actor->tenant_id,
                        $actor->id,
                        $data,
                        'Attendance was recorded outside the permitted school location.'
                    );
                }
            }
        }

        $timestamp = Carbon::parse($data['local_timestamp']);

        try {
            $record = DB::transaction(function () use ($actor, $staff, $settings, $data, $timestamp) {
                $record = StaffAttendanceRecord::query()
                    ->where('tenant_id', $actor->tenant_id)
                    ->where('user_id', $staff->id)
                    ->whereDate('attendance_date', $data['attendance_date'])
                    ->lockForUpdate()
                    ->first();

                if (! $record) {
                    $record = new StaffAttendanceRecord([
                        'tenant_id' => $actor->tenant_id,
                        'user_id' => $staff->id,
                        'attendance_date' => $data['attendance_date'],
                    ]);
                }

                if ($data['action'] === 'clock_in') {
                    if (! $record->exists || ! $record->clock_in_time) {
                        $record->clock_in_time = $timestamp->format('H:i:s');
                        $record->status = $settings->classifyClockIn($timestamp->format('H:i:s'));
                    }
                    $record->clock_in_method = 'offline';
                    $record->clocked_in_by = $actor->id;
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
                    'tenant_id' => $actor->tenant_id,
                    'client_uuid' => $data['client_uuid'],
                    'staff_user_id' => $staff->id,
                    'submitted_by' => $actor->id,
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
                return $this->reject(
                    $actor->tenant_id,
                    $actor->id,
                    $data,
                    $exception->getMessage() ?: 'The offline attendance action was rejected.'
                );
            }
            throw $exception;
        } catch (QueryException $exception) {
            if ($existing = $this->existingEvent((int) $actor->tenant_id, $data['client_uuid'])) {
                return $this->idempotentResponse($existing);
            }
            throw $exception;
        }

        $record->loadMissing(['staff', 'clockedInBy']);

        return response()->json([
            'success' => true,
            'idempotent' => false,
            'status' => 'synced',
            'message' => 'Offline attendance synchronized successfully.',
            'record' => $this->recordPayload($record),
        ]);
    }

    private function reject(int $tenantId, int $actorId, array $data, string $reason): JsonResponse
    {
        try {
            DB::table('staff_attendance_sync_events')->updateOrInsert(
                ['tenant_id' => $tenantId, 'client_uuid' => $data['client_uuid']],
                [
                    'staff_user_id' => (int) $data['staff_id'],
                    'submitted_by' => $actorId,
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
            if ($existing = $this->existingEvent($tenantId, $data['client_uuid'])) {
                return $this->idempotentResponse($existing);
            }
            throw $exception;
        }

        return response()->json([
            'success' => false,
            'idempotent' => false,
            'status' => 'rejected',
            'message' => $reason,
        ], 422);
    }

    private function existingEvent(int $tenantId, string $clientUuid): ?object
    {
        return DB::table('staff_attendance_sync_events')
            ->where('tenant_id', $tenantId)
            ->where('client_uuid', $clientUuid)
            ->first();
    }

    private function idempotentResponse(object $event): JsonResponse
    {
        $record = $event->attendance_record_id
            ? StaffAttendanceRecord::query()
                ->where('tenant_id', $event->tenant_id)
                ->whereKey($event->attendance_record_id)
                ->with(['staff', 'clockedInBy'])
                ->first()
            : null;

        if ($event->status === 'rejected') {
            return response()->json([
                'success' => false,
                'idempotent' => true,
                'status' => 'rejected',
                'message' => $event->rejection_reason ?: 'This offline attendance action was rejected.',
                'record' => $record ? $this->recordPayload($record) : null,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'idempotent' => true,
            'status' => 'synced',
            'message' => 'Attendance already synchronized.',
            'record' => $record ? $this->recordPayload($record) : null,
        ]);
    }

    private function normaliseToken(string $value): string
    {
        if (! str_contains($value, 'qr_token=')) {
            return $value;
        }

        $query = parse_url($value, PHP_URL_QUERY) ?: '';
        parse_str($query, $parts);

        return ! empty($parts['qr_token']) ? (string) $parts['qr_token'] : $value;
    }

    private function recordPayload(StaffAttendanceRecord $record): array
    {
        return [
            'id' => $record->id,
            'staff_id' => $record->user_id,
            'staff_name' => $record->staff?->name,
            'staff_number' => $record->staff?->staff_id,
            'date' => $record->attendance_date?->toDateString(),
            'status' => $record->status,
            'clock_in' => $record->clock_in_time,
            'clock_out' => $record->clock_out_time,
            'method' => $record->clock_in_method,
            'recorded_by_proxy' => $record->clock_in_method === 'proxy',
            'recorded_by' => $record->clockedInBy?->name,
            'proxy_reason' => $record->proxy_reason,
            'client_uuid' => $record->client_uuid,
            'offline' => (bool) $record->is_offline_upload,
            'rejection_reason' => $record->offline_rejection_reason,
            'latitude' => $record->clock_in_lat,
            'longitude' => $record->clock_in_lng,
            'accuracy' => $record->location_accuracy,
            'updated_at' => $record->updated_at?->toIso8601String(),
        ];
    }
}
