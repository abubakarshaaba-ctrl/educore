<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Notifications\PlatformBroadcastPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                'image_url' => $broadcast->image_path
                    ? Storage::disk('public')->url($broadcast->image_path)
                    : null,
                'target' => $broadcast->target,
                'creator' => $broadcast->creator_name,
                'expires_at' => $broadcast->expires_at,
                'created_at' => $broadcast->created_at,
                'active' => $broadcast->expired_at === null
                    && ($broadcast->expires_at === null || now()->lt($broadcast->expires_at)),
            ]);

        return response()->json(['broadcasts' => $broadcasts]);
    }

    public function store(
        Request $request,
        PlatformBroadcastPublisher $publisher,
    ) {
        $this->guard($request);

        $data = $request->validate($publisher->validationRules());

        $publication = $publisher->publish(
            actor: $request->user(),
            data: $data,
            image: $request->file('image'),
        );

        return response()->json([
            'message' => "Broadcast published to {$publication['tenant_count']} school(s). Push and email delivery is processing.",
            'status' => 'published',
            'delivery_status' => 'processing_after_response',
            'id' => $publication['id'],
            // Keep the existing numeric response contract stable. Final
            // delivery totals are recorded in the application log after the
            // response has been sent.
            'push_users_matched' => 0,
            'push_device_tokens' => 0,
            'push_notifications_sent' => 0,
            'push_notifications_failed' => 0,
            'email_notifications_sent' => 0,
            'email_notifications_failed' => 0,
            'image_url' => $publication['image_path']
                ? Storage::disk('public')->url($publication['image_path'])
                : null,
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

    private function guard(Request $request): void
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Platform Super Admin access required.');
    }
}
