<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MobilePlatformTenantController extends Controller
{
    public function show(Request $request, Tenant $tenant): JsonResponse
    {
        $this->guard($request);
        $tenant->loadCount(['users', 'students']);

        $admins = User::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('role', User::roleAliasesFor('admin'))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_active'])
            ->map(fn (User $admin): array => [
                'id' => (int) $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'active' => (bool) $admin->is_active,
            ]);

        return response()->json([
            'tenant' => $this->tenantData($tenant),
            'admins' => $admins,
            'subscription' => [
                'is_free' => PricingService::isFree(PricingService::activeStudentCount($tenant->id)),
                'expires_at' => $tenant->subscription_expires_at?->toDateString(),
                'can_extend' => !PricingService::isFree(PricingService::activeStudentCount($tenant->id)),
                'allowed_months' => [1, 3, 6, 12, 24],
            ],
        ]);
    }

    public function extend(Request $request, Tenant $tenant): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'months' => ['required', 'integer', 'min:1', 'max:24'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        if (PricingService::isFree(PricingService::activeStudentCount($tenant->id))) {
            throw ValidationException::withMessages([
                'months' => 'Free-plan schools do not have a subscription expiry to extend.',
            ]);
        }

        $months = (int) $data['months'];
        $before = $tenant->subscription_expires_at?->toIso8601String();

        DB::transaction(function () use ($tenant, $months, $data, $request, $user, $before): void {
            $locked = Tenant::query()->whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            $base = $locked->subscription_expires_at && $locked->subscription_expires_at->isFuture()
                ? $locked->subscription_expires_at->copy()
                : now();
            $newExpiry = $base->addMonthsNoOverflow($months);

            $locked->update([
                'subscription_expires_at' => $newExpiry,
                'status' => Tenant::STATUS_ACTIVE,
            ]);

            if (Schema::hasTable('audit_logs')) {
                AuditLog::create([
                    'tenant_id' => $locked->id,
                    'actor_user_id' => $user->id,
                    'auditable_type' => Tenant::class,
                    'auditable_id' => $locked->id,
                    'action' => 'tenant.subscription.extended.via_api',
                    'old_values' => ['subscription_expires_at' => $before, 'status' => $tenant->status],
                    'new_values' => [
                        'subscription_expires_at' => $newExpiry->toIso8601String(),
                        'status' => Tenant::STATUS_ACTIVE,
                        'months' => $months,
                    ],
                    'reason' => trim($data['reason']),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        });

        $fresh = $tenant->fresh()->loadCount(['users', 'students']);

        return response()->json([
            'message' => "Subscription extended by {$months} month(s).",
            'tenant' => $this->tenantData($fresh),
        ]);
    }

    private function guard(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless($user->isSuperAdmin(), 403, 'Platform Super Admin access required.');

        return $user;
    }

    private function tenantData(Tenant $tenant): array
    {
        return [
            'id' => (int) $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'address' => $tenant->address,
            'status' => $tenant->status,
            'plan' => PricingService::tierLabel((int) ($tenant->students_count ?? PricingService::activeStudentCount($tenant->id))),
            'students_capacity' => PricingService::capacityFor($tenant),
            'subscription_expires_at' => $tenant->subscription_expires_at?->toDateString(),
            'users' => (int) ($tenant->users_count ?? $tenant->users()->count()),
            'students' => (int) ($tenant->students_count ?? $tenant->students()->count()),
        ];
    }
}
