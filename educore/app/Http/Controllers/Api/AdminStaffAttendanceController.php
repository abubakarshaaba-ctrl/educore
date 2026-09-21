<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use App\Models\StaffOfflineClockIn;
use App\Models\User;
use App\Services\StaffAttendanceScheduleService;
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
        $scheduleService = app(StaffAttendanceScheduleService::class);
        $daySchedule = $scheduleService->forDate(
            (int) $user->tenant_id,
            $date,
            $settings
        );

        $allRecords = StaffAttendanceRecord::where('tenant_id', $user->tenant_id)
            ->whereDate('attendance_date', $date)
            ->get();

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

        $eligible = $daySchedule->is_working
            ? User::attendanceEligibleOn($user->tenant_id, $date)->count()
            : 0;
        $clockedIn = $allRecords->whereNotNull('clock_in_time')->count();
        $onTime = $allRecords->whereIn('status', ['early', 'present'])->count();
        $attended = $allRecords->whereIn('status', ['early', 'present', 'late'])->count();
        $pendingOffline = StaffOfflineClockIn::where('tenant_id', $user->tenant_id)->where('status', 'pending')->count();
        $pendingProxy = StaffAttendanceRecord::where('tenant_id', $user->tenant_id)->where('proxy_review_status', 'pending')->count();

        return response()->json([
            'date' => $date,
            'summary' => [
                'eligible' => $eligible,
                'clocked_in' => $clockedIn,
                'early' => $allRecords->where('status', 'early')->count(),
                'present' => $allRecords->where('status', 'present')->count(),
                'late' => $allRecords->where('status', 'late')->count(),
                'absent' => max(0, $eligible - $clockedIn),
                'punctuality_rate' => $attended > 0 ? (int) round(($onTime / $attended) * 100) : 0,
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
            'day_schedule' => $this->workingDayPayload($daySchedule),
            'working_days' => $scheduleService->workingDays(
                (int) $user->tenant_id,
                $settings
            )->map(fn ($day): array => $this->workingDayPayload($day))->values(),
        ]);
    }

    public function report(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $month = $request->integer('month', now()->month);
        $year = $request->integer('year', now()->year);
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth();

        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $workingDays = collect(
            app(StaffAttendanceScheduleService::class)->workingDatesForMonth(
                (int) $user->tenant_id,
                $start,
                $end,
                $settings
            )
        );

        $attendanceRecords = StaffAttendanceRecord::where('tenant_id', $user->tenant_id)
            ->whereBetween('attendance_date', [$start, $end])
            ->get();
        $records = $attendanceRecords->groupBy('user_id');

        $staff = User::tenantStaff($user->tenant_id)->orderBy('name')->get()->map(function (User $member) use ($records, $workingDays): array {
            $rows = $records->get($member->id, collect());
            $scheduledRows = $rows->filter(
                fn (StaffAttendanceRecord $row) =>
                    $workingDays->contains($row->attendance_date?->format('Y-m-d'))
                    && in_array($row->status, ['early', 'present', 'late', 'absent'], true)
            );
            $onTime = $scheduledRows->whereIn('status', ['early', 'present'])->count();
            $attended = $scheduledRows->whereIn('status', ['early', 'present', 'late'])->count();
            $absent = max(0, $workingDays->count() - $attended);
            return [
                'id' => $member->id,
                'name' => $member->name,
                'staff_id' => $member->staff_id,
                'early' => $scheduledRows->where('status', 'early')->count(),
                'present' => $scheduledRows->where('status', 'present')->count(),
                'late' => $scheduledRows->where('status', 'late')->count(),
                'absent' => $absent,
                'days' => $workingDays->count(),
                'punctuality' => $workingDays->count() > 0
                    ? (int) round(($onTime / $workingDays->count()) * 100)
                    : 0,
                'detail' => $rows->sortBy('attendance_date')->map(fn (StaffAttendanceRecord $row): array => [
                    'date' => $row->attendance_date?->format('Y-m-d'),
                    'status' => $row->status,
                    'clock_in' => $row->clock_in_time,
                    'clock_out' => $row->clock_out_time,
                ])->values(),
            ];
        });

        $onTime = $attendanceRecords->whereIn('status', ['early', 'present'])->count();
        $attended = $attendanceRecords->whereIn('status', ['early', 'present', 'late'])->count();
        $late = $attendanceRecords->where('status', 'late')->count();

        return response()->json([
            'month' => $month,
            'year' => $year,
            'working_days' => $workingDays,
            'summary' => [
                'on_time' => $onTime,
                'attended' => $attended,
                'late' => $late,
                'punctuality_rate' => $attended > 0 ? (int) round(($onTime / $attended) * 100) : 0,
            ],
            'staff' => $staff,
        ]);
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
        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $schedule = app(StaffAttendanceScheduleService::class)->forDate(
            (int) $user->tenant_id,
            $data['attendance_date'],
            $settings
        );
        $clockOut = isset($data['clock_out_time']) ? $data['clock_out_time'].':00' : null;
        $closing = $schedule->closing_time
            ? substr((string) $schedule->closing_time, 0, 8)
            : null;

        $record = StaffAttendanceRecord::updateOrCreate(
            [
                'tenant_id' => $user->tenant_id,
                'user_id' => $target->id,
                'attendance_date' => $data['attendance_date'],
            ],
            [
                'status' => $data['status'],
                'clock_in_time' => isset($data['clock_in_time']) ? $data['clock_in_time'].':00' : null,
                'clock_out_time' => $clockOut,
                'expected_resumption_time' => $schedule->resumption_time
                    ? substr((string) $schedule->resumption_time, 0, 8)
                    : null,
                'expected_closing_time' => $closing,
                'grace_minutes' => (int) $schedule->grace_minutes,
                'scheduled_workday' => (bool) $schedule->is_working,
                'departure_status' => $clockOut && $closing
                    ? ($clockOut < $closing ? 'early' : 'on_time')
                    : null,
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

        $attendanceDate = $offline->attendance_date->toDateString();
        $scheduleService = app(StaffAttendanceScheduleService::class);
        $schedule = $scheduleService->forDate(
            (int) $offline->tenant_id,
            $attendanceDate,
            $settings
        );

        $attendance = DB::transaction(function () use ($offline, $user, $geoVerified, $attendanceDate, $scheduleService, $schedule): StaffAttendanceRecord {
            $record = StaffAttendanceRecord::updateOrCreate(
                [
                    'tenant_id' => $offline->tenant_id,
                    'user_id' => $offline->user_id,
                    'attendance_date' => $offline->attendance_date,
                ],
                [
                    'status' => $scheduleService->classifyArrival(
                        $schedule,
                        $attendanceDate,
                        (string) $offline->clock_in_time
                    ),
                    'clock_in_time' => $offline->clock_in_time,
                    'expected_resumption_time' => $schedule->resumption_time
                        ? substr((string) $schedule->resumption_time, 0, 8)
                        : null,
                    'expected_closing_time' => $schedule->closing_time
                        ? substr((string) $schedule->closing_time, 0, 8)
                        : null,
                    'grace_minutes' => (int) $schedule->grace_minutes,
                    'scheduled_workday' => (bool) $schedule->is_working,
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

        // The first native Android attendance build used the UI-oriented values
        // "confirmed" and "flagged". Normalize those legacy aliases so already-
        // installed APKs remain compatible while the canonical API contract stays
        // "approve" / "reject".
        $action = strtolower(trim((string) $request->input('action')));
        $action = match ($action) {
            'confirm', 'confirmed', 'approved' => 'approve',
            'flag', 'flagged', 'rejected' => 'reject',
            default => $action,
        };
        $request->merge(['action' => $action]);

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
            // Legacy scalar fields remain accepted for older native clients.
            'resumption_time' => ['nullable', 'date_format:H:i'],
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'closing_time' => ['nullable', 'date_format:H:i'],
            'working_days' => ['nullable', 'array'],
            'working_days.*.day_of_week' => [
                'required_with:working_days',
                Rule::in(StaffAttendanceScheduleService::DAYS),
            ],
            'working_days.*.is_working' => ['required_with:working_days', 'boolean'],
            'working_days.*.resumption_time' => ['nullable', 'date_format:H:i'],
            'working_days.*.closing_time' => ['nullable', 'date_format:H:i'],
            'working_days.*.grace_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'geo_enabled' => ['required', 'boolean'],
            'geo_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'geo_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'geo_radius_meters' => ['nullable', 'integer', 'min:10', 'max:2000'],
        ]);

        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $scheduleService = app(StaffAttendanceScheduleService::class);

        if (! empty($data['working_days'])) {
            $days = collect($data['working_days'])
                ->keyBy('day_of_week')
                ->map(fn (array $row): array => [
                    'is_working' => (bool) $row['is_working'],
                    'resumption_time' => $row['resumption_time'] ?? null,
                    'closing_time' => $row['closing_time'] ?? null,
                    'grace_minutes' => (int) ($row['grace_minutes'] ?? 0),
                ])
                ->all();

            $saved = $scheduleService->save((int) $user->tenant_id, $days);
            $fallback = $saved->first(fn ($day) => (bool) $day->is_working);

            if ($fallback) {
                $settings->update([
                    'resumption_time' => substr((string) $fallback->resumption_time, 0, 8),
                    'grace_minutes' => (int) $fallback->grace_minutes,
                    'closing_time' => substr((string) $fallback->closing_time, 0, 8),
                ]);
            }
        } elseif (
            isset($data['resumption_time'], $data['grace_minutes'], $data['closing_time'])
        ) {
            $settings->update([
                'resumption_time' => $data['resumption_time'].':00',
                'grace_minutes' => (int) $data['grace_minutes'],
                'closing_time' => $data['closing_time'].':00',
            ]);
        }

        $settings->update([
            'geo_enabled' => (bool) $data['geo_enabled'],
            'geo_lat' => $data['geo_lat'] ?? null,
            'geo_lng' => $data['geo_lng'] ?? null,
            'geo_radius_meters' => $data['geo_radius_meters'] ?? $settings->geo_radius_meters,
        ]);

        $workingDays = $scheduleService->workingDays(
            (int) $user->tenant_id,
            $settings->fresh()
        );

        return response()->json([
            'message' => 'Conventional curriculum work hours and staff attendance settings updated.',
            'settings' => $this->settingsPayload($settings->fresh()),
            'working_days' => $workingDays
                ->map(fn ($day): array => $this->workingDayPayload($day))
                ->values(),
        ]);
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
    private function workingDayPayload($day): array
    {
        return [
            'day_of_week' => (string) $day->day_of_week,
            'is_working' => (bool) $day->is_working,
            'resumption_time' => $day->resumption_time
                ? substr((string) $day->resumption_time, 0, 5)
                : null,
            'closing_time' => $day->closing_time
                ? substr((string) $day->closing_time, 0, 5)
                : null,
            'grace_minutes' => (int) $day->grace_minutes,
        ];
    }

}
