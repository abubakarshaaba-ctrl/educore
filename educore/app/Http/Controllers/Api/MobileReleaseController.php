<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MobileReleaseController extends Controller
{
    public function __invoke(Request $request, PushNotificationService $push): JsonResponse
    {
        $expectedToken = (string) config('services.mobile_release.webhook_token', '');
        $providedToken = (string) $request->bearerToken();

        abort_if(
            $expectedToken === '' || $providedToken === '' || ! hash_equals($expectedToken, $providedToken),
            403,
            'Release notification authorization failed.'
        );

        $data = $request->validate([
            'version_name' => ['required', 'string', 'max:50'],
            'version_code' => ['required', 'integer', 'min:1'],
            'download_url' => ['required', 'url', 'max:2048'],
            'message' => ['nullable', 'string', 'max:500'],
            'force_update' => ['nullable', 'boolean'],
        ]);

        $versionName = trim($data['version_name']);
        $body = trim((string) ($data['message'] ?? ''));
        if ($body === '') {
            $body = "EduCore {$versionName} is ready to install.";
        }

        $payload = [
            'type' => 'app_update',
            'version_name' => $versionName,
            'version_code' => (string) $data['version_code'],
            'download_url' => $data['download_url'],
            'force_update' => ! empty($data['force_update']) ? 'true' : 'false',
        ];

        $attempted = 0;
        $delivered = 0;

        DeviceToken::query()
            ->where(function ($query): void {
                $query->whereNull('platform')->orWhere('platform', 'android');
            })
            ->orderBy('id')
            ->chunkById(100, function ($tokens) use ($push, $body, $payload, &$attempted, &$delivered): void {
                foreach ($tokens as $device) {
                    $attempted++;
                    if ($push->send(
                        (string) $device->token,
                        'EduCore update available',
                        Str::limit($body, 180),
                        $payload,
                    )) {
                        $delivered++;
                    }
                }
            });

        return response()->json([
            'status' => 'accepted',
            'version_name' => $versionName,
            'version_code' => (int) $data['version_code'],
            'attempted' => $attempted,
            'delivered' => $delivered,
        ]);
    }
}
