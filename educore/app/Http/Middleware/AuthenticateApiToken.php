<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Services\Auth\ApiRoleAccessPolicy;
use App\Services\Mobile\MobileModuleService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-token authentication for the mobile API.
 *
 * Sets the token's user on the default guard so auth()->user() and the
 * tenant global scope (BaseTenantModel) work exactly as on the web side.
 * Every authenticated tenant-staff request is then authorized by the same
 * explicit module contract used by the web application.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next, ApiRoleAccessPolicy $accessPolicy): Response
    {
        $plain = $request->bearerToken();

        if (! $plain) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $token = ApiToken::findValid($plain);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'The secure mobile session could not be verified. Please sign in again.'], 401);
        }

        if (! $token || ! $token->user) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $user = $token->user;

        if (! $user->is_active) {
            return response()->json(['message' => 'Account deactivated.'], 403);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('api_token', $token);

        try {
            $allowed = $accessPolicy->allows($user, $request);
        } catch (Throwable $exception) {
            report($exception);
            $path = trim($request->path(), '/');
            $path = preg_replace('#^api/v1/#', '', $path) ?? $path;
            $allowed = in_array($path, ['bootstrap', 'auth/logout', 'me'], true);
        }

        if (! $allowed) {
            return response()->json([
                'message' => 'Access denied for your assigned role.',
                'role' => $user->roleLabel(),
            ], 403);
        }

        try {
            if (! $token->last_used_at || $token->last_used_at->lt(now()->subMinute())) {
                $token->forceFill(['last_used_at' => now()])->saveQuietly();
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            return $next($request);
        } catch (Throwable $exception) {
            report($exception);

            $path = trim($request->path(), '/');
            $path = preg_replace('#^api/v1/#', '', $path) ?? $path;
            if ($path !== 'bootstrap') {
                throw $exception;
            }

            // Authentication has already succeeded at this point. If anything in
            // the normal bootstrap/controller/serialization pipeline fails, return
            // a conservative but valid workspace snapshot rather than blocking the
            // user at the login screen. The reference id is safe to report and can
            // be matched against the server log if deeper diagnosis is needed.
            $reference = 'BOOT-' . strtoupper(Str::random(8));

            try {
                $tenant = $user->tenant;
            } catch (Throwable $ignored) {
                $tenant = null;
            }

            try {
                $roleKey = (string) $user->roleKey();
            } catch (Throwable $ignored) {
                $roleKey = 'staff';
            }

            try {
                $roleLabel = (string) ($user->roleLabel() ?? 'Staff');
            } catch (Throwable $ignored) {
                $roleLabel = 'Staff';
            }

            try {
                $roles = $user->getRoleNames()->values()->all();
            } catch (Throwable $ignored) {
                $roles = array_values(array_filter([$roleKey]));
            }

            try {
                $permissions = $user->effectivePermissionKeys();
            } catch (Throwable $ignored) {
                $permissions = [];
            }

            try {
                $modules = app(MobileModuleService::class)->forUser($user);
            } catch (Throwable $ignored) {
                $modules = [];
            }

            try {
                $portal = $user->isSuperAdmin()
                    ? 'platform'
                    : ($user->isStudent()
                        ? 'student'
                        : ($user->isParent()
                            ? 'parent'
                            : (in_array($roleKey, ['admin', 'principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator'], true)
                                ? 'admin'
                                : 'staff')));
            } catch (Throwable $ignored) {
                $portal = 'staff';
            }

            return response()->json([
                'contract_version' => 1,
                'user' => [
                    'id' => (int) $user->id,
                    'name' => (string) ($user->name ?? 'EduCore User'),
                    'email' => $user->email,
                    'staff_id' => $user->staff_id,
                    'role_key' => $roleKey,
                    'role' => $roleLabel,
                    'roles' => $roles,
                    'portal' => $portal,
                ],
                'school' => [
                    'id' => $tenant?->id,
                    'name' => (string) ($tenant?->name ?? 'EduCore School'),
                    'slug' => (string) ($tenant?->slug ?? 'school'),
                    'branding' => [
                        'primary_color' => '#071E45',
                        'accent_color' => '#D79A21',
                        'motto' => null,
                    ],
                ],
                'academic' => [
                    'session' => null,
                    'term' => null,
                ],
                'access' => [
                    'allowed' => true,
                    'state' => 'allowed',
                    'message' => 'School account access is available.',
                    'severity' => null,
                    'expires_at' => null,
                ],
                'permissions' => is_array($permissions) ? $permissions : [],
                'features' => [],
                'modules' => is_array($modules) ? $modules : [],
                'token' => [
                    'expires_at' => null,
                ],
                'server_time' => now()->toIso8601String(),
                'degraded' => true,
                'request_id' => $reference,
            ])->header('X-EduCore-Bootstrap-Reference', $reference);
        }
    }
}
