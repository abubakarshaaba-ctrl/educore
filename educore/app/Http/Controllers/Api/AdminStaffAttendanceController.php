<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminStaffAttendanceController extends Controller
{
    public function daily(Request $request)
    {
        $user = $this->guard($request);
        $date = $request->date('date')?->toDateString() ?? today()->toDateString();
        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $records = StaffAttendanceRecord::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereDate('attendance_date', $date)
            ->with(['staff:id,name,staff_id', 'clockedInBy:id,name'])
            ->orderBy('clock_in_time')
            ->get();
        $eligible = User::attendanceEligibleOn($user->tenant_id, $date)->count();

        return response()->json([
            'date' => $date,
            'summary' => [
                'eligible' => $eligible,
                'clocked_in' => $records->whereNotNull('clock_in_time')->count(),
                'early' => $records->where('status', 'early')->count(),
                'present' => $records->whereIn('status', ['early', 'present', 'late'])->count(),
                'late' => $records->where('status', 'late')->count(),
                'absent' => max(0, $eligible - $records->whereNotNull('clock_in_time')->count()),
            ],
            'records' => $records->map(fn (StaffAttendanceRecord $record) => $this->recordPayload($record))->values(),
            'settings' => $this->settingsPayload($settings),
        ]);
    }

    /** Compatibility with the previous /admin/staff-attendance endpoint. */
    public function index(Request $request)
    {
        return $this->daily($request);
    }

    public function monthly(Request $request)
    {
        $user = $this->guard($request);
        $month = $request->integer('month', now()->month);
        $year = $request->integer('year', now()->year);
        abort_unless($month >= 1 && $month <= 12 && $year >= 2000 && $year <= 2200, 422, 'Invalid attendance month.');

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth();
        $records = StaffAttendanceRecord::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('user_id');

        $staff = User::tenantStaff($user->tenant_id)->orderBy('name')->get()->map(function (User $member) use ($records) {
            $rows = $records->get($member->id, collect());
            return [
                'id' => $member->id,
                'name' => $member->name,
                'staff_id' => $member->staff_id,
                'early' => $rows->where('status', 'early')->count(),
                'present' => $rows->whereIn('status', ['early', 'present', 'late'])->count(),
                'late' => $rows->where('status', 'late')->count(),
                'absent' => $rows->where('status', 'absent')->count(),
                'days' => $rows->count(),
            ];
        })->values();

        return response()->json(['month' => $month, 'year' => $year, 'staff' => $staff]);
    }

    public function report(Request $request)
    {
        return $this->monthly($request);
    }

    public function settings(Request $request)
    {
        $user = $this->guard($request);
        return response()->json(['settings' => $this->settingsPayload(StaffAttendanceSetting::forTenant($user->tenant_id))]);
    }

    public function updateSettings(Request $request)
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'resumption_time' => ['sometimes', 'required', 'date_format:H:i'],
            'grace_minutes' => ['sometimes', 'required', 'integer', 'min:0', 'max:120'],
            'closing_time' => ['sometimes', 'required', 'date_format:H:i'],
            'geo_enabled' => ['sometimes', 'required', 'boolean'],
            'geo_lat' => ['required', 'numeric', 'between:-90,90'],
            'geo_lng' => ['required', 'numeric', 'between:-180,180'],
            'geo_radius_meters' => ['required', 'numeric', 'gt:0', 'max:50000'],
        ]);
        if (isset($data['resumption_time'])) $data['resumption_time'] .= ':00';
        if (isset($data['closing_time'])) $data['closing_time'] .= ':00';
        $data['geo_enabled'] = $data['geo_enabled'] ?? true;
        $data['geo_radius_meters'] = (int) round($data['geo_radius_meters']);

        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $settings->update($data);
        $settings->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Attendance settings saved successfully.',
            'settings' => $this->settingsPayload($settings),
        ]);
    }

    public function offline(Request $request)
    {
        $user = $this->guard($request);
        $records = StaffAttendanceRecord::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('is_offline_upload', true)
            ->with(['staff:id,name,staff_id', 'clockedInBy:id,name'])
            ->latest('updated_at')
            ->limit(200)
            ->get();

        return response()->json([
            'records' => $records->map(fn (StaffAttendanceRecord $record) => $this->recordPayload($record))->values(),
        ]);
    }

    public function syncOffline(Request $request)
    {
        $user = $this->guard($request);
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

        $existing = StaffAttendanceRecord::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('client_uuid', $data['client_uuid'])
            ->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'idempotent' => true,
                'record' => $this->recordPayload($existing->loadMissing(['staff', 'clockedInBy'])),
            ]);
        }

        $staff = User::tenantStaff($user->tenant_id)->whereKey($data['staff_id'])->first();
        abort_unless($staff, 422, 'The selected staff member is not available for this school.');
        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);

        if (!empty($data['qr_token']) && !$settings->verifyStaticQrToken($data['qr_token'])) {
            return response()->json(['success' => false, 'status' => 'rejected', 'message' => 'The attendance QR is no longer valid.'], 422);
        }

        $timestamp = Carbon::parse($data['local_timestamp']);
        $record = DB::transaction(function () use ($data, $staff, $user, $settings, $timestamp) {
            $record = StaffAttendanceRecord::firstOrNew([
                'tenant_id' => $user->tenant_id,
                'user_id' => $staff->id,
                'attendance_date' => $data['attendance_date'],
            ]);
            if ($data['action'] === 'clock_in') {
                if (!$record->exists || !$record->clock_in_time) {
                    $record->clock_in_time = $timestamp->format('H:i:s');
                    $record->status = $settings->classifyClockIn($timestamp->format('H:i:s'));
                }
                $record->clock_in_method = 'offline';
                $record->clocked_in_by = $user->id;
                $record->clock_in_lat = $data['latitude'] ?? null;
                $record->clock_in_lng = $data['longitude'] ?? null;
                $record->location_accuracy = $data['accuracy'] ?? null;
            } else {
                abort_unless($record->exists && $record->clock_in_time, 422, 'A clock-in record is required before clock-out.');
                $record->clock_out_time = $timestamp->format('H:i:s');
            }
            $record->client_uuid = $data['client_uuid'];
            $record->is_offline_upload = true;
            $record->save();
            return $record;
        });

        return response()->json([
            'success' => true,
            'idempotent' => false,
            'record' => $this->recordPayload($record->loadMissing(['staff', 'clockedInBy'])),
        ]);
    }

    public function proxyReviews(Request $request)
    {
        $user = $this->guard($request);
        $records = StaffAttendanceRecord::query()
            ->where('tenant_id', $user->tenant_id)
            ->where(function ($query) {
                $query->where('clock_in_method', 'proxy')
                    ->orWhereNotNull('proxy_review_status');
            })
            ->with(['staff:id,name,staff_id', 'clockedInBy:id,name'])
            ->latest('updated_at')
            ->limit(200)
            ->get();

        return response()->json(['records' => $records->map(fn ($record) => $this->recordPayload($record))->values()]);
    }

    public function proxyClock(Request $request)
    {
        $actor = $this->guard($request);
        $data = $request->validate([
            'staff_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'clock_in_time' => ['required', 'date_format:H:i'],
            'clock_out_time' => ['nullable', 'date_format:H:i', 'after:clock_in_time'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'status' => ['nullable', Rule::in(['early', 'present', 'late', 'absent'])],
            'device' => ['nullable', 'string', 'max:255'],
        ]);
        $staff = User::tenantStaff($actor->tenant_id)->whereKey($data['staff_id'])->first();
        abort_unless($staff, 422, 'The selected staff member is not available for this school.');
        $settings = StaffAttendanceSetting::forTenant($actor->tenant_id);

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
            'notes' => 'Recorded by proxy: ' . $data['reason'],
        ]);

        Log::notice('Staff attendance recorded by proxy', [
            'tenant_id' => $actor->tenant_id,
            'actor_user_id' => $actor->id,
            'staff_user_id' => $staff->id,
            'attendance_record_id' => $record->id,
            'reason' => $data['reason'],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded by proxy.',
            'record' => $this->recordPayload($record->loadMissing(['staff', 'clockedInBy'])),
        ], 201);
    }

    public function qr(Request $request)
    {
        $user = $this->guard($request);
        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $payload = $settings->staticQrPayload();

        return response()->json([
            'success' => true,
            'data' => [
                'qr_token' => $payload,
                'school_name' => $user->tenant?->name,
                'generated_at' => $settings->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                'expires_at' => null,
            ],
        ]);
    }

    public function resetQr(Request $request)
    {
        $user = $this->guard($request);
        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
        $settings->resetStaticQr();
        $settings->refresh();

        Log::notice('School attendance QR reset', ['tenant_id' => $user->tenant_id, 'actor_user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'School attendance QR reset successfully.',
            'data' => [
                'qr_token' => $settings->staticQrPayload(),
                'school_name' => $user->tenant?->name,
                'generated_at' => now()->toIso8601String(),
                'expires_at' => null,
            ],
        ]);
    }

    private function guard(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->tenant_id && ($user->isSuperAdmin() || $user->canManage('staff-attendance')), 403, 'You do not have permission to manage staff attendance.');
        return $user;
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
            'configured' => $settings->geo_lat !== null && $settings->geo_lng !== null && (int) $settings->geo_radius_meters > 0,
        ];
    }
}
