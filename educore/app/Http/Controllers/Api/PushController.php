<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class PushController extends Controller
{
    /** Register/refresh this device's FCM token for the signed-in user. */
    public function registerToken(Request $request)
    {
        $data = $request->validate([
            'token'    => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:20'],
        ]);

        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id'      => $request->user()->id,
                'platform'     => $data['platform'] ?? 'android',
                'last_seen_at' => now(),
            ]
        );

        return response()->json(['message' => 'Device registered.']);
    }

    public function unregisterToken(Request $request)
    {
        $data = $request->validate(['token' => ['required', 'string']]);
        DeviceToken::where('token', $data['token'])->where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Device unregistered.']);
    }
}
