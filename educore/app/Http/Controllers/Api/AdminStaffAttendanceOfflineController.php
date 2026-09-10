<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendanceRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Administrative review feed for offline attendance.
 *
 * Includes successfully replayed offline attendance rows and rejected replay
 * events. Rejected actions are never silently discarded and retain the reason
 * supplied by the server-side validation path.
 */
class AdminStaffAttendanceOfflineController extends Controller
{
    public function __invoke(Request $request)
    {
        $actor = $request->user();
        abort_unless(
            $actor && $actor->tenant_id && ($actor->isSuperAdmin() || $actor->canManage('staff-attendance')),
            403,
            'You do not have permission to manage staff attendance.'
        );

        $records = StaffAttendanceRecord::query()
            ->where('tenant_id', $actor->tenant_id)
            ->where('is_offline_upload', true)
            ->with(['staff:id,name,staff_id', 'clockedInBy:id,name'])
            ->latest('updated_at')
            ->limit(150)
            ->get()
            ->map(fn (StaffAttendanceRecord $record): array => [
                'id' => $record->id,
                'staff_id' => $record->user_id,
                'staff_name' => $record->staff?->name,
                'staff_number' => $record->staff?->staff_id,
                'recorded_by' => $record->clockedInBy?->name,
                'date' => $record->attendance_date?->toDateString(),
                'clock_in' => $record->clock_in_time,
                'clock_out' => $record->clock_out_time,
                'status' => 'synced',
                'attendance_status' => $record->status,
                'client_uuid' => $record->client_uuid,
                'latitude' => $record->clock_in_lat,
                'longitude' => $record->clock_in_lng,
                'accuracy' => $record->location_accuracy,
                'rejection_reason' => $record->offline_rejection_reason,
                'updated_at' => $record->updated_at?->toIso8601String(),
            ]);

        $rejectedEvents = DB::table('staff_attendance_sync_events')
            ->where('tenant_id', $actor->tenant_id)
            ->where('status', 'rejected')
            ->orderByDesc('updated_at')
            ->limit(150)
            ->get();

        $staffById = User::query()
            ->where('tenant_id', $actor->tenant_id)
            ->whereIn('id', $rejectedEvents->pluck('staff_user_id')->filter()->unique()->values())
            ->get(['id', 'name', 'staff_id'])
            ->keyBy('id');

        $rejected = $rejectedEvents->map(function ($event) use ($staffById): array {
            $staff = $staffById->get($event->staff_user_id);
            $payload = is_string($event->payload) ? json_decode($event->payload, true) : (array) $event->payload;

            return [
                // Negative ids keep list keys distinct from attendance-record ids
                // without exposing a second Android DTO family.
                'id' => -((int) $event->id),
                'staff_id' => (int) $event->staff_user_id,
                'staff_name' => $staff?->name,
                'staff_number' => $staff?->staff_id,
                'recorded_by' => null,
                'date' => (string) $event->attendance_date,
                'clock_in' => $event->action === 'clock_in'
                    ? substr((string) ($event->local_timestamp ?? ''), 11, 8)
                    : null,
                'clock_out' => $event->action === 'clock_out'
                    ? substr((string) ($event->local_timestamp ?? ''), 11, 8)
                    : null,
                'status' => 'rejected',
                'attendance_status' => null,
                'client_uuid' => $event->client_uuid,
                'latitude' => isset($payload['latitude']) ? (float) $payload['latitude'] : null,
                'longitude' => isset($payload['longitude']) ? (float) $payload['longitude'] : null,
                'accuracy' => isset($payload['accuracy']) ? (float) $payload['accuracy'] : null,
                'rejection_reason' => $event->rejection_reason,
                'updated_at' => $event->updated_at,
            ];
        });

        return response()->json([
            'records' => $rejected->concat($records)->values(),
        ]);
    }
}
