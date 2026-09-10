<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use App\Models\StaffOfflineClockIn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminStaffAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:early,present,late,absent'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $date = isset($data['date']) ? Carbon::parse($data['date'])->toDateString() : today()->toDateString();
        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $records = StaffAttendanceRecord::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereDate('attendance_date', $date)
            ->with(['staff:id,name,staff_id', 'clockedInBy:id,name'])
            ->get()
            ->keyBy('user_id');

        $eligible = User::attendanceEligibleOn($user->tenant_id, $date)
            ->orderBy('name')
            ->get(['id', 'name', 'staff_id']);

        $rows = $eligible->map(function (User $member) use ($records) {
            $record = $records->get($member->id);
            return [
                'id' => $record?->id,
                'user_id' => $member->id,
                'staff' => $member->name,
                'staff_id' => $member->staff_id,
                'status' => $record?->status ?? 'absent',
                'clock_in' => $record?->clock_in_time,
                'clock_out' => $record?->clock_out_time,
                'method' => $record?->clock_in_method,
                'clocked_in_by' => $record?->clockedInBy?->name,
                'geo_verified' => (bool) ($record?->geo_verified ?? false),
                'offline' => (bool) ($record?->is_offline_upload ?? false),
                'notes' => $record?->notes,
                'proxy_review_status' => $record?->proxy_review_status,
            ];
        });

        if (! empty($data['status'])) {
            $rows = $rows->where('status', $data['status']);
        }
        if (! empty($data['q'])) {
            $needle = mb_strtolower(trim($data['q']));
            $rows = $rows->filter(fn (array $row) => str_contains(mb_strtolower((string) $row['staff']), $needle)
                || str_contains(mb_strtolower((string) ($row['staff_id'] ?? '')), $needle));
        }

        $clockedIn = $records->whereNotNull('clock_in_time')->count();
        $eligibleCount = $eligible->count();

        return response()->json([
            'date' => $date,
            'summary' => [
                'eligible' => $eligibleCount,
                'clocked_in' => $clockedIn,
                'early' => $records->where('status', 'early')->count(),
                'present' => $records->where('status', 'present')->count(),
                'late' => $records->where('status', 'late')->count(),
                'absent' => max(0, $eligibleCount - $records->whereIn('status', ['early', 'present', 'late'])->count()),
            ],
            'records' => $rows->values(),
            'pending' => [
                'offline' => StaffOfflineClockIn::where('tenant_id', $user->tenant_id)->where('status', 'pending')->count(),
                'proxy' => StaffAttendanceRecord::where('tenant_id', $user->tenant_id)->where('proxy_review_status', 'pending')->count(),
            ],
            'settings' => $this->settingsPayload($settings),
        ]);
    }

    public function report(Request $request)
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);
        $month = (int) ($data['month'] ?? now()->month);
        $year = (int) ($data['year'] ?? now()->year);
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth();

        $workingDays = collect();
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if ($day->isWeekday()) {
                $workingDays->push($day->toDateString());
            }
        }

        $records = StaffAttendanceRecord::where('tenant_id', $user->tenant_id)
            ->whereBetween('attendance_date', [$start, $end])
            ->get()
            ->groupBy('user_id');

        $staff = User::tenantStaff($user->tenant_id)->orderBy('name')->get()->map(function (User $member) use ($records, $workingDays, $user) {
            $rows = $records->get($member->id, collect());
            $eligibleDays = $workingDays->filter(fn (string $date) => User::attendanceEligibleOn($user->tenant_id, $date)->whereKey($member->id)->exists());
            $counts = ['early' => 0, 'present' => 0, 'late' => 0, 'absent' => 0];
            $detail = [];

            foreach ($eligibleDays as $date) {
                $record = $rows->first(fn (StaffAttendanceRecord $row) => $row->attendance_date->toDateString() === $date);
                $status = $record?->status ?? 'absent';
                $counts[$status] = ($counts[$status] ?? 0) + 1;
                $detail[] = [
                    'date' => $date,
                    'status' => $status,
                    'clock_in' => $record?->clock_in_time,
                    'clock_out' => $record?->clock_out_time,
                ];
            }

            $total = $eligibleDays->count();
            return [
                'id' => $member->id,
                'name' => $member->name,
                'staff_id' => $member->staff_id,
                'early' => $counts['early'],
                'present' => $counts['present'],
                'late' => $counts['late'],
                'absent' => $counts['absent'],
                'days' => $total,
                'punctuality' => $total > 0 ? (int) round((($counts['early'] + $counts['present']) / $total) * 100) : 0,
                'detail' => $detail,
            ];
        });

        return response()->json([
            'month' => $month,
            'year' => $year,
            'working_days' => $workingDays->values(),
            'staff' => $staff,
        ]);
    }

    public function offlineQueue(Request $request)
    {
        $user = $this->guard($request);
        $records = StaffOfflineClockIn::with(['staff:id,name,staff_id', 'clockedBy:id,name'])
            ->where('tenant_id', $user->tenant_id)
            ->where('status', 'pending')
            ->latest()
            ->limit(100)
            ->get();

        return response()->json([
            'records' => $records->map(fn (StaffOfflineClockIn $row) => [
                'id' => $row->id,
                'user_id' => $row->user_id,
                'staff' => $row->staff?->name,
                'staff_id' => $row->staff?->staff_id,
                'clocked_by' => $row->clockedBy?->name,
                'attendance_date' => $row->attendance_date?->toDateString(),
                'clock_in' => $row->clock_in_time,
                'lat' => $row->lat,
                'lng' => $row->lng,
                'status' => $row->status,
            ])->values(),
        ]);
    }

    public function processOffline(Request $request, StaffOfflineClockIn $record)
    {
        $user = $this->guard($request);
        abort_unless($record->tenant_id === $user->tenant_id, 403);
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'max:240'],
        ]);
        abort_unless($record->status === 'pending', 422, 'This offline record has already been processed.');

        if ($data['action'] === 'reject') {
            $record->update(['status' => 'rejected', 'reject_reason' => $data['reason'] ?? null]);
            return response()->json(['message' => 'Offline attendance rejected.']);
        }

        $date = $record->attendance_date->toDateString();
        abort_unless(User::attendanceEligibleOn($user->tenant_id, $date)->whereKey($record->user_id)->exists(), 422, 'Staff member was not eligible on this date.');
        abort_if(StaffAttendanceRecord::where('tenant_id', $user->tenant_id)->where('user_id', $record->user_id)->whereDate('attendance_date', $date)->whereNotNull('clock_in_time')->exists(), 422, 'Attendance already exists for this staff member and date.');

        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        StaffAttendanceRecord::updateOrCreate(
            ['tenant_id' => $user->tenant_id, 'user_id' => $record->user_id, 'attendance_date' => $date],
            [
                'status' => $settings->classifyClockIn($record->clock_in_time),
                'clock_in_time' => $record->clock_in_time,
                'clock_in_method' => 'offline',
                'clocked_in_by' => $record->clocked_by,
                'clock_in_lat' => $record->lat,
                'clock_in_lng' => $record->lng,
                'geo_verified' => false,
                'is_offline_upload' => true,
            ]
        );
        $record->update(['status' => 'applied']);

        return response()->json(['message' => 'Offline attendance approved and applied.']);
    }

    public function proxyReviews(Request $request)
    {
        $user = $this->guard($request);
        $records = StaffAttendanceRecord::with(['staff:id,name,staff_id,passport_photo', 'clockedInBy:id,name'])
            ->where('tenant_id', $user->tenant_id)
            ->where('proxy_review_status', 'pending')
            ->latest('attendance_date')
            ->limit(100)
            ->get();

        return response()->json([
            'records' => $records->map(fn (StaffAttendanceRecord $row) => [
                'id' => $row->id,
                'user_id' => $row->user_id,
                'staff' => $row->staff?->name,
                'staff_id' => $row->staff?->staff_id,
                'attendance_date' => $row->attendance_date?->toDateString(),
                'clock_in' => $row->clock_in_time,
                'clocked_in_by' => $row->clockedInBy?->name,
                'proxy_review_status' => $row->proxy_review_status,
                'has_proxy_photo' => filled($row->proxy_photo),
                'has_profile_photo' => filled($row->staff?->passport_photo),
            ])->values(),
        ]);
    }

    public function decideProxy(Request $request, StaffAttendanceRecord $record)
    {
        $user = $this->guard($request);
        abort_unless($record->tenant_id === $user->tenant_id, 403);
        $data = $request->validate(['action' => ['required', 'in:confirmed,flagged']]);
        abort_unless($record->proxy_review_status === 'pending', 422, 'This proxy attendance has already been reviewed.');

        $record->update([
            'proxy_review_status' => $data['action'],
            'proxy_reviewed_by' => $user->id,
            'proxy_reviewed_at' => now(),
        ]);

        return response()->json(['message' => 'Proxy attendance marked as '.$data['action'].'.']);
    }

    public function manualOverride(Request $request)
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', 'in:early,present,late,absent'],
            'clock_in_time' => ['nullable', 'date_format:H:i'],
            'clock_out_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:200'],
        ]);
        $date = Carbon::parse($data['attendance_date'])->toDateString();
        abort_unless(User::attendanceEligibleOn($user->tenant_id, $date)->whereKey($data['user_id'])->exists(), 422, 'Select a staff member employed on the attendance date.');

        $record = StaffAttendanceRecord::updateOrCreate(
            ['tenant_id' => $user->tenant_id, 'user_id' => $data['user_id'], 'attendance_date' => $date],
            [
                'status' => $data['status'],
                'clock_in_time' => ! empty($data['clock_in_time']) ? $data['clock_in_time'].':00' : null,
                'clock_out_time' => ! empty($data['clock_out_time']) ? $data['clock_out_time'].':00' : null,
                'clock_in_method' => 'manual',
                'clocked_in_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return response()->json(['message' => 'Attendance record updated.', 'record_id' => $record->id]);
    }

    public function updateSettings(Request $request)
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

    public function resetQr(Request $request)
    {
        $user = $this->guard($request);
        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $settings->resetStaticQr();

        return response()->json(['message' => 'School attendance QR reset. Previously printed copies are now invalid.']);
    }

    private function guard(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && in_array($user->roleKey(), [
            'admin', 'principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator',
        ], true), 403, 'School administrator access required.');

        return $user;
    }

    private function settingsPayload(StaffAttendanceSetting $settings): array
    {
        return [
            'resumption_time' => substr((string) $settings->resumption_time, 0, 5),
            'grace_minutes' => $settings->grace_minutes,
            'closing_time' => substr((string) $settings->closing_time, 0, 5),
            'geo_enabled' => (bool) $settings->geo_enabled,
            'geo_lat' => $settings->geo_lat,
            'geo_lng' => $settings->geo_lng,
            'geo_radius_meters' => $settings->geo_radius_meters,
        ];
    }
}
