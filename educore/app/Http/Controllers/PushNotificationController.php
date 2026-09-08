<?php

namespace App\Http\Controllers;

use App\Models\NotificationQueue;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PushNotificationController extends Controller
{
    // Subscribe the authenticated account's browser/device endpoint.
    public function subscribe(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user && $user->tenant_id, 403, 'A school account is required.');

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:255'],
            'p256dh_key' => ['required', 'string', 'max:512'],
            'auth_key' => ['required', 'string', 'max:255'],
        ]);

        // endpoint is globally unique in the database. Resolve it without the
        // tenant scope so a browser endpoint that belongs to another account or
        // school cannot be silently taken over by an updateOrCreate call.
        $existing = PushSubscription::withoutTenantScope()
            ->where('endpoint', $data['endpoint'])
            ->first();

        if ($existing && (
            (int) $existing->tenant_id !== (int) $user->tenant_id
            || (int) $existing->user_id !== (int) $user->id
        )) {
            throw ValidationException::withMessages([
                'endpoint' => 'This push endpoint cannot be registered for this account.',
            ]);
        }

        $subscription = $existing ?: new PushSubscription();
        $subscription->forceFill([
            'tenant_id' => (int) $user->tenant_id,
            'user_id' => (int) $user->id,
            'endpoint' => $data['endpoint'],
            'p256dh_key' => $data['p256dh_key'],
            'auth_key' => $data['auth_key'],
            'is_active' => true,
        ])->save();

        return response()->json(['status' => 'subscribed']);
    }

    // Unsubscribe only an endpoint owned by the authenticated account.
    public function unsubscribe(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user && $user->tenant_id, 403, 'A school account is required.');

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:255'],
        ]);

        PushSubscription::withoutTenantScope()
            ->where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->where('endpoint', $data['endpoint'])
            ->delete();

        // Keep this idempotent so browsers can safely retry an unsubscribe.
        return response()->json(['status' => 'unsubscribed']);
    }

    // Test push — sending is a privileged notification action.
    public function sendTest(Request $request)
    {
        $user = $this->authorizeSender();

        $subs = PushSubscription::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->get();

        $queued = 0;
        foreach ($subs as $sub) {
            NotificationQueue::create([
                'channel' => 'push',
                'recipient' => $sub->endpoint,
                'subject' => 'Test Notification',
                'body' => json_encode([
                    'title' => 'Test from '.$user->tenant?->name,
                    'body' => 'Push notifications are working!',
                    'icon' => '/favicon.ico',
                ]),
                'gateway' => 'web_push',
                'status' => 'pending',
            ]);
            $queued++;
        }

        return back()->with('success', "Test notification queued for {$queued} device(s).");
    }

    // Send push to all school subscribers.
    public function broadcast(Request $request)
    {
        $user = $this->authorizeSender();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:300'],
        ]);

        $subs = PushSubscription::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->get();

        foreach ($subs as $sub) {
            NotificationQueue::create([
                'channel' => 'push',
                'recipient' => $sub->endpoint,
                'subject' => $data['title'],
                'body' => json_encode([
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'icon' => '/favicon.ico',
                ]),
                'gateway' => 'web_push',
                'status' => 'pending',
            ]);
        }

        return back()->with('success', 'Push notification queued for '.$subs->count().' devices.');
    }

    // Get VAPID public key (public by design; private key never leaves config).
    public function vapidKey()
    {
        return response()->json([
            'publicKey' => config('services.vapid.public_key', 'VAPID_PUBLIC_KEY_HERE'),
        ]);
    }

    private function authorizeSender(): User
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user && $user->tenant_id, 403, 'A school account is required.');
        abort_if($user->isStudent() || $user->isParent(), 403, 'Notification sending permission required.');

        // canAccessModule('notifications') intentionally accepts view/send
        // submodules, so use exact authority for a write action. A parent-level
        // custom deny also blocks notification sending.
        $allowed = ! $user->hasDeniedPermission('notifications')
            && (
                $user->canAccessExactModule('notifications')
                || $user->canAccessExactModule('notifications.send')
            );

        abort_unless($allowed, 403, 'Notification sending permission required.');

        return $user;
    }
}
