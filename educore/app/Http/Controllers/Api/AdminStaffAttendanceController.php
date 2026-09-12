<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use App\Models\StaffOfflineClockIn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminStaffAttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $date = $request->date('date')?->toDateString() ?? today()->toDateString();
        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);

        $records = StaffAttendanceRecord::where('tenant_id', $user->tenant_id)
            ->whereDate('attendance_date', $date)
            ->with(['staff:id,name,staff_id', 'clockedInBy:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $search = trim((string) $request->input('q'));
                $q->whereHas('staff', fn ($staff) => $staff
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('staff_id', 'like', "%{$search}%"));
            })
            ->orderBy('clock_in_time')
            ->get();

        $eligible = User::attendanceEligibleOn($user->tenant_id, $date)->count();
        $pendingOffline = StaffOfflineClockIn::where('tenant_id', $user->tenant_id)->where('status', 'pending')->count();
        $pendingProxy = StaffAttendanceRecord::where('tenant_id', $user->tenant_id)->where('proxy_review_status', 'pending')->count();

        return response()->json([
            'date' => $date,
            'summary' => [
                'eligible' => $eligible,
                'clocked_in' => $records->whereNotNull('clock_in_time')->count(),
                'early' => $records->where('status', 'early')->count(),
                'present' => $records->where('status', 'present')->count(),
                'late' => $records->where('status', 'late')->count(),
                'absent' => max(0, $eligible - $records->whereNotNull('clock_in_time')->count()),
            ],
            'pending' => ['offline' => $pendingOffline, 'proxy' => $pendingProxy],
            'records' => $records->map(fn (StaffAttendanceRecord $record): array => [
                'id' => $record->id,
                'user_id' => $record->user_id,
                'staff' => $record->staff?->name,
                'staff_id' => $record->staff?->staff_id,
                'status' => $record->status,
                'clock_in' => $record->clock_in_time,
                'clock_out' => $record->clock_out_time,
                'method' => $record->clock_in_method,
                'clocked_in_by' => $record->clockedInBy?->name,
                'geo_verified' => (bool) $record->geo_verified,
                'offline' => (bool) $record->is_offline_upload,
                'notes' => $record->notes,
                'proxy_review_status' => $record->proxy_review_status,
            ])->values(),
            'settings' => $this->settingsPayload($settings),
        ]);
    }

    public function report(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $month = $request->integer('month', now()->month);
        $year = $request->integer('year', now()->year);
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth();

        $workingDays = collect();
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if (!$day->isWeekend()) $workingDays->push($day->toDateString());
        }

        $records = StaffAttendanceRecord::where('tenant_id', $user->tenant_id)
            ->whereBetween('attendance_date', [$start, $end])
            ->get()
            ->groupBy('user_id');

        $staff = User::tenantStaff($user->tenant_id)->orderBy('name')->get()->map(function (User $member) use ($records): array {
            $rows = $records->get($member->id, collect());
            $onTime = $rows->whereIn('status', ['early', 'present'])->count();
            $attended = $rows->whereIn('status', ['early', 'present', 'late'])->count();
            return [
                'id' => $member->id,
                'name' => $member->name,
                'staff_id' => $member->staff_id,
                'early' => $rows->where('status', 'early')->count(),
                'present' => $rows->where('status', 'present')->count(),
                'late' => $rows->where('status', 'late')->count(),
                'absent' => $rows->where('status', 'absent')->count(),
                'days' => $rows->count(),
                'punctuality' => $attended > 0 ? (int) round(($onTime / $attended) * 100) : 0,
                'detail' => $rows->sortBy('attendance_date')->map(fn (StaffAttendanceRecord $row): array => [
                    'date' => $row->attendance_date?->format('Y-m-d'),
                    'status' => $row->status,
                    'clock_in' => $row->clock_in_time,
                    'clock_out' => $row->clock_out_time,
                ])->values(),
            ];
        });

        return response()->json(['month' => $month, 'year' => $year, 'working_days' => $workingDays, 'staff' => $staff]);
    }

    public function manualOverride(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['early', 'present', 'late', 'absent'])],
            'clock_in_time' => ['nullable', 'date_format:H:i'],
            'clock_out_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $target = User::tenantStaff($user->tenant_id)->findOrFail((int) $data['user_id']);
        $record = StaffAttendanceRecord::updateOrCreate(
            [
                'tenant_id' => $user->tenant_id,
                'user_id' => $target->id,
                'attendance_date' => $data['attendance_date'],
            ],
            [
                'status' => $data['status'],
                'clock_in_time' => isset($data['clock_in_time']) ? $data['clock_in_time'].':00' : null,
                'clock_out_time' => isset($data['clock_out_time']) ? $data['clock_out_time'].':00' : null,
                'clock_in_method' => 'manual',
                'clocked_in_by' => $user->id,
                'geo_verified' => false,
                'notes' => $data['notes'] ?? 'Manual attendance override from mobile administration.',
            ]
        );

        return response()->json(['message' => 'Attendance record saved.', 'record_id' => $record->id]);
    }

    public function offlineQueue(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $records = StaffOfflineClockIn::where('tenant_id', $user->tenant_id)
            ->where('status', 'pending')
            ->with(['staff:id,name,staff_id', 'clockedBy:id,name'])
            ->orderByDesc('attendance_date')
            ->get()
            ->map(fn (StaffOfflineClockIn $record): array => [
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
            ]);

        return response()->json(['records' => $records]);
    }

    public function processOffline(Request $request, int $record): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $offline = StaffOfflineClockIn::where('tenant_id', $user->tenant_id)->where('status', 'pending')->findOrFail($record);

        if ($data['action'] === 'reject') {
            $offline->update(['status' => 'rejected', 'reject_reason' => $data['reason'] ?? 'Rejected during administrator review.']);
            return response()->json(['message' => 'Offline attendance rejected.']);
        }

        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $qrValid = $settings->verifyStaticQrToken((string) $offline->qr_token)
            || $settings->verifyQrToken((string) $offline->qr_token)
            || (bool) $settings->verifyPersonalQrToken((string) $offline->qr_token);
        abort_unless($qrValid, 422, 'The queued attendance QR cannot be verified. Reject this record instead.');

        $geoVerified = false;
        if ($settings->geo_enabled) {
            abort_if($offline->lat === null || $offline->lng === null, 422, 'Location evidence is missing; this offline record cannot be approved.');
            $distance = $settings->distanceTo((float) $offline->lat, (float) $offline->lng);
            abort_if($distance > $settings->geo_radius_meters, 422, 'Location evidence is outside the configured school geofence.');
            $geoVerified = true;
        }

        $attendance = DB::transaction(function () use ($offline, $user, $geoVerified): StaffAttendanceRecord {
            $record = StaffAttendanceRecord::updateOrCreate(
                [
                    'tenant_id' => $offline->tenant_id,
                    'user_id' => $offline->user_id,
                    'attendance_date' => $offline->attendance_date,
                ],
                [
                    'status' => 'present',
                    'clock_in_time' => $offline->clock_in_time,
                    'clock_in_method' => 'offline_review',
                    'clocked_in_by' => $offline->clocked_by ?: $offline->user_id,
                    'clock_in_lat' => $offline->lat,
                    'clock_in_lng' => $offline->lng,
                    'geo_verified' => $geoVerified,
                    'is_offline_upload' => true,
                    'notes' => 'Approved by '.$user->name.' after QR'.($geoVerified ? ' and geofence' : '').' verification.',
                ]
            );
            $offline->update(['status' => 'approved', 'reject_reason' => null]);
            return $record;
        });

        return response()->json(['message' => 'Offline attendance verified and approved.', 'record_id' => $attendance->id]);
    }

    public function proxyReviews(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $records = StaffAttendanceRecord::where('tenant_id', $user->tenant_id)
            ->where('proxy_review_status', 'pending')
            ->with(['staff:id,name,staff_id,passport_photo', 'clockedInBy:id,name'])
            ->orderByDesc('attendance_date')
            ->get()
            ->map(fn (StaffAttendanceRecord $record): array => [
                'id' => $record->id,
                'user_id' => $record->user_id,
                'staff' => $record->staff?->name,
                'staff_id' => $record->staff?->staff_id,
                'attendance_date' => $record->attendance_date?->format('Y-m-d'),
                'clock_in' => $record->clock_in_time,
                'clocked_in_by' => $record->clockedInBy?->name,
                'proxy_review_status' => $record->proxy_review_status,
                'has_proxy_photo' => filled($record->proxy_photo),
                'has_profile_photo' => filled($record->staff?->passport_photo),
            ]);
        return response()->json(['records' => $records]);
    }

    public function decideProxy(Request $request, int $record): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate(['action' => ['required', Rule::in(['approve', 'reject'])]]);
        $attendance = StaffAttendanceRecord::where('tenant_id', $user->tenant_id)
            ->where('proxy_review_status', 'pending')
            ->findOrFail($record);

        $attendance->update([
            'proxy_review_status' => $data['action'] === 'approve' ? 'approved' : 'rejected',
            'proxy_reviewed_by' => $user->id,
            'proxy_reviewed_at' => now(),
            'notes' => trim(($attendance->notes ? $attendance->notes.' ' : '').'Proxy evidence '.($data['action'] === 'approve' ? 'approved' : 'rejected').' by '.$user->name.'.'),
        ]);

        return response()->json(['message' => 'Proxy attendance review updated.', 'record_id' => $attendance->id]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'resumption_time' => ['required', 'date_format:H:i'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'closing_time' => ['required', 'date_format:H:i'],
            'geo_enabled' => ['required', 'boolean'],
            'geo_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'geo_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'geo_radius_meters' => ['nullable', 'integer', 'min:10', 'max:2000'],
        ]);
        $data['resumption_time'] .= ':00';
        $data['closing_time'] .= ':00';
        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $settings->update($data);
        return response()->json(['message' => 'Staff attendance settings updated.', 'settings' => $this->settingsPayload($settings->fresh())]);
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

    private function settingsPayload(StaffAttendanceSetting $settings): array
    {
        return [
            'resumption_time' => substr((string) $settings->resumption_time, 0, 5),
            'grace_minutes' => (int) $settings->grace_minutes,
            'closing_time' => substr((string) $settings->closing_time, 0, 5),
            'geo_enabled' => (bool) $settings->geo_enabled,
            'geo_lat' => $settings->geo_lat === null ? null : (float) $settings->geo_lat,
            'geo_lng' => $settings->geo_lng === null ? null : (float) $settings->geo_lng,
            'geo_radius_meters' => $settings->geo_radius_meters === null ? null : (int) $settings->geo_radius_meters,
        ];
    }
}
