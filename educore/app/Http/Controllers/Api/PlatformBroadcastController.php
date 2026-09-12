<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlatformBroadcastController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);

        $broadcasts = DB::table('platform_broadcasts as broadcasts')
            ->leftJoin('users as creators', 'creators.id', '=', 'broadcasts.created_by')
            ->select('broadcasts.*', 'creators.name as creator_name')
            ->latest('broadcasts.id')
            ->limit(100)
            ->get()
            ->map(fn ($broadcast) => [
                'id' => (int) $broadcast->id,
                'title' => $broadcast->title,
                'body' => $broadcast->body,
                'target' => $broadcast->target,
                'creator' => $broadcast->creator_name,
                'expires_at' => $broadcast->expires_at,
                'created_at' => $broadcast->created_at,
                'active' => $broadcast->expired_at === null
                    && ($broadcast->expires_at === null || now()->lt($broadcast->expires_at)),
            ]);

        return response()->json(['broadcasts' => $broadcasts]);
    }

    public function store(Request $request)
    {
        $this->guard($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'target' => ['required', Rule::in(['all', 'active', 'trial', 'expired'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $tenantIds = $this->targetTenants($data['target'])->pluck('id');
        $now = now();

        $broadcastId = DB::transaction(function () use ($request, $data, $tenantIds, $now) {
            $broadcastId = DB::table('platform_broadcasts')->insertGetId([
                'created_by' => $request->user()->id,
                'title' => trim($data['title']),
                'body' => trim($data['body']),
                'target' => $data['target'],
                'tenant_count' => $tenantIds->count(),
                'expires_at' => $data['expires_at'] ?? null,
                'expired_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($tenantIds->chunk(250) as $chunk) {
                DB::table('announcements')->insert(
                    $chunk->map(fn ($tenantId) => [
                        'tenant_id' => $tenantId,
                        'platform_broadcast_id' => $broadcastId,
                        'title' => trim($data['title']),
                        'body' => trim($data['body']),
                        'audience' => 'all',
                        'priority' => 'important',
                        'publish_date' => $now->toDateString(),
                        'expire_date' => isset($data['expires_at'])
                            ? \Illuminate\Support\Carbon::parse($data['expires_at'])->toDateString()
                            : null,
                        'is_published' => true,
                        'created_by' => $request->user()->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            }

            return $broadcastId;
        });

        return response()->json([
            'message' => "Broadcast published to {$tenantIds->count()} school(s).",
            'status' => 'published',
            'id' => $broadcastId,
        ], 201);
    }

    public function expire(Request $request, int $broadcast)
    {
        $this->guard($request);

        $record = DB::table('platform_broadcasts')->where('id', $broadcast)->first();
        abort_unless($record, 404, 'Broadcast not found.');

        DB::transaction(function () use ($broadcast) {
            DB::table('platform_broadcasts')->where('id', $broadcast)->update([
                'expired_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('announcements')->where('platform_broadcast_id', $broadcast)->update([
                'expire_date' => today()->subDay()->toDateString(),
                'updated_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Broadcast expired across tenant notices.',
            'status' => 'expired',
            'id' => $broadcast,
        ]);
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
            'expired' => $query->where(function (Builder $expired) {
                $expired->where('status', Tenant::STATUS_SUBSCRIPTION_EXPIRED)
                    ->orWhereDate('subscription_expires_at', '<', today());
            }),
            default => $query,
        };
    }

    private function guard(Request $request): void
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Platform Super Admin access required.');
    }
}
