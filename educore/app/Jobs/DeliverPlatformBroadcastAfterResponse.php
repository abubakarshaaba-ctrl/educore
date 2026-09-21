<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Services\Notifications\PlatformBroadcastEmailService;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliverPlatformBroadcastAfterResponse
{
    use Dispatchable;

    /**
     * @param array<int> $tenantIds
     */
    public function __construct(
        public readonly int $broadcastId,
        public readonly array $tenantIds,
    ) {
    }

    public function handle(
        PushNotificationService $push,
        PlatformBroadcastEmailService $emails,
    ): void {
        try {
            $this->deliver($push, $emails);
        } catch (Throwable $error) {
            // The HTTP publication response has already been sent by the time
            // this command runs. Never allow a downstream transport/storage
            // exception to masquerade as a failed broadcast publication.
            Log::error('Platform broadcast after-response delivery aborted unexpectedly.', [
                'broadcast_id' => $this->broadcastId,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function deliver(
        PushNotificationService $push,
        PlatformBroadcastEmailService $emails,
    ): void {
        $broadcast = DB::table('platform_broadcasts')
            ->where('id', $this->broadcastId)
            ->first();

        if (! $broadcast) {
            Log::warning('Platform broadcast delivery skipped because the broadcast no longer exists.', [
                'broadcast_id' => $this->broadcastId,
            ]);

            return;
        }

        $pushStats = [
            'users' => 0,
            'tokens' => 0,
            'sent' => 0,
            'failed' => 0,
        ];

        Announcement::withoutTenantScope()
            ->where('platform_broadcast_id', $this->broadcastId)
            ->orderBy('id')
            ->chunkById(100, function ($announcements) use ($push, &$pushStats): void {
                foreach ($announcements as $announcement) {
                    try {
                        $result = $push->notifyAnnouncementPublished($announcement);
                        $pushStats['users'] += (int) ($result['users'] ?? 0);
                        $pushStats['tokens'] += (int) ($result['tokens'] ?? 0);
                        $pushStats['sent'] += (int) ($result['sent'] ?? 0);
                        $pushStats['failed'] += (int) ($result['failed'] ?? 0);
                    } catch (Throwable $error) {
                        $pushStats['failed']++;
                        Log::error('Platform broadcast push delivery failed for an announcement.', [
                            'broadcast_id' => $this->broadcastId,
                            'announcement_id' => $announcement->id,
                            'tenant_id' => $announcement->tenant_id,
                            'error' => $error->getMessage(),
                        ]);
                    }
                }
            });

        $emailStats = [
            'tenants' => count($this->tenantIds),
            'recipients' => 0,
            'sent' => 0,
            'failed' => 0,
        ];

        try {
            $emailStats = $emails->sendToTenantIds(
                tenantIds: $this->tenantIds,
                broadcastId: $this->broadcastId,
                title: (string) $broadcast->title,
                body: (string) $broadcast->body,
                imagePath: $broadcast->image_path ?? null,
                expiresAt: $broadcast->expires_at ?? null,
            );
        } catch (Throwable $error) {
            $emailStats['failed']++;
            Log::error('Platform broadcast email delivery failed before recipient completion.', [
                'broadcast_id' => $this->broadcastId,
                'error' => $error->getMessage(),
            ]);
        }

        Log::info('Platform broadcast delivery completed.', [
            'broadcast_id' => $this->broadcastId,
            'targeted_tenants' => count($this->tenantIds),
            'push_users_matched' => $pushStats['users'],
            'push_device_tokens' => $pushStats['tokens'],
            'push_notifications_sent' => $pushStats['sent'],
            'push_notifications_failed' => $pushStats['failed'],
            'email_recipients' => $emailStats['recipients'] ?? 0,
            'email_notifications_sent' => $emailStats['sent'] ?? 0,
            'email_notifications_failed' => $emailStats['failed'] ?? 0,
        ]);
    }
}
