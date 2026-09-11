<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformNoticeController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->guard($request);
        $tenant = $user->tenant;
        $tenantStatus = PricingService::isFree(PricingService::activeStudentCount($user->tenant_id))
            ? 'trial'
            : (($tenant?->status ?? null) === 'active' ? 'active' : 'expired');

        $notices = DB::table('platform_broadcasts')
            ->whereIn('target', ['all', $tenantStatus])
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($query) use ($user): void {
                $query->whereNull('audience')
                    ->orWhere('audience', 'staff')
                    ->when($user->role === 'admin', fn ($q) => $q->orWhere('audience', 'admins'))
                    ->orWhere('audience', 'all_accounts');
            })
            ->leftJoin('platform_broadcast_user_reads as reads', function ($join) use ($user): void {
                $join->on('reads.broadcast_id', '=', 'platform_broadcasts.id')
                    ->where('reads.user_id', '=', $user->id);
            })
            ->whereNull('reads.dismissed_at')
            ->select([
                'platform_broadcasts.id',
                'platform_broadcasts.title',
                'platform_broadcasts.body',
                'platform_broadcasts.target',
                'platform_broadcasts.audience',
                'platform_broadcasts.priority',
                'platform_broadcasts.expires_at',
                'platform_broadcasts.created_at',
                'reads.read_at',
            ])
            ->orderByRaw("CASE platform_broadcasts.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 ELSE 2 END")
            ->orderByDesc('platform_broadcasts.created_at')
            ->limit(50)
            ->get()
            ->map(fn ($notice) => [
                'id' => (int) $notice->id,
                'title' => $notice->title,
                'body' => $notice->body,
                'priority' => $notice->priority ?: 'normal',
                'audience' => $notice->audience ?: 'staff',
                'is_read' => $notice->read_at !== null,
                'created_at' => $notice->created_at,
                'expires_at' => $notice->expires_at,
                'source' => 'platform',
            ]);

        return response()->json([
            'notices' => $notices,
            'unread_count' => $notices->where('is_read', false)->count(),
        ]);
    }

    public function markRead(Request $request, int $broadcast)
    {
        $user = $this->guard($request);
        $this->assertVisibleTo($user, $broadcast);

        DB::table('platform_broadcast_user_reads')->updateOrInsert(
            ['broadcast_id' => $broadcast, 'user_id' => $user->id],
            [
                'tenant_id' => $user->tenant_id,
                'read_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['message' => 'Platform notice marked as read.']);
    }

    public function dismiss(Request $request, int $broadcast)
    {
        $user = $this->guard($request);
        $this->assertVisibleTo($user, $broadcast);

        DB::table('platform_broadcast_user_reads')->updateOrInsert(
            ['broadcast_id' => $broadcast, 'user_id' => $user->id],
            [
                'tenant_id' => $user->tenant_id,
                'read_at' => now(),
                'dismissed_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['message' => 'Platform notice dismissed.']);
    }

    private function guard(Request $request): User
    {
        $user = $request->user();
        abort_unless(
            $user && $user->tenant_id && $user->isTenantStaff() && $user->is_active,
            403,
            'An active staff account from a school is required to view platform notices.'
        );

        return $user;
    }

    private function assertVisibleTo(User $user, int $broadcast): void
    {
        $tenant = $user->tenant;
        $tenantStatus = PricingService::isFree(PricingService::activeStudentCount($user->tenant_id))
            ? 'trial'
            : (($tenant?->status ?? null) === 'active' ? 'active' : 'expired');

        $exists = DB::table('platform_broadcasts')
            ->where('id', $broadcast)
            ->whereIn('target', ['all', $tenantStatus])
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($query) use ($user): void {
                $query->whereNull('audience')
                    ->orWhere('audience', 'staff')
                    ->when($user->role === 'admin', fn ($q) => $q->orWhere('audience', 'admins'))
                    ->orWhere('audience', 'all_accounts');
            })
            ->exists();

        abort_unless($exists, 404, 'Platform notice not found.');
    }
}
