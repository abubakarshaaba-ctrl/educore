<?php

namespace App\Services\Notifications;

use App\Jobs\DeliverPlatformBroadcastAfterResponse;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class PlatformBroadcastPublisher
{
    public function validationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:5000', 'required_without:image'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_without:body'],
            'target' => ['required', Rule::in(['all', 'active', 'trial', 'expired'])],
            'recipient_scope' => ['required', Rule::in(['tenant_admin', 'all_users'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * Persist a platform broadcast and its tenant in-app announcements.
     * External push/email delivery is deliberately deferred until after the
     * HTTP response so transport latency cannot turn a successful publication
     * into a 500 response.
     *
     * @return array{id:int,tenant_ids:array<int>,tenant_count:int,image_path:?string}
     */
    public function publish(
        User $actor,
        array $data,
        ?UploadedFile $image = null,
    ): array {
        $title = trim((string) $data['title']);
        $body = trim((string) ($data['body'] ?? ''));
        $target = (string) $data['target'];
        $recipientScope = (string) $data['recipient_scope'];
        $expiresAt = $data['expires_at'] ?? null;

        $tenantIds = $this->targetTenants($target)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $imagePath = $image?->store('platform-broadcasts', 'public');
        $now = now();

        try {
            $broadcastId = DB::transaction(function () use (
                $actor,
                $title,
                $body,
                $target,
                $recipientScope,
                $expiresAt,
                $tenantIds,
                $imagePath,
                $now,
            ): int {
                $broadcastId = DB::table('platform_broadcasts')->insertGetId([
                    'created_by' => $actor->id,
                    'title' => $title,
                    'body' => $body,
                    'image_path' => $imagePath,
                    'target' => $target,
                    'recipient_scope' => $recipientScope,
                    'tenant_count' => count($tenantIds),
                    'expires_at' => $expiresAt,
                    'expired_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach (array_chunk($tenantIds, 250) as $chunk) {
                    if ($chunk === []) {
                        continue;
                    }

                    DB::table('announcements')->insert(
                        array_map(
                            fn (int $tenantId): array => [
                                'tenant_id' => $tenantId,
                                'platform_broadcast_id' => $broadcastId,
                                'title' => $title,
                                'body' => $body,
                                'image_path' => $imagePath,
                                'audience' => $recipientScope === 'all_users' ? 'all' : 'tenant_admin',
                                'priority' => 'important',
                                'publish_date' => $now->toDateString(),
                                'expire_date' => $expiresAt
                                    ? \Illuminate\Support\Carbon::parse($expiresAt)->toDateString()
                                    : null,
                                'is_published' => true,
                                'created_by' => $actor->id,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ],
                            $chunk,
                        ),
                    );
                }

                return (int) $broadcastId;
            });
        } catch (Throwable $error) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $error;
        }

        DeliverPlatformBroadcastAfterResponse::dispatchAfterResponse(
            $broadcastId,
            $tenantIds,
        );

        return [
            'id' => $broadcastId,
            'tenant_ids' => $tenantIds,
            'tenant_count' => count($tenantIds),
            'image_path' => $imagePath,
        ];
    }

    private function targetTenants(string $target): Builder
    {
        $query = Tenant::query();

        return match ($target) {
            'active' => $query->where('status', Tenant::STATUS_ACTIVE)
                ->where(fn (Builder $expiry) => $expiry
                    ->whereNull('subscription_expires_at')
                    ->orWhereDate('subscription_expires_at', '>=', today())),
            'trial' => $query->where('status', Tenant::STATUS_PENDING),
            'expired' => $query->where(function (Builder $expired): void {
                $expired->where('status', Tenant::STATUS_SUBSCRIPTION_EXPIRED)
                    ->orWhereDate('subscription_expires_at', '<', today());
            }),
            default => $query,
        };
    }
}
