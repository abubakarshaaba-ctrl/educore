<?php
namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Models\NotificationQueue;
use App\Models\Announcement;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    // Subscribe device
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'endpoint'   => ['required', 'string'],
            'p256dh_key' => ['required', 'string'],
            'auth_key'   => ['required', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id'   => auth()->id(),
                'p256dh_key'=> $data['p256dh_key'],
                'auth_key'  => $data['auth_key'],
                'is_active' => true,
            ]
        );
        return response()->json(['status' => 'subscribed']);
    }

    // Unsubscribe
    public function unsubscribe(Request $request)
    {
        PushSubscription::where('endpoint', $request->endpoint)->delete();
        return response()->json(['status' => 'unsubscribed']);
    }

    // Test push (admin)
    public function sendTest(Request $request)
    {
        $subs = PushSubscription::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)->get();

        $queued = 0;
        foreach ($subs as $sub) {
            NotificationQueue::create([
                'channel'   => 'push',
                'recipient' => $sub->endpoint,
                'subject'   => 'Test Notification',
                'body'      => json_encode([
                    'title' => 'Test from '.auth()->user()->tenant?->name,
                    'body'  => 'Push notifications are working!',
                    'icon'  => '/favicon.ico',
                ]),
                'gateway'   => 'web_push',
                'status'    => 'pending',
            ]);
            $queued++;
        }

        return back()->with('success', "Test notification queued for {$queued} device(s).");
    }

    // Send push to all school subscribers
    public function broadcast(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body'  => ['required', 'string', 'max:300'],
        ]);

        $subs = PushSubscription::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)->get();

        foreach ($subs as $sub) {
            NotificationQueue::create([
                'channel'   => 'push',
                'recipient' => $sub->endpoint,
                'subject'   => $data['title'],
                'body'      => json_encode([
                    'title' => $data['title'],
                    'body'  => $data['body'],
                    'icon'  => '/favicon.ico',
                ]),
                'gateway'  => 'web_push',
                'status'   => 'pending',
            ]);
        }

        return back()->with('success', 'Push notification queued for '.$subs->count().' devices.');
    }

    // Get VAPID public key (for service worker)
    public function vapidKey()
    {
        return response()->json([
            'publicKey' => config('services.vapid.public_key', 'VAPID_PUBLIC_KEY_HERE'),
        ]);
    }
}
