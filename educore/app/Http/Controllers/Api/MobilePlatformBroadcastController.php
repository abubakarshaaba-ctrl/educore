<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Notifications\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobilePlatformBroadcastController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->guard($request);
        if (! Schema::hasTable('platform_broadcasts')) {
            return response()->json(['broadcasts' => []]);
        }

        $broadcasts = DB::table('platform_broadcasts')
            ->leftJoin('users', 'users.id', '=', 'platform_broadcasts.created_by')
            ->select('platform_broadcasts.*', 'users.name as creator_name')
            ->orderByDesc('platform_broadcasts.created_at')
            ->limit(100)
            ->get()
            ->map(fn ($item): array => [
                'id' => (int) $item->id,
                'title' => $item->title,
                'body' => $item->body,
                'target' => $item->target,
                'creator' => $item->creator_name,
                'expires_at' => $item->expires_at,
                'created_at' => $item->created_at,
                'active' => $item->expires_at === null || Carbon::parse($item->expires_at)->isFuture(),
            ]);

        return response()->json(['broadcasts' => $broadcasts]);
    }

    public function store(Request $request, PushNotificationService $push): JsonResponse
    {
        $user = $this->guard($request);
        abort_unless(Schema::hasTable('platform_broadcasts'), 503, 'Platform broadcasts are not available on this installation.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'target' => ['required', Rule::in(['all', 'active', 'trial', 'expired'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $tenantIds = $this->targetTenantIds($data['target']);
        $announcements = [];
        $broadcastId = DB::transaction(function () use ($data, $user, $tenantIds, &$announcements): int {
            $id = DB::table('platform_broadcasts')->insertGetId([
                'title' => trim($data['title']),
                'body' => trim($data['body']),
                'target' => $data['target'],
                'created_by' => $user->id,
                'expires_at' => $data['expires_at'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($tenantIds as $tenantId) {
                $announcements[] = Announcement::create([
                    'tenant_id' => $tenantId,
                    'title' => trim($data['title']),
                    'body' => trim($data['body']),
                    'audience' => 'admin',
                    'priority' => 'important',
                    'publish_date' => today(),
                    'expire_date' => isset($data['expires_at']) ? Carbon::parse($data['expires_at'])->toDateString() : null,
                    'is_published' => true,
                    'created_by' => $user->id,
                ]);
            }

            return $id;
        });

        $pushSent = 0;
        foreach ($announcements as $announcement) {
            if ($push->notifyAnnouncementPublished($announcement)) {
                $pushSent++;
            }
        }

        return response()->json([
            'message' => sprintf('Platform broadcast delivered to %d school(s).', count($tenantIds)),
            'id' => $broadcastId,
            'schools' => count($tenantIds),
            'push_batches_sent' => $pushSent,
        ], 201);
    }

    public function expire(Request $request, int $broadcast): JsonResponse
    {
        $this->guard($request);
        $record = DB::table('platform_broadcasts')->where('id', $broadcast)->first();
        abort_unless($record, 404, 'Broadcast not found.');
        if ($record->expires_at !== null && Carbon::parse($record->expires_at)->isPast()) {
            throw ValidationException::withMessages(['broadcast' => 'This broadcast has already expired.']);
        }

        DB::table('platform_broadcasts')->where('id', $broadcast)->update([
            'expires_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Platform broadcast expired.']);
    }

    private function targetTenantIds(string $target): array
    {
        $query = Tenant::query();
        match ($target) {
            'active' => $query->where('status', Tenant::STATUS_ACTIVE),
            'expired' => $query->where(function ($q): void {
                $q->where('status', Tenant::STATUS_SUBSCRIPTION_EXPIRED)
                    ->orWhereDate('subscription_expires_at', '<', today());
            }),
            'trial' => $query->where('status', Tenant::STATUS_PENDING),
            default => null,
        };

        return $query->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    private function guard(Request $request): User
    {
        $user = $request->user();
        abort_unless($user?->isSuperAdmin(), 403, 'Platform Super Admin access required.');
        return $user;
    }
}
