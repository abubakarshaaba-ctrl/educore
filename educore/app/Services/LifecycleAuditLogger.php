<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LifecycleAuditLogger
{
    public function record(
        ?int $tenantId,
        ?User $actor,
        Model $auditable,
        string $action,
        array $oldValues = [],
        array $newValues = [],
        ?string $reason = null,
        ?Request $request = null
    ): AuditLog {
        return AuditLog::create([
            'tenant_id' => $tenantId,
            'actor_user_id' => $actor?->id,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
