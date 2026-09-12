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
use Throwable;

class MobileBootstrapController extends Controller
{
    public function __invoke(
        Request $request,
        MobileModuleService $modules,
        TenantAccessService $tenantAccess
    ): JsonResponse {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->isTenantStaff() && ! $user->isEmploymentActive()) {
            return response()->json([
                'message' => 'Your employment is no longer active. Contact the school.',
            ], 403);
        }

        $superAdmin = $user->isSuperAdmin();
        $tenant = $user->tenant;

        try {
            $access = $superAdmin
                ? TenantAccessDecision::allow('Platform access is available.')
                : $tenantAccess->applicationAccess($tenant);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => 'Your school access could not be verified. Please try again.',
            ], 503);
        }

        try {
            $session = $superAdmin ? null : AcademicSession::current()->first();
        } catch (Throwable $exception) {
            report($exception);
            $session = null;
        }

        try {
            $term = $superAdmin ? null : Term::current()->first();
        } catch (Throwable $exception) {
            report($exception);
            $term = null;
        }

        $token = $request->attributes->get('api_token');
        $subscriptionExpiresAt = null;
        try {
            $subscriptionExpiresAt = (! $superAdmin && $tenant)
                ? $tenant->billingTenant()->subscription_expires_at
                : null;
        } catch (Throwable $exception) {
            report($exception);
        }

        $graceDays = (int) ($access->metadata['grace_days'] ?? 0);
        $mobileAccessExpiresAt = $access->state === TenantAccessDecision::STATE_GRACE
            && $access->expiresAt
            && $graceDays > 0
                ? $access->expiresAt->copy()->addDays($graceDays)
                : ($access->expiresAt ?? $subscriptionExpiresAt);

        try {
            $roles = $superAdmin
                ? ['super_admin']
                : ($access->allowed
                    ? $user->getRoleNames()->values()->all()
                    : array_values(array_filter([$user->roleKey()])));
        } catch (Throwable $exception) {
            report($exception);
            $roles = array_values(array_filter([$superAdmin ? 'super_admin' : $user->roleKey()]));
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

        return response()->json([
            'contract_version' => 1,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'staff_id' => $user->staff_id,
                'role_key' => $superAdmin ? 'super_admin' : $user->roleKey(),
                'role' => $superAdmin ? 'Platform Super Admin' : ($user->roleLabel() ?? 'staff'),
                'roles' => $roles,
                'portal' => $this->portalFor($user),
            ],
            'school' => [
                'id' => $tenant?->id,
                'name' => $tenant?->name ?? 'EduCore Platform',
                'slug' => $tenant?->slug ?? 'platform',
                'branding' => [
                    'primary_color' => $tenant?->theme_primary ?? $tenant?->primary_color ?? '#071E45',
                    'accent_color' => $tenant?->theme_accent ?? $tenant?->secondary_color ?? '#D79A21',
                    'motto' => $tenant?->motto,
                ],
            ],
            'academic' => [
                'session' => $session?->only(['id', 'name']),
                'term' => $term?->only(['id', 'name']),
            ],
            'access' => [
                'allowed' => $access->allowed,
                'state' => $access->state,
                'message' => $access->message,
                'severity' => $access->severity,
                'expires_at' => $mobileAccessExpiresAt?->toIso8601String(),
            ],
            'permissions' => $permissions,
            'features' => $features,
            'modules' => $availableModules,
            'token' => [
                'expires_at' => $token?->expires_at?->toIso8601String(),
            ],
            'server_time' => now()->toIso8601String(),
        ]);
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
