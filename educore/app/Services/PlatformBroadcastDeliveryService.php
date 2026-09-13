<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlatformBroadcastDeliveryService
{
    public function forUser(User $user): Collection
    {
        if (! $user->tenant_id || $user->isSuperAdmin()) {
            return collect();
        }

        $tenant = $user->tenant;
        $state = $this->tenantState($tenant);

        return DB::table('platform_broadcasts as broadcasts')
            ->whereIn('broadcasts.target', ['all', $state])
            ->where(fn ($query) => $query->whereNull('broadcasts.expires_at')->orWhere('broadcasts.expires_at', '>=', now()))
            ->whereNotExists(function ($query) use ($user): void {
                $query->selectRaw('1')
                    ->from('platform_broadcast_dismissals as dismissals')
                    ->whereColumn('dismissals.broadcast_id', 'broadcasts.id')
                    ->where('dismissals.tenant_id', $user->tenant_id);
            })
            ->orderByDesc('broadcasts.created_at')
            ->orderByDesc('broadcasts.id')
            ->get();
    }

    public function asNotificationItems(User $user): Collection
    {
        return $this->forUser($user)->map(fn ($broadcast): array => [
            // Negative IDs keep platform broadcasts collision-free from tenant announcements.
            'id' => -1 * (int) $broadcast->id,
            'title' => (string) $broadcast->title,
            'body' => (string) $broadcast->body,
            'priority' => 'important',
            'published_at' => $broadcast->created_at
                ? Carbon::parse($broadcast->created_at)->toIso8601String()
                : now()->toIso8601String(),
            'expires_at' => $broadcast->expires_at ? Carbon::parse($broadcast->expires_at)->toIso8601String() : null,
            // Platform broadcasts are tenant-level notices, not per-user announcement reads.
            'is_read' => true,
            'deep_link' => ['type' => 'platform_broadcast', 'id' => (string) $broadcast->id],
        ]);
    }

    private function tenantState($tenant): string
    {
        if (! $tenant) return 'active';
        if ((string) $tenant->status === 'trial') return 'trial';
        if ($tenant->subscription_expires_at && now()->greaterThan($tenant->subscription_expires_at)) return 'expired';
        return 'active';
    }
}
