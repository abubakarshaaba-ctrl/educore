<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendanceSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminStaffAttendanceQrController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $settings = StaffAttendanceSetting::forTenant($user->tenant_id);

        return response()->json([
            'ok' => true,
            'payload' => $settings->staticQrPayload(),
            'type' => 'screen',
            'tenant_id' => $user->tenant_id,
            'school' => $user->tenant?->name,
            'generated_at' => now()->toIso8601String(),
            'note' => 'This QR remains valid until a school administrator resets it.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $user = $this->guard($request);

        try {
            $settings = StaffAttendanceSetting::forTenant($user->tenant_id);
            $settings->resetStaticQr();
            $fresh = $settings->fresh();

            if (! $fresh) {
                throw new \RuntimeException('Attendance settings could not be reloaded after QR reset.');
            }

            return response()->json([
                'ok' => true,
                'message' => 'School attendance QR reset. Previously printed copies are now invalid.',
                'payload' => $fresh->staticQrPayload(),
                'type' => 'screen',
                'tenant_id' => $user->tenant_id,
                'generated_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $error) {
            Log::error('Staff attendance QR reset failed', [
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'error' => $error->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'The school attendance QR could not be reset. Please try again.',
            ], 500);
        }
    }

    private function guard(Request $request): User
    {
        $user = $request->user();
        $allowedRoles = ['admin', 'principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator'];

        abort_unless(
            $user
                && $user->tenant_id
                && $user->isTenantStaff()
                && (in_array($user->roleKey(), $allowedRoles, true) || $user->canManage('staff-attendance')),
            403,
            'Staff attendance management permission required.'
        );

        return $user;
    }
}
