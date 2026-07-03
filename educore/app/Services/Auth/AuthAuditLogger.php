<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AuthAuditLogger
{
    public function recordForUser(
        User $user,
        string $action,
        array $newValues = [],
        ?Request $request = null,
        ?string $reason = null
    ): ?AuditLog {
        return $this->record($user->tenant_id, null, $user, $action, $newValues, $request, $reason);
    }

    public function recordForTenant(
        Tenant $tenant,
        string $action,
        array $newValues = [],
        ?Request $request = null,
        ?string $reason = null,
        ?User $actor = null
    ): ?AuditLog {
        return $this->record($tenant->id, $actor, $tenant, $action, $newValues, $request, $reason);
    }

    private function record(
        ?int $tenantId,
        ?User $actor,
        Model $auditable,
        string $action,
        array $newValues,
        ?Request $request,
        ?string $reason
    ): ?AuditLog {
        if (!Schema::hasTable('audit_logs')) {
            return null;
        }

        return AuditLog::create([
            'tenant_id' => $tenantId,
            'actor_user_id' => $actor?->id,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'action' => $action,
            'old_values' => [],
            'new_values' => $newValues,
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
