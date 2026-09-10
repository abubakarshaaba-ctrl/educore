<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendanceSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStaffAttendanceQrController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user
                && $user->tenant_id
                && $user->isTenantStaff()
                && $user->canManage('staff-attendance'),
            403,
            'Staff attendance management permission required.'
        );

        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);

        return response()->json([
            'payload' => $settings->staticQrPayload(),
            'type' => 'screen',
            'tenant_id' => $user->tenant_id,
            'school' => $user->tenant?->name,
            'generated_at' => now()->toIso8601String(),
            'note' => 'This QR remains valid until a school administrator resets it.',
        ]);
    }
}
