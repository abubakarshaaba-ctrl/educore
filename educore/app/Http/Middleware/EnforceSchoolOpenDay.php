<?php

namespace App\Http\Middleware;

use App\Services\SchoolWeekService;
use Closure;
use Illuminate\Http\Request;

/**
 * Prevents normal live staff clock-in/proxy attendance on a configured closed
 * day. Manual administrative corrections remain available intentionally.
 */
class EnforceSchoolOpenDay
{
    private const LIVE_ATTENDANCE_ROUTES = [
        'staff-attendance.api.clockin',
        'staff-attendance.api.proxy.initiate',
        'staff-attendance.api.proxy.verify',
        'staff-attendance.proxy',
    ];

    public function __construct(private readonly SchoolWeekService $schoolWeek)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        if (! $user || ! $user->tenant_id) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        if (! in_array($routeName, self::LIVE_ATTENDANCE_ROUTES, true)) {
            return $next($request);
        }

        if ($this->schoolWeek->isOpenOn((int) $user->tenant_id)) {
            return $next($request);
        }

        $days = implode(', ', $this->schoolWeek->labels((int) $user->tenant_id));
        $message = 'School is closed today. Attendance clock-in is available only on configured school days'.($days ? ': '.$days.'.' : '.');

        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        return back()->withErrors(['attendance' => $message]);
    }
}
