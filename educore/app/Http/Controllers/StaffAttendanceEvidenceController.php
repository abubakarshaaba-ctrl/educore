<?php

namespace App\Http\Controllers;

use App\Models\StaffAttendanceRecord;
use App\Services\AuthenticatedIdentityAssetStorage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffAttendanceEvidenceController extends Controller
{
    public function __invoke(
        Request $request,
        int $record,
        string $kind,
        AuthenticatedIdentityAssetStorage $assets
    ) {
        $user = $request->user();
        abort_unless($user && $user->tenant_id, 403);
        abort_unless($user->isAdmin() || $user->canManage('staff-attendance'), 403, 'Staff attendance management access required.');

        $attendance = StaffAttendanceRecord::query()
            ->where('tenant_id', $user->tenant_id)
            ->with('staff:id,tenant_id,passport_photo')
            ->findOrFail($record);

        $path = match ($kind) {
            'passport' => $attendance->staff?->passport_photo,
            'clock-in' => $attendance->clock_in_photo,
            'proxy' => $attendance->proxy_photo,
            default => throw ValidationException::withMessages(['kind' => 'Unsupported attendance evidence type.']),
        };

        $absolute = $assets->resolveAbsolutePath($path);
        if (!$absolute) {
            abort(404, 'Attendance evidence is unavailable.');
        }

        return response()->file($absolute, [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
