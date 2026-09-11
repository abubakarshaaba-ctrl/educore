<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminStaffProxyClockController extends Controller
{
    public function __invoke(Request $request)
    {
        $actor = $this->guard($request);
        $data = $request->validate([
            'staff_qr_token' => ['required', 'string', 'max:4096'],
            'date' => ['required', 'date_format:Y-m-d'],
            'clock_in_time' => ['required', 'date_format:H:i'],
            'clock_out_time' => ['nullable', 'date_format:H:i', 'after:clock_in_time'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'status' => ['nullable', Rule::in(['early', 'present', 'late', 'absent'])],
            'device' => ['nullable', 'string', 'max:255'],
        ]);

        abort_unless(
            $actor->tenant?->isSchoolOpenOn($data['date']) ?? true,
            422,
            'The selected date is not configured as a school day.'
        );

        $settings = StaffAttendanceSetting::forTenant($actor->tenant_id);
        $staff = $settings->verifyPersonalQrToken($data['staff_qr_token']);

        abort_unless(
            $staff
                && $staff->isTenantStaff()
                && (int) $staff->tenant_id === (int) $actor->tenant_id
                && $staff->wasEmployedOn($data['date']),
            422,
            'The scanned staff ID card is invalid, belongs to another school, or is not attendance-eligible on the selected date.'
        );

        $record = StaffAttendanceRecord::updateOrCreate([
            'tenant_id' => $actor->tenant_id,
            'user_id' => $staff->id,
            'attendance_date' => $data['date'],
        ], [
            'clock_in_time' => $data['clock_in_time'] . ':00',
            'clock_out_time' => isset($data['clock_out_time']) ? $data['clock_out_time'] . ':00' : null,
            'status' => $data['status'] ?? $settings->classifyClockIn($data['clock_in_time']),
            'clock_in_method' => 'proxy',
            'clocked_in_by' => $actor->id,
            'proxy_reason' => $data['reason'],
            'proxy_actor_ip' => $request->ip(),
            'proxy_device' => $data['device'] ?? substr((string) $request->userAgent(), 0, 255),
            'proxy_review_status' => 'recorded',
            'proxy_reviewed_by' => $actor->id,
            'proxy_reviewed_at' => now(),
            'notes' => 'Recorded by proxy after scanning staff ID card QR: ' . $data['reason'],
        ]);

        Log::notice('Staff attendance recorded by proxy from staff ID card QR', [
            'tenant_id' => $actor->tenant_id,
            'actor_user_id' => $actor->id,
            'staff_user_id' => $staff->id,
            'staff_id' => $staff->staff_id,
            'attendance_record_id' => $record->id,
            'reason' => $data['reason'],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded by proxy for '.$staff->name.' ('.($staff->staff_id ?: 'no Staff ID').').',
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
                'recorded_by_proxy' => true,
                'recorded_by' => $actor->name,
                'proxy_reason' => $record->proxy_reason,
            ],
        ], 201);
    }

    private function guard(Request $request): User
    {
        $user = $request->user();
        abort_unless(
            $user && $user->tenant_id && ($user->isSuperAdmin() || $user->canManage('staff-attendance')),
            403,
            'You do not have permission to manage staff attendance.'
        );

        return $user;
    }
}
