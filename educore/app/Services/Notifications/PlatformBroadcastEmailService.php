<?php

namespace App\Services\Notifications;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Tenant\PlatformBroadcastNotification;
use App\Services\TenantUrlGenerator;
use Illuminate\Support\Facades\Log;

class PlatformBroadcastEmailService
{
    public function __construct(
        private readonly TenantUrlGenerator $tenantUrls,
    ) {
    }

    /**
     * Email a platform broadcast only to active tenant-administrator user
     * accounts. Tenant contact addresses and other leadership/staff roles are
     * deliberately excluded from platform broadcast delivery.
     *
     * @param iterable<int|string> $tenantIds
     * @return array{tenants:int,recipients:int,sent:int,failed:int}
     */
    public function sendToTenantIds(
        iterable $tenantIds,
        int $broadcastId,
        string $title,
        string $body,
        ?string $imagePath = null,
        ?string $expiresAt = null,
    ): array {
        $ids = collect($tenantIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $stats = [
            'tenants' => 0,
            'recipients' => 0,
            'sent' => 0,
            'failed' => 0,
        ];

        if ($ids->isEmpty()) {
            return $stats;
        }

        Tenant::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->chunkById(100, function ($tenants) use (
                $broadcastId,
                $title,
                $body,
                $imagePath,
                $expiresAt,
                &$stats
            ): void {
                foreach ($tenants as $tenant) {
                    $stats['tenants']++;

                    $admins = User::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('is_active', true)
                        ->where(function ($query): void {
                            $query->whereNull('employment_status')
                                ->orWhere('employment_status', User::STAFF_STATUS_ACTIVE);
                        })
                        ->whereIn('role', User::roleAliasesFor('admin'))
                        ->whereNotNull('email')
                        ->where('email', '!=', '')
                        ->orderBy('id')
                        ->get(['id', 'name', 'email']);

                    $seen = [];
                    foreach ($admins as $admin) {
                        $email = strtolower(trim((string) $admin->email));
                        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$email])) {
                            continue;
                        }
                        $seen[$email] = true;
                        $stats['recipients']++;

                        try {
                            $admin->notify($this->notification(
                                tenant: $tenant,
                                broadcastId: $broadcastId,
                                title: $title,
                                body: $body,
                                imagePath: $imagePath,
                                expiresAt: $expiresAt,
                            ));
                            $stats['sent']++;
                        } catch (\Throwable $error) {
                            $stats['failed']++;
                            Log::error('Platform broadcast email failed.', [
                                'broadcast_id' => $broadcastId,
                                'tenant_id' => $tenant->id,
                                'user_id' => $admin->id,
                                'error' => $error->getMessage(),
                            ]);
                        }
                    }

                }
                }
            });

        return $stats;
    }

    private function notification(
        Tenant $tenant,
        int $broadcastId,
        string $title,
        string $body,
        ?string $imagePath,
        ?string $expiresAt,
    ): PlatformBroadcastNotification {
        return new PlatformBroadcastNotification(
            broadcastId: $broadcastId,
            title: $title,
            body: $body,
            imagePath: $imagePath,
            expiresAt: $expiresAt,
            schoolName: $tenant->name,
            actionUrl: $this->tenantUrls->url($tenant, '/platform-notices'),
        );
    }
}
