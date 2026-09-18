<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MobileReleaseController extends Controller
{
    private const APP_UPDATES_TOPIC = 'educore_app_updates';
    private const RELEASE_FILE = 'mobile-releases/android.json';

    /**
     * Public, read-only release metadata used by the Android app's foreground
     * and WorkManager update checks. This avoids querying a private GitHub
     * repository from installed apps.
     */
    public function latest(): JsonResponse
    {
        if (! Storage::disk('local')->exists(self::RELEASE_FILE)) {
            return response()->json([
                'available' => false,
                'release' => null,
            ]);
        }

        $release = json_decode(
            (string) Storage::disk('local')->get(self::RELEASE_FILE),
            true,
        );

        if (! is_array($release)) {
            return response()->json([
                'available' => false,
                'release' => null,
            ]);
        }

        // Never advertise release metadata unless it matches the APK that the
        // public download endpoint actually serves. This prevents a successful
        // release webhook from putting installed apps into an update loop while
        // /download/app is still pinned to the previous verified APK.
        $expectedSha256 = strtolower(trim((string) ($release['sha256'] ?? '')));
        $deployedApk = public_path('downloads/EduCore.apk');
        if (
            ! preg_match('/\A[a-f0-9]{64}\z/', $expectedSha256)
            || ! is_file($deployedApk)
            || ! hash_equals($expectedSha256, strtolower((string) hash_file('sha256', $deployedApk)))
        ) {
            return response()->json([
                'available' => false,
                'release' => null,
            ]);
        }

        return response()->json([
            'available' => true,
            'release' => $release,
        ]);
    }

    /**
     * Protected Codemagic release webhook. It records the newest Android
     * release and immediately sends a high-priority data-only FCM topic push.
     */
    public function __invoke(Request $request, PushNotificationService $push): JsonResponse
    {
        $expectedToken = trim((string) config('services.mobile_release.webhook_token', ''));
        $providedToken = trim((string) $request->bearerToken());

        if ($expectedToken === '') {
            return response()->json([
                'message' => 'Mobile release webhook authentication is not configured on the server.',
            ], 503);
        }

        if ($providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'message' => 'Release notification authorization failed.',
            ], 401);
        }

        $data = $request->validate([
            'version_name' => ['required', 'string', 'max:80'],
            'version_code' => ['required', 'integer', 'min:1'],
            'download_url' => ['required', 'url', 'max:2048'],
            'message' => ['nullable', 'string', 'max:4000'],
            'force_update' => ['nullable', 'boolean'],
        ]);

        $versionName = trim($data['version_name']);
        $versionCode = (int) $data['version_code'];
        $forceUpdate = (bool) ($data['force_update'] ?? false);
        $body = trim((string) ($data['message'] ?? ''));
        if ($body === '') {
            $body = "EduCore {$versionName} is ready to install. Tap to update.";
        }

        $release = [
            'platform' => 'android',
            'version_name' => $versionName,
            'version_code' => $versionCode,
            'minimum_supported_version_code' => $forceUpdate ? $versionCode : 1,
            'download_url' => $data['download_url'],
            'message' => $body,
            'force_update' => $forceUpdate,
            'published_at' => now()->toIso8601String(),
        ];

        Storage::disk('local')->put(
            self::RELEASE_FILE,
            json_encode($release, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        $payload = [
            'type' => 'app_update',
            'version_name' => $versionName,
            'version_code' => (string) $versionCode,
            'minimum_supported_version_code' => (string) $release['minimum_supported_version_code'],
            'download_url' => $data['download_url'],
            'force_update' => $forceUpdate ? 'true' : 'false',
        ];

        $title = $forceUpdate ? 'EduCore update required' : 'EduCore update available';
        $delivered = $push->sendToTopic(
            self::APP_UPDATES_TOPIC,
            $title,
            Str::limit($body, 180),
            $payload,
        );

        return response()->json([
            'status' => $delivered ? 'accepted' : 'push_failed',
            'version_name' => $versionName,
            'version_code' => $versionCode,
            'topic' => self::APP_UPDATES_TOPIC,
            'delivered' => $delivered,
            'release_recorded' => true,
        ], $delivered ? 200 : 502);
    }
}
