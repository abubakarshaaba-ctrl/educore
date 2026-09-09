<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PlatformImpersonationController extends Controller
{
    public function start(Request $request, Tenant $tenant)
    {
        /** @var User|null $superAdmin */
        $superAdmin = $request->user();
        abort_unless($superAdmin?->isSuperAdmin(), 403, 'Platform Super Admin access required.');

        if ($request->session()->has('super_admin_id') || $request->session()->has('impersonating_tenant_id')) {
            throw ValidationException::withMessages([
                'impersonation' => 'An impersonation session is already active. Return to the Super Admin account before starting another one.',
            ]);
        }

        $admin = User::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('role', User::roleAliasesFor('admin'))
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (!$admin) {
            throw ValidationException::withMessages([
                'tenant' => 'No active school administrator is available for impersonation.',
            ]);
        }

        DB::transaction(function () use ($request, $superAdmin, $tenant, $admin): void {
            $this->audit(
                request: $request,
                actorUserId: $superAdmin->id,
                tenantId: $tenant->id,
                action: 'platform.impersonation.started',
                oldValues: [],
                newValues: [
                    'target_admin_user_id' => $admin->id,
                    'target_tenant_id' => $tenant->id,
                ],
            );
        });

        $request->session()->put([
            'super_admin_id' => $superAdmin->id,
            'impersonating_tenant_id' => $tenant->id,
            'impersonated_admin_id' => $admin->id,
            'impersonation_started_at' => now()->toIso8601String(),
        ]);

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('info', 'Now viewing as: '.$tenant->name);
    }

    public function stop(Request $request)
    {
        /** @var User|null $currentUser */
        $currentUser = $request->user();
        $superAdminId = (int) $request->session()->get('super_admin_id');
        $tenantId = (int) $request->session()->get('impersonating_tenant_id');
        $impersonatedAdminId = (int) $request->session()->get('impersonated_admin_id');
        $startedAt = $request->session()->get('impersonation_started_at');

        $superAdmin = User::query()
            ->whereKey($superAdminId)
            ->where('is_super_admin', true)
            ->where('is_active', true)
            ->first();

        abort_unless(
            $superAdmin
            && $currentUser
            && !$currentUser->isSuperAdmin()
            && (int) $currentUser->id === $impersonatedAdminId
            && (int) $currentUser->tenant_id === $tenantId,
            403,
            'No verified Platform Super Admin impersonation session was found.'
        );

        $this->audit(
            request: $request,
            actorUserId: $superAdmin->id,
            tenantId: $tenantId,
            action: 'platform.impersonation.stopped',
            oldValues: [
                'impersonated_admin_user_id' => $currentUser->id,
                'started_at' => $startedAt,
            ],
            newValues: [],
        );

        $request->session()->forget([
            'super_admin_id',
            'impersonating_tenant_id',
            'impersonated_admin_id',
            'impersonation_started_at',
        ]);

        Auth::login($superAdmin);
        $request->session()->regenerate();

        return redirect()->route('super.dashboard')->with('success', 'Returned to Platform Super Admin.');
    }

    private function audit(
        Request $request,
        int $actorUserId,
        int $tenantId,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        AuditLog::create([
            'tenant_id' => $tenantId,
            'actor_user_id' => $actorUserId,
            'auditable_type' => Tenant::class,
            'auditable_id' => $tenantId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => 'super_admin_impersonation',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
