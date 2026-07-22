<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Services\Auth\AuthAuditLogger;
use App\Services\Auth\LoginUserResolver;
use App\Services\TenantAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * Unified mobile API authentication for tenant portal accounts.
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

        if (!$user->isSuperAdmin() && !$user->isTenantStaff() && !$user->isStudent() && !$user->isParent()) {
            return response()->json(['message' => 'This account is not enabled for the mobile app yet.'], 403);
        }

        if ($user->isTenantStaff() && !$user->isEmploymentActive()) {
            return response()->json(['message' => 'Your employment is no longer active. Contact the school.'], 403);
        }

        $tenant = $user->tenant;

        if (!$user->isSuperAdmin() && !$tenant) {
            return response()->json(['message' => 'Account is not linked to any school.'], 403);
        }

        if (!$user->isSuperAdmin()) {
            $decision = $tenantAccess->applicationAccess($tenant);
            if ($decision->isDenied()) {
                return response()->json(['message' => 'This school account is currently unavailable.'], 403);
            }
        }

        $token = ApiToken::issue($user, $data['device'] ?? $request->userAgent());

        $managementRoles = [
            'admin', 'principal', 'head', 'head_teacher',
            'vice_principal', 'academic_administrator',
        ];
        $portal = $user->isSuperAdmin()
            ? 'platform'
            : ($user->isStudent()
            ? 'student'
            : ($user->isParent()
                ? 'parent'
                : (in_array($user->roleKey(), $managementRoles, true) ? 'admin' : 'staff')));

        $user->forceFill(['last_login_at' => now()])->save();
        $audit->recordForUser($user, 'auth.login.success', ['login_surface' => 'mobile_api'], $request);

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'staff_id' => $user->staff_id,
                'role_key' => $user->roleKey(),
                'role'     => $user->roleLabel() ?? 'staff',
                'roles'    => $user->getRoleNames()->values(),
                'portal'   => $portal,
            ],
            'school' => [
                'id'   => $tenant?->id,
                'name' => $tenant?->name ?? 'EduCore Platform',
                'slug' => $tenant?->slug ?? 'platform',
            ],
            'permissions' => $user->isSuperAdmin()
                ? ['platform.access', 'platform.tenants', 'platform.billing', 'platform.plans']
                : $user->getAllPermissions()->pluck('name')->sort()->values(),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->attributes->get('api_token');
        $token?->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink(['email' => strtolower(trim($data['email']))]);

        // Deliberately identical response whether the address exists or not.
        return response()->json(['message' => 'If that email is registered, a password reset link has been sent.']);
    }
}
