<?php

namespace App\Services\Notifications;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Tenant\PlatformBroadcastNotification;
use App\Services\PricingService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bridges the legacy raw platform_broadcasts insert used by SuperAdminController
 * into the normal delivery pipelines without changing the broadcast storage
 * contract. The listener is registered only for HTTP requests.
 *
 * Platform broadcasts continue to go through FCM and are also emailed to the
 * active tenant administrator account(s) registered for each targeted school.
 */
class PlatformBroadcastPushListener
{
    public function __construct(private readonly PushNotificationService $push) {}

    public function __invoke(QueryExecuted $query): void
    {
        if (! request()->routeIs('super.broadcasts.store')) {
            return;
        }

        if (! preg_match('/^\s*insert\s+into\s+[`"\[]?platform_broadcasts[`"\]]?\s*\(([^)]+)\)/i', $query->sql, $match)) {
            return;
        }

        try {
            $columns = array_map(
                static fn (string $column): string => trim($column, " \t\n\r\0\x0B`\"[]"),
                explode(',', $match[1]),
            );

            if (count($columns) > count($query->bindings)) {
                return;
            }

            $values = array_combine($columns, array_slice($query->bindings, 0, count($columns)));
            if (! is_array($values)
                || ! array_key_exists('title', $values)
                || ! array_key_exists('body', $values)
                || ! array_key_exists('target', $values)) {
                return;
            }

            $broadcastQuery = DB::table('platform_broadcasts')
                ->where('title', (string) $values['title'])
                ->where('target', (string) $values['target']);

            if ($values['body'] === null) {
                $broadcastQuery->whereNull('body');
            } else {
                $broadcastQuery->where('body', (string) $values['body']);
            }

            if (array_key_exists('created_by', $values) && $values['created_by'] !== null) {
                $broadcastQuery->where('created_by', $values['created_by']);
            }

            $broadcast = $broadcastQuery->orderByDesc('id')->first();

            if (! $broadcast) {
                return;
            }
        } catch (\Throwable $exception) {
            Log::warning('Platform broadcast delivery lookup failed.', [
                'error' => $exception->getMessage(),
            ]);
            return;
        }

        try {
            $this->push->notifyPlatformBroadcast(
                (int) $broadcast->id,
                (string) $broadcast->title,
                (string) ($broadcast->body ?? ''),
                (string) $broadcast->target,
            );
        } catch (\Throwable $exception) {
            // Persisting the broadcast must not fail merely because push
            // delivery is temporarily unavailable.
            Log::warning('Platform broadcast push delivery failed.', [
                'broadcast_id' => (int) $broadcast->id,
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            $this->emailTenantAdmins($broadcast);
        } catch (\Throwable $exception) {
            // Email is a secondary delivery channel; the in-app broadcast must
            // remain saved even if the mail provider is temporarily unavailable.
            Log::warning('Platform broadcast email delivery failed.', [
                'broadcast_id' => (int) $broadcast->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function emailTenantAdmins(object $broadcast): void
    {
        User::query()
            ->whereNotNull('tenant_id')
            ->where('is_active', true)
            ->where('is_super_admin', false)
            ->whereIn('role', User::roleAliasesFor('admin'))
            ->whereNotNull('email')
            ->with('tenant')
            ->orderBy('id')
            ->chunkById(100, function ($admins) use ($broadcast): void {
                foreach ($admins as $admin) {
                    $tenant = $admin->tenant;
                    if (! $tenant || ! $this->tenantMatchesTarget($tenant, (string) $broadcast->target)) {
                        continue;
                    }

                    try {
                        $admin->notify(new PlatformBroadcastNotification(
                            (int) $broadcast->id,
                            (string) $broadcast->title,
                            (string) ($broadcast->body ?? ''),
                            isset($broadcast->image_path) ? (string) $broadcast->image_path : null,
                            $broadcast->expires_at
                                ? \Carbon\Carbon::parse($broadcast->expires_at)->format('d M Y')
                                : null,
                        ));
                    } catch (\Throwable $exception) {
                        Log::warning('Platform broadcast email failed for tenant administrator.', [
                            'broadcast_id' => (int) $broadcast->id,
                            'tenant_id' => (int) $tenant->id,
                            'user_id' => (int) $admin->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            });
    }

    private function tenantMatchesTarget(Tenant $tenant, string $target): bool
    {
        if ($target === 'all') {
            return true;
        }

        $isFreeTier = PricingService::isFree(PricingService::activeStudentCount((int) $tenant->id));

        return match ($target) {
            'trial' => $isFreeTier,
            'active' => ! $isFreeTier
                && $tenant->status === Tenant::STATUS_ACTIVE
                && ! $tenant->isExpired(),
            'expired' => ! $isFreeTier && $tenant->isExpired(),
            default => false,
        };
    }
}
