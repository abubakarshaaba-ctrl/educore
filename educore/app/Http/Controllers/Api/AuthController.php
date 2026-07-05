<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Services\Auth\AuthAuditLogger;
use App\Services\Auth\LoginUserResolver;
use App\Services\TenantAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Mobile API authentication — teachers/staff only (v1).
 * Mirrors the unified web login rules (LoginUserResolver + tenant access).
 */
class AuthController extends Controller
{
    public function login(
        Request $request,
        LoginUserResolver $users,
        TenantAccessService $tenantAccess,
        AuthAuditLogger $audit
    ) {
        $data = $request->validate([
            'login_id' => ['required', 'string', 'max:180'],
            'password' => ['required', 'string'],
            'device'   => ['nullable', 'string', 'max:150'],
        ]);

        $user = $users->resolveGlobal(trim($data['login_id']));

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'These credentials do not match our records.'], 422);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Your account has been deactivated. Contact the school.'], 403);
        }

        // v1 is a staff app — students/parents/super admins use the web portals
        if ($user->is_super_admin || !$user->isTenantStaff()) {
            return response()->json(['message' => 'The mobile app is for school staff accounts.'], 403);
        }

        if (!$user->isEmploymentActive()) {
            return response()->json(['message' => 'Your employment is no longer active. Contact the school.'], 403);
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            return response()->json(['message' => 'Account is not linked to any school.'], 403);
        }

        $decision = $tenantAccess->applicationAccess($tenant);
        if ($decision->isDenied()) {
            return response()->json(['message' => 'This school account is currently unavailable.'], 403);
        }

        $token = ApiToken::issue($user, $data['device'] ?? $request->userAgent());

        $user->forceFill(['last_login_at' => now()])->save();
        $audit->recordForUser($user, 'auth.login.success', ['login_surface' => 'mobile_api'], $request);

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'staff_id' => $user->staff_id,
                'role'     => $user->roleLabel() ?? 'staff',
            ],
            'school' => [
                'id'   => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->attributes->get('api_token');
        $token?->delete();

        return response()->json(['message' => 'Signed out.']);
    }
}
