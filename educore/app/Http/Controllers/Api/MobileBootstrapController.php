<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Term;
use App\Services\Mobile\MobileModuleService;
use App\Services\TenantAccessDecision;
use App\Services\TenantAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class MobileBootstrapController extends Controller
{
    public function __invoke(
        Request $request,
        MobileModuleService $modules,
        TenantAccessService $tenantAccess
    ): JsonResponse {
        $requestId = 'MB-' . strtoupper(Str::random(8));
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'request_id' => $requestId,
            ], 401)->header('X-EduCore-Request-Id', $requestId);
        }

        // Bootstrap is the app's authenticated entry point. Every optional
        // enrichment is isolated so legacy/missing metadata cannot turn a
        // valid sign-in into an HTTP 500.
        try {
            if ($user->isTenantStaff() && ! $user->isEmploymentActive()) {
                return response()->json([
                    'message' => 'Your employment is no longer active. Contact the school.',
                    'request_id' => $requestId,
                ], 403)->header('X-EduCore-Request-Id', $requestId);
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        $superAdmin = false;
        try {
            $superAdmin = $user->isSuperAdmin();
        } catch (Throwable $exception) {
            report($exception);
        }

        $tenant = null;
        try {
            $tenant = $user->tenant;
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            $access = $superAdmin
                ? TenantAccessDecision::allow('Platform access is available.')
                : $tenantAccess->applicationAccess($tenant);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'EduCore could not verify the school access state. Please try again.',
                'request_id' => $requestId,
            ], 503)->header('X-EduCore-Request-Id', $requestId);
        }

        $session = null;
        try {
            $session = $superAdmin ? null : AcademicSession::current()->first();
        } catch (Throwable $exception) {
            report($exception);
        }

        $term = null;
        try {
            $term = $superAdmin ? null : Term::current()->first();
        } catch (Throwable $exception) {
            report($exception);
        }

        $subscriptionExpiresAt = null;
        try {
            $subscriptionExpiresAt = (! $superAdmin && $tenant)
                ? ($tenant->billingTenant()->subscription_expires_at ?? $tenant->subscription_expires_at)
                : null;
        } catch (Throwable $exception) {
            report($exception);
            try {
                $subscriptionExpiresAt = $tenant?->subscription_expires_at;
            } catch (Throwable $ignored) {
                report($ignored);
            }
        }

        $mobileAccessExpiresAt = null;
        try {
            $graceDays = (int) ($access->metadata['grace_days'] ?? 0);
            $mobileAccessExpiresAt = $access->state === TenantAccessDecision::STATE_GRACE
                && $access->expiresAt
                && $graceDays > 0
                    ? $access->expiresAt->copy()->addDays($graceDays)
                    : ($access->expiresAt ?? $subscriptionExpiresAt);
        } catch (Throwable $exception) {
            report($exception);
        }

        $roleKey = $superAdmin ? 'super_admin' : 'staff';
        try {
            $roleKey = $superAdmin ? 'super_admin' : ($user->roleKey() ?: 'staff');
        } catch (Throwable $exception) {
            report($exception);
        }

        $roleLabel = $superAdmin ? 'Platform Super Admin' : 'Staff';
        try {
            $roleLabel = $superAdmin ? 'Platform Super Admin' : ($user->roleLabel() ?: 'Staff');
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            $roles = $superAdmin
                ? ['super_admin']
                : ($access->allowed
                    ? $user->getRoleNames()->values()->all()
                    : array_values(array_filter([$roleKey])));
        } catch (Throwable $exception) {
            report($exception);
            $roles = array_values(array_filter([$roleKey]));
        }

        try {
            $permissions = $access->allowed
                ? ($superAdmin ? ['*'] : $user->effectivePermissionKeys())
                : [];
        } catch (Throwable $exception) {
            report($exception);
            $permissions = [];
        }

        try {
            $features = $access->allowed && ! $superAdmin
                ? $user->subscriptionFeatureKeys()
                : ($superAdmin ? ['*'] : []);
        } catch (Throwable $exception) {
            report($exception);
            $features = [];
        }

        try {
            $availableModules = $access->allowed ? $modules->forUser($user) : [];
        } catch (Throwable $exception) {
            report($exception);
            $availableModules = [];
        }

        $portal = $superAdmin ? 'platform' : 'staff';
        try {
            $portal = $this->portalFor($user);
        } catch (Throwable $exception) {
            report($exception);
        }

        $schoolId = null;
        $schoolName = 'EduCore Platform';
        $schoolSlug = 'platform';
        $primaryColor = '#071E45';
        $accentColor = '#D79A21';
        $motto = null;
        try {
            if ($tenant) {
                $schoolId = $tenant->id;
                $schoolName = $tenant->name ?: 'EduCore School';
                $schoolSlug = $tenant->slug ?: 'school';
                $primaryColor = $tenant->theme_primary ?? $tenant->primary_color ?? $primaryColor;
                $accentColor = $tenant->theme_accent ?? $tenant->secondary_color ?? $accentColor;
                $motto = $tenant->motto ?? null;
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        $tokenExpiresAt = null;
        try {
            $tokenExpiresAt = $request->attributes->get('api_token')?->expires_at?->toIso8601String();
        } catch (Throwable $exception) {
            report($exception);
        }

        $accessExpiresAt = null;
        try {
            $accessExpiresAt = $mobileAccessExpiresAt?->toIso8601String();
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            return response()->json([
                'contract_version' => 1,
                'user' => [
                    'id' => (int) $user->id,
                    'name' => (string) ($user->name ?: 'EduCore User'),
                    'email' => $user->email,
                    'staff_id' => $user->staff_id,
                    'role_key' => $roleKey,
                    'role' => $roleLabel,
                    'roles' => array_values((array) $roles),
                    'portal' => $portal,
                ],
                'school' => [
                    'id' => $schoolId,
                    'name' => $schoolName,
                    'slug' => $schoolSlug,
                    'branding' => [
                        'primary_color' => $primaryColor,
                        'accent_color' => $accentColor,
                        'motto' => $motto,
                    ],
                ],
                'academic' => [
                    'session' => $session ? ['id' => (int) $session->id, 'name' => (string) $session->name] : null,
                    'term' => $term ? ['id' => (int) $term->id, 'name' => (string) $term->name] : null,
                ],
                'access' => [
                    'allowed' => (bool) $access->allowed,
                    'state' => (string) $access->state,
                    'message' => (string) $access->message,
                    'severity' => $access->severity,
                    'expires_at' => $accessExpiresAt,
                ],
                'permissions' => array_values(array_filter((array) $permissions, 'is_string')),
                'features' => array_values(array_filter((array) $features, 'is_string')),
                'modules' => array_values((array) $availableModules),
                'token' => ['expires_at' => $tokenExpiresAt],
                'server_time' => now()->toIso8601String(),
                'request_id' => $requestId,
            ])->header('X-EduCore-Request-Id', $requestId);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'EduCore could not prepare the school workspace. Please try again.',
                'request_id' => $requestId,
            ], 503)->header('X-EduCore-Request-Id', $requestId);
        }
    }

    private function portalFor($user): string
    {
        if ($user->isSuperAdmin()) {
            return 'platform';
        }
        if ($user->isStudent()) {
            return 'student';
        }
        if ($user->isParent()) {
            return 'parent';
        }

        return in_array($user->roleKey(), [
            'admin',
            'principal',
            'head',
            'head_teacher',
            'vice_principal',
            'academic_administrator',
        ], true) ? 'admin' : 'staff';
    }
}
