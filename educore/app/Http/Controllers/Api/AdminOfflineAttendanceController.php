<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use App\Models\StaffOfflineClockIn;
use App\Models\User;
use App\Services\StaffAttendanceScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Throwable;

class AdminOfflineAttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);

        $records = StaffOfflineClockIn::where('tenant_id', $user->tenant_id)
            ->where('status', 'pending')
            ->with(['staff:id,name,staff_id', 'clockedBy:id,name'])
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (StaffOfflineClockIn $record) use ($settings): array {
                [$verifiable, $issue] = $this->verificationState($settings, $record);
                return [
                    'id' => $record->id,
                    'user_id' => $record->user_id,
                    'staff' => $record->staff?->name,
                    'staff_id' => $record->staff?->staff_id,
                    'clocked_by' => $record->clockedBy?->name,
                    'attendance_date' => $record->attendance_date?->format('Y-m-d'),
                    'clock_in' => $record->clock_in_time,
                    'lat' => $record->lat === null ? null : (float) $record->lat,
                    'lng' => $record->lng === null ? null : (float) $record->lng,
                    'status' => $record->status,
                    'verifiable' => $verifiable,
                    'verification_issue' => $issue,
                ];
            });

        return response()->json([
            'count' => $records->count(),
            'verifiable_count' => $records->where('verifiable', true)->count(),
            'records' => $records->values(),
        ]);
    }

    public function process(Request $request, int $record): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'approve_manual', 'reject'])],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $offline = StaffOfflineClockIn::where('tenant_id', $user->tenant_id)->find($record);
        if (! $offline) {
            return response()->json(['message' => 'This offline attendance record was already removed. The review list has been refreshed.']);
        }
        if ($offline->status !== 'pending') {
            return response()->json(['message' => 'This offline attendance record was already processed. The review list has been refreshed.']);
        }

        try {
            if ($data['action'] === 'reject') {
                $offlineValues = ['status' => 'rejected'];
                if (Schema::hasColumn($offline->getTable(), 'reject_reason')) {
                    $offlineValues['reject_reason'] = trim((string) ($data['reason'] ?? 'Rejected during administrator review.'));
                }
                $offline->update($offlineValues);

                return response()->json(['message' => 'Offline attendance rejected.', 'record_id' => $offline->id]);
            }

            if (! $offline->attendance_date || blank($offline->clock_in_time)) {
                return response()->json([
                    'message' => 'This legacy offline record is missing its attendance date or clock-in time. Reject the queued record instead.',
                ], 422);
            }

            $staffExists = User::where('tenant_id', $offline->tenant_id)
                ->whereKey($offline->user_id)
                ->exists();
            if (! $staffExists) {
                return response()->json([
                    'message' => 'This legacy offline record belongs to a staff account that is no longer available. Reject the queued record instead.',
                ], 422);
            }

            $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
            [$verifiable, $issue] = $this->verificationState($settings, $offline);
            $manual = $data['action'] === 'approve_manual' || ! $verifiable;

            $geoVerified = false;
            if ($settings->geo_enabled && $offline->lat !== null && $offline->lng !== null) {
                $geoVerified = $settings->distanceTo((float) $offline->lat, (float) $offline->lng) <= (int) $settings->geo_radius_meters;
            }
            if ($settings->geo_enabled && ! $geoVerified) {
                $manual = true;
                $issue = trim(($issue ? $issue.' ' : '').'Stored location evidence is missing or outside the current school geofence.');
            }

            $manualReason = trim((string) ($data['reason'] ?? ''));
            if ($manual && $manualReason === '') {
                $manualReason = $issue ?: 'Legacy offline evidence required administrator review.';
            }

            $attendanceDate = $offline->attendance_date->toDateString();
            $scheduleService = app(StaffAttendanceScheduleService::class);
            $schedule = $scheduleService->forDate(
                (int) $offline->tenant_id,
                $attendanceDate,
                $settings
            );

            $attendance = DB::transaction(function () use ($offline, $user, $settings, $scheduleService, $schedule, $attendanceDate, $geoVerified, $manual, $manualReason): StaffAttendanceRecord {
                $attendanceModel = new StaffAttendanceRecord();
                $attendanceTable = $attendanceModel->getTable();

                $values = [
                    'status' => $scheduleService->classifyArrival(
                        $schedule,
                        $attendanceDate,
                        (string) $offline->clock_in_time
                    ),
                    'clock_in_time' => $offline->clock_in_time,
                ];

                if (Schema::hasColumn($attendanceTable, 'expected_resumption_time')) {
                    $values['expected_resumption_time'] = $schedule->resumption_time
                        ? substr((string) $schedule->resumption_time, 0, 8)
                        : null;
                }
                if (Schema::hasColumn($attendanceTable, 'expected_closing_time')) {
                    $values['expected_closing_time'] = $schedule->closing_time
                        ? substr((string) $schedule->closing_time, 0, 8)
                        : null;
                }
                if (Schema::hasColumn($attendanceTable, 'grace_minutes')) {
                    $values['grace_minutes'] = (int) $schedule->grace_minutes;
                }
                if (Schema::hasColumn($attendanceTable, 'scheduled_workday')) {
                    $values['scheduled_workday'] = (bool) $schedule->is_working;
                }

                if (Schema::hasColumn($attendanceTable, 'clock_in_method')) {
                    $values['clock_in_method'] = $manual ? 'offline_manual_review' : 'offline_review';
                }
                if (Schema::hasColumn($attendanceTable, 'clocked_in_by')) {
                    $actorId = $offline->clocked_by ?: $offline->user_id;
                    $actorExists = User::where('tenant_id', $offline->tenant_id)->whereKey($actorId)->exists();
                    $values['clocked_in_by'] = $actorExists ? $actorId : $offline->user_id;
                }
                if (Schema::hasColumn($attendanceTable, 'clock_in_lat')) {
                    $values['clock_in_lat'] = $offline->lat;
                }
                if (Schema::hasColumn($attendanceTable, 'clock_in_lng')) {
                    $values['clock_in_lng'] = $offline->lng;
                }
                if (Schema::hasColumn($attendanceTable, 'geo_verified')) {
                    $values['geo_verified'] = $geoVerified;
                }
                if (Schema::hasColumn($attendanceTable, 'is_offline_upload')) {
                    $values['is_offline_upload'] = true;
                }
                if (Schema::hasColumn($attendanceTable, 'notes')) {
                    $values['notes'] = $manual
                        ? 'Administrator-approved legacy offline attendance by '.$user->name.'. Review note: '.$manualReason
                        : 'Approved by '.$user->name.' after stored QR'.($geoVerified ? ' and geofence' : '').' verification.';
                }

                $attendance = StaffAttendanceRecord::updateOrCreate(
                    [
                        'tenant_id' => $offline->tenant_id,
                        'user_id' => $offline->user_id,
                        'attendance_date' => $offline->attendance_date,
                    ],
                    $values
                );

                $offlineValues = ['status' => 'approved'];
                if (Schema::hasColumn($offline->getTable(), 'reject_reason')) {
                    $offlineValues['reject_reason'] = null;
                }
                $offline->update($offlineValues);

                return $attendance;
            });

            return response()->json([
                'message' => $manual
                    ? 'Legacy offline attendance approved after administrator review.'
                    : 'Offline attendance verified and approved.',
                'record_id' => $attendance->id,
            ]);
        } catch (Throwable $exception) {
            Log::error('Offline attendance review failed', [
                'tenant_id' => $user->tenant_id,
                'offline_record_id' => $offline->id,
                'action' => $data['action'],
                'exception' => $exception->getMessage(),
            ]);

            report($exception);

            return response()->json([
                'message' => 'This offline attendance record could not be processed safely. Refresh the review queue and try again, or reject the legacy record if it remains invalid.',
            ], 422);
        }
    }

    private function verificationState(StaffAttendanceSetting $settings, StaffOfflineClockIn $offline): array
    {
        $token = trim((string) $offline->qr_token);
        if ($token === '') return [false, 'No QR evidence was stored with this legacy offline record.'];

        $date = $offline->attendance_date?->format('Y-m-d') ?? '';
        $time = (string) $offline->clock_in_time;
        if ($settings->verifyOfflineVerificationProof($token, (int) $offline->user_id, $date, $time)) return [true, null];
        if ($settings->verifyStaticQrToken($token)) return [true, null];
        $personal = $settings->verifyPersonalQrToken($token);
        if ($personal && (int) $personal->id === (int) $offline->user_id) return [true, null];
        if ($date === today()->toDateString() && $settings->verifyQrToken($token)) return [true, null];

        return [false, 'This legacy QR proof expired or was invalidated by a QR reset.'];
    }

    private function guard(Request $request): User
    {
        $user = $request->user();
        $allowedRoles = ['admin', 'principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator'];
        abort_unless(
            $user && $user->tenant_id && $user->isTenantStaff()
                && (in_array($user->roleKey(), $allowedRoles, true) || $user->canManage('staff-attendance')),
            403,
            'Staff attendance management permission required.'
        );
        return $user;
    }
}
