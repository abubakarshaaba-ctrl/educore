<?php

namespace App\Services\Notifications;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Tenant\PlatformBroadcastNotification;
use App\Services\TenantUrlGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class PlatformBroadcastEmailService
{
    public function __construct(
        private readonly TenantUrlGenerator $tenantUrls,
    ) {
    }

    /**
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
        string $recipientScope = 'tenant_admin',
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
                $recipientScope,
                &$stats
            ): void {
                foreach ($tenants as $tenant) {
                    $stats['tenants']++;

                    $recipients = User::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('is_active', true)
                        ->where('is_super_admin', false)
                        ->whereNotNull('email')
                        ->where('email', '!=', '')
                        ->when(
                            $recipientScope === 'tenant_admin',
                            fn (Builder $query) => $query
                                ->whereIn('role', User::roleAliasesFor('admin'))
                                ->where(function (Builder $employment): void {
                                    $employment->whereNull('employment_status')
                                        ->orWhere('employment_status', User::STAFF_STATUS_ACTIVE);
                                }),
                        )
                        ->orderBy('id')
                        ->get(['id', 'tenant_id', 'name', 'email', 'role', 'is_super_admin']);

                    $seen = [];
                    foreach ($recipients as $recipient) {
                        $email = strtolower(trim((string) $recipient->email));
                        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$email])) {
                            continue;
                        }

                        $seen[$email] = true;
                        $stats['recipients']++;

                        try {
                            $recipient->notify($this->notification(
                                tenant: $tenant,
                                recipient: $recipient,
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
                                'user_id' => $recipient->id,
                                'recipient_scope' => $recipientScope,
                                'error' => $error->getMessage(),
                            ]);
                        }
                    }
                }
            });

        return $stats;
    }

    private function notification(
        Tenant $tenant,
        User $recipient,
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
            actionUrl: $this->actionUrl($tenant, $recipient),
        );
    }

    private function actionUrl(Tenant $tenant, User $recipient): string
    {
        $path = match (true) {
            $recipient->isAdmin() => '/platform-notices',
            $recipient->isParent() => '/parent/notifications',
            $recipient->isStudent() => '/student/dashboard',
            $recipient->canAccessModule('announcements') => '/announcements',
            default => '/my/dashboard',
        };

        return $this->tenantUrls->url($tenant, $path);
    }
}
