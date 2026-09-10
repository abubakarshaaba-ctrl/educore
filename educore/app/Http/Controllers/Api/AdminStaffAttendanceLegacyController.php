<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendanceRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Temporary compatibility controller for already-installed Android builds.
 * New clients use AdminStaffAttendanceController canonical endpoints.
 */
class AdminStaffAttendanceLegacyController extends Controller
{
    public function manual(Request $request)
    {
        $actor = $this->guard($request);
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'attendance_date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(['early','present','late','absent'])],
            'clock_in_time' => ['nullable', 'date_format:H:i'],
            'clock_out_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        abort_unless(User::tenantStaff($actor->tenant_id)->whereKey($data['user_id'])->exists(), 422, 'The selected staff member is not available for this school.');

        $record = StaffAttendanceRecord::updateOrCreate([
            'tenant_id' => $actor->tenant_id,
            'user_id' => $data['user_id'],
            'attendance_date' => $data['attendance_date'],
        ], [
            'status' => $data['status'],
            'clock_in_time' => isset($data['clock_in_time']) ? $data['clock_in_time'].':00' : null,
            'clock_out_time' => isset($data['clock_out_time']) ? $data['clock_out_time'].':00' : null,
            'clock_in_method' => 'manual',
            'clocked_in_by' => $actor->id,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Attendance updated.', 'record_id' => $record->id]);
    }

    public function reviewOffline(Request $request, StaffAttendanceRecord $record)
    {
        $actor = $this->guard($request);
        abort_unless((int) $record->tenant_id === (int) $actor->tenant_id && $record->is_offline_upload, 404);
        $data = $request->validate(['action' => ['required', Rule::in(['approve','reject'])], 'reason' => ['nullable','string','max:1000']]);
        if ($data['action'] === 'reject' && blank($data['reason'] ?? null)) {
            return response()->json(['message' => 'A rejection reason is required.'], 422);
        }
        $record->update([
            'proxy_review_status' => $data['action'] === 'approve' ? 'approved' : 'rejected',
            'offline_rejection_reason' => $data['action'] === 'reject' ? $data['reason'] : null,
            'proxy_reviewed_by' => $actor->id,
            'proxy_reviewed_at' => now(),
        ]);
        return response()->json(['success' => true, 'message' => $data['action'] === 'approve' ? 'Offline attendance approved.' : 'Offline attendance rejected.', 'record_id' => $record->id]);
    }

    public function reviewProxy(Request $request, StaffAttendanceRecord $record)
    {
        $actor = $this->guard($request);
        abort_unless((int) $record->tenant_id === (int) $actor->tenant_id && $record->clock_in_method === 'proxy', 404);
        $data = $request->validate(['action' => ['required', Rule::in(['confirmed','flagged'])]]);
        $record->update(['proxy_review_status' => $data['action'], 'proxy_reviewed_by' => $actor->id, 'proxy_reviewed_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Proxy attendance review saved.', 'record_id' => $record->id]);
    }

    private function guard(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->tenant_id && ($user->isSuperAdmin() || $user->canManage('staff-attendance')), 403, 'You do not have permission to manage staff attendance.');
        return $user;
    }
}
