<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PlatformTenantRemovalService
{
    public function remove(
        Tenant $tenant,
        User $actor,
        string $confirmation,
        string $currentPassword,
        string $reason,
        ?Request $request = null,
    ): void {
        if (!hash_equals($tenant->name, $confirmation)) {
            throw ValidationException::withMessages([
                'confirmation' => 'Enter the school name exactly to confirm removal.',
            ]);
        }
        if (!Hash::check($currentPassword, $actor->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Your current password is incorrect.',
            ]);
        }
        $reason = trim($reason);
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 500) {
            throw ValidationException::withMessages([
                'reason' => 'Provide a removal reason between 10 and 500 characters.',
            ]);
        }

        DB::transaction(function () use ($tenant, $actor, $reason, $request): void {
            $locked = Tenant::withTrashed()->whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            if ($locked->trashed()) {
                throw ValidationException::withMessages(['tenant' => 'This school has already been removed.']);
            }

            $userIds = User::query()->where('tenant_id', $locked->id)->pluck('id');

            if ($userIds->isNotEmpty() && Schema::hasTable('api_tokens')) {
                ApiToken::query()->whereIn('user_id', $userIds)->delete();
            }
            if ($userIds->isNotEmpty() && Schema::hasTable('push_subscriptions')) {
                DB::table('push_subscriptions')->whereIn('user_id', $userIds)->delete();
            }

            User::query()->where('tenant_id', $locked->id)->update([
                'is_active' => false,
                'status_changed_at' => now(),
            ]);

            if (Schema::hasTable('audit_logs')) {
                AuditLog::create([
                    'tenant_id' => $locked->id,
                    'actor_user_id' => $actor->id,
                    'auditable_type' => Tenant::class,
                    'auditable_id' => $locked->id,
                    'action' => 'tenant.removed.via_platform',
                    'old_values' => [
                        'name' => $locked->name,
                        'slug' => $locked->slug,
                        'status' => $locked->status,
                    ],
                    'new_values' => [
                        'status' => Tenant::STATUS_SUSPENDED,
                        'removed_at' => now()->toIso8601String(),
                        'api_sessions_revoked' => true,
                        'push_subscriptions_revoked' => true,
                    ],
                    'reason' => $reason,
                    'ip_address' => $request?->ip(),
                    'user_agent' => $request?->userAgent(),
                ]);
            }

            $locked->update(['status' => Tenant::STATUS_SUSPENDED]);
            $locked->delete();
        });
    }
}
