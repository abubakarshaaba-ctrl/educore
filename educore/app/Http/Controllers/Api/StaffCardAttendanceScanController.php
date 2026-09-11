<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StaffCardAttendanceScanController extends Controller
{
    public function __invoke(Request $request)
    {
        $actor = $this->guardStaff($request);
        $data = $request->validate([
            'staff_qr_token' => ['required', 'string', 'max:4096'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'device' => ['nullable', 'string', 'max:255'],
        ]);

        $now = now();
        $date = $now->toDateString();
        abort_unless(
            $actor->tenant?->isSchoolOpenOn($date) ?? true,
            422,
            'Today is not configured as a school day.'
        );

        $settings = StaffAttendanceSetting::forTenant($actor->tenant_id);
        $staff = $settings->verifyPersonalQrToken($data['staff_qr_token']);

        abort_unless(
            $staff
                && $staff->isTenantStaff()
                && (int) $staff->tenant_id === (int) $actor->tenant_id
                && $staff->wasEmployedOn($date),
            422,
            'The scanned staff ID card is invalid, belongs to another school, or is not attendance-eligible today.'
        );

        if ($settings->geo_enabled) {
            abort_unless(
                array_key_exists('latitude', $data)
                    && $data['latitude'] !== null
                    && array_key_exists('longitude', $data)
                    && $data['longitude'] !== null,
                422,
                'Location is required to record attendance for this school.'
            );
            $distance = $settings->distanceTo((float) $data['latitude'], (float) $data['longitude']);
            abort_unless(
                $distance <= (float) $settings->geo_radius_meters,
                422,
                'Attendance scan is outside the school attendance radius.'
            );
        }

        [$record, $action] = DB::transaction(function () use ($actor, $staff, $settings, $data, $now, $date): array {
            $record = StaffAttendanceRecord::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('user_id', $staff->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->first();

            if (!$record) {
                $record = new StaffAttendanceRecord([
                    'tenant_id' => $actor->tenant_id,
                    'user_id' => $staff->id,
                    'attendance_date' => $date,
                ]);
            }

            if (!$record->clock_in_time) {
                $time = $now->format('H:i:s');
                $record->clock_in_time = $time;
                $record->status = $settings->classifyClockIn($time);
                $record->clock_in_method = 'staff_card_qr';
                $record->clocked_in_by = $actor->id;
                $record->clock_in_lat = $data['latitude'] ?? null;
                $record->clock_in_lng = $data['longitude'] ?? null;
                $record->location_accuracy = $data['accuracy'] ?? null;
                $record->notes = 'Staff ID card scanned in real time by '.$actor->name.'.';
                $action = 'clock_in';
            } elseif (!$record->clock_out_time) {
                $record->clock_out_time = $now->format('H:i:s');
                $record->notes = trim(($record->notes ? $record->notes.' ' : '').'Staff ID card scanned for real-time clock-out by '.$actor->name.'.');
                $action = 'clock_out';
            } else {
                abort(422, 'Attendance is already complete for this staff member today.');
            }

            $record->proxy_actor_ip = $requestIp = request()->ip();
            $record->proxy_device = $data['device'] ?? substr((string) request()->userAgent(), 0, 255);
            $record->save();

            return [$record, $action];
        });

        Log::notice('Staff ID card attendance scan recorded', [
            'tenant_id' => $actor->tenant_id,
            'scanner_user_id' => $actor->id,
            'staff_user_id' => $staff->id,
            'staff_id' => $staff->staff_id,
            'attendance_record_id' => $record->id,
            'action' => $action,
            'server_timestamp' => $now->toIso8601String(),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'action' => $action,
            'server_timestamp' => $now->toIso8601String(),
            'message' => ($action === 'clock_in' ? 'Clock-in' : 'Clock-out').' recorded for '.$staff->name.' ('.($staff->staff_id ?: 'no Staff ID').').',
            'record' => [
                'id' => $record->id,
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'staff_number' => $staff->staff_id,
                'date' => $record->attendance_date?->toDateString(),
                'status' => $record->status,
                'clock_in' => $record->clock_in_time,
                'clock_out' => $record->clock_out_time,
                'method' => $record->clock_in_method,
                'recorded_by' => $actor->name,
            ],
        ], 201);
    }

    private function guardStaff(Request $request): User
    {
        $user = $request->user();
        abort_unless(
            $user && $user->tenant_id && $user->isTenantStaff(),
            403,
            'A staff account from this school is required to scan staff ID attendance.'
        );

        return $user;
    }
}
