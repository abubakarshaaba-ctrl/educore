<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffAttendanceController;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffAttendanceSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Staff self-attendance for the mobile app.
 *
 * Clock-in/out reuse the existing StaffAttendanceController JSON endpoints
 * (QR + geo-fence rules identical to the web PWA); this controller only
 * adds the mobile summary feed.
 */
class StaffAttendanceApiController extends Controller
{
    /**
     * Clock-in from the mobile app.
     *
     * The school display/ID-card QR encodes a URL like
     * ".../staff-attendance/my?qr_token=<payload>". The web PWA reads the
     * qr_token from the query string when the browser opens that URL, but the
     * app scans the raw string — so we normalise it to the bare token here,
     * then delegate to the proven web clock-in logic (QR + geo-fence rules).
     */
    public function clockIn(Request $request, StaffAttendanceController $web)
    {
        $token = (string) $request->input('token', '');

        if (str_contains($token, 'qr_token=')) {
            $query = parse_url($token, PHP_URL_QUERY) ?: '';
            parse_str($query, $parts);
            if (!empty($parts['qr_token'])) {
                $request->merge(['token' => $parts['qr_token']]);
            }
        }

        return $web->clockInQr($request);
    }

    public function me(Request $request)
    {
        $user  = $request->user();
        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year', now()->year);

        $start = Carbon::createFromDate($year, $month, 1);
        $end   = (clone $start)->endOfMonth();

        $records = StaffAttendanceRecord::where('user_id', $user->id)
            ->whereBetween('attendance_date', [$start, $end])
            ->orderByDesc('attendance_date')
            ->get()
            ->map(fn ($r) => [
                'date'      => $r->attendance_date instanceof \DateTimeInterface
                    ? $r->attendance_date->format('Y-m-d')
                    : (string) $r->attendance_date,
                'status'    => $r->status,
                'clock_in'  => $r->clock_in_time,
                'clock_out' => $r->clock_out_time,
                'method'    => $r->clock_in_method,
            ]);

        $today = StaffAttendanceRecord::where('user_id', $user->id)
            ->whereDate('attendance_date', today())
            ->first();

        $settings = StaffAttendanceSetting::firstOrCreate(['tenant_id' => $user->tenant_id]);

        return response()->json([
            'month'  => $month,
            'year'   => $year,
            'counts' => [
                'early'   => $records->where('status', 'early')->count(),
                'present' => $records->where('status', 'present')->count(),
                'late'    => $records->where('status', 'late')->count(),
                'absent'  => $records->where('status', 'absent')->count(),
            ],
            'today' => $today ? [
                'status'    => $today->status,
                'clock_in'  => $today->clock_in_time,
                'clock_out' => $today->clock_out_time,
            ] : null,
            'settings' => [
                'geo_enabled'       => (bool) $settings->geo_enabled,
                'geo_radius_meters' => (int) ($settings->geo_radius_meters ?? 0),
            ],
            'records' => $records,
        ]);
    }
}
