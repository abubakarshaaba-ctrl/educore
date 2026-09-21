<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MobileReleaseController extends Controller
{
    private const REPO = 'abubakarshaaba-ctrl/educore';
    private const APP_UPDATES_TOPIC = 'educore_app_updates';
    private const RELEASE_FILE = 'mobile-releases/android.json';
    private const APK_ASSET_NAME = 'EduCore.apk';
    private const APK_CHECKSUM_ASSET_NAME = 'EduCore.apk.sha256';

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

        // Only advertise metadata once the canonical public APK matches the
        // recorded production release. FCM may announce the GitHub asset first,
        // while /download/app catches up through the normal server deployment.
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
     * Release handoff from Codemagic.
     *
     * Preferred authentication is release attestation: the server verifies the
     * published GitHub Release, its tag/version, APK URL and checksum before
     * accepting the push request. A configured shared webhook token remains
     * supported as an optional additional/legacy authentication path.
     */
    public function __invoke(Request $request, PushNotificationService $push): JsonResponse
    {
        $data = $request->validate([
            'version_name' => ['required', 'string', 'max:80'],
            'version_code' => ['required', 'integer', 'min:1'],
            'download_url' => ['required', 'url', 'max:2048'],
            'message' => ['nullable', 'string', 'max:4000'],
            'force_update' => ['nullable', 'boolean'],
            'sha256' => ['required', 'regex:/\A[a-fA-F0-9]{64}\z/'],
            'source_release_tag' => ['required', 'string', 'max:180'],
        ]);

        $versionName = trim($data['version_name']);
        $versionCode = (int) $data['version_code'];
        $forceUpdate = (bool) ($data['force_update'] ?? false);
        $sourceReleaseTag = trim($data['source_release_tag']);
        $sha256 = strtolower(trim($data['sha256']));

        $expectedToken = trim((string) config('services.mobile_release.webhook_token', ''));
        $providedToken = trim((string) $request->bearerToken());
        $authenticatedBySecret = $expectedToken !== ''
            && $providedToken !== ''
            && hash_equals($expectedToken, $providedToken);

        if (! $authenticatedBySecret) {
            $attestation = $this->verifyPublishedRelease(
                $sourceReleaseTag,
                $versionName,
                $versionCode,
                $data['download_url'],
                $sha256,
            );

            if (! ($attestation['verified'] ?? false)) {
                return response()->json([
                    'message' => 'Release attestation failed.',
                    'reason' => $attestation['reason'] ?? 'unknown',
                ], 401);
            }
        }

        $body = trim((string) ($data['message'] ?? ''));
        if ($body === '') {
            $body = "EduCore {$versionName} is ready to install. Tap to update.";
        }

        $previous = null;
        if (Storage::disk('local')->exists(self::RELEASE_FILE)) {
            $decoded = json_decode((string) Storage::disk('local')->get(self::RELEASE_FILE), true);
            if (is_array($decoded)) {
                $previous = $decoded;
            }
        }

        $pushAlreadySent = (
            ($previous['source_release_tag'] ?? null) === $sourceReleaseTag
            && (int) ($previous['version_code'] ?? 0) === $versionCode
            && ! empty($previous['push_sent_at'])
        );

        $release = [
            'platform' => 'android',
            'version_name' => $versionName,
            'version_code' => $versionCode,
            'minimum_supported_version_code' => $forceUpdate ? $versionCode : 1,
            'download_url' => $data['download_url'],
            'message' => $body,
            'force_update' => $forceUpdate,
            'published_at' => now()->toIso8601String(),
            'source_release_tag' => $sourceReleaseTag,
            'sha256' => $sha256,
            'push_sent_at' => $pushAlreadySent ? (string) $previous['push_sent_at'] : null,
        ];

        Storage::disk('local')->put(
            self::RELEASE_FILE,
            json_encode($release, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        if ($pushAlreadySent) {
            return response()->json([
                'status' => 'accepted',
                'version_name' => $versionName,
                'version_code' => $versionCode,
                'topic' => self::APP_UPDATES_TOPIC,
                'delivered' => true,
                'push' => 'skipped-already-notified',
                'push_sent_at' => $release['push_sent_at'],
                'release_recorded' => true,
                'authenticated_by' => $authenticatedBySecret ? 'shared-secret' : 'github-release-attestation',
            ]);
        }

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

        // Release metadata and push delivery are different states. Persist the
        // delivery marker only after FCM accepts the message so a transient
        // failure can be retried by Codemagic or the self-deploy flow.
        if ($delivered) {
            $release['push_sent_at'] = now()->toIso8601String();
            Storage::disk('local')->put(
                self::RELEASE_FILE,
                json_encode($release, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            );
        }

        return response()->json([
            'status' => $delivered ? 'accepted' : 'push_failed',
            'version_name' => $versionName,
            'version_code' => $versionCode,
            'topic' => self::APP_UPDATES_TOPIC,
            'delivered' => $delivered,
            'push' => $delivered ? 'sent' : 'failed',
            'push_sent_at' => $release['push_sent_at'],
            'retryable' => ! $delivered,
            'release_recorded' => true,
            'authenticated_by' => $authenticatedBySecret ? 'shared-secret' : 'github-release-attestation',
        ], $delivered ? 200 : 502);
    }

    private function verifyPublishedRelease(
        string $tag,
        string $versionName,
        int $versionCode,
        string $downloadUrl,
        string $sha256,
    ): array {
        if (! preg_match('/\Aandroid-v(.+)-code(\d+)-b\d+-([0-9a-f]{7,40})\z/i', $tag, $matches)) {
            return ['verified' => false, 'reason' => 'release-tag-invalid'];
        }

        if ($matches[1] !== $versionName || (int) $matches[2] !== $versionCode) {
            return ['verified' => false, 'reason' => 'release-version-mismatch'];
        }

        $headers = [
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'educore-mobile-release-verifier',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
        $githubToken = trim((string) config('app.deploy_gh_token', ''));
        if ($githubToken !== '') {
            $headers['Authorization'] = 'Bearer '.$githubToken;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->get(
                    'https://api.github.com/repos/'.self::REPO.'/releases/tags/'.rawurlencode($tag)
                );

            if (! $response->successful()) {
                return [
                    'verified' => false,
                    'reason' => 'release-query-failed',
                    'http_status' => $response->status(),
                ];
            }

            $release = $response->json();
            if (
                ! is_array($release)
                || ($release['draft'] ?? true)
                || ($release['prerelease'] ?? true)
                || (string) ($release['tag_name'] ?? '') !== $tag
            ) {
                return ['verified' => false, 'reason' => 'release-not-published'];
            }

            $targetCommitish = strtolower((string) ($release['target_commitish'] ?? ''));
            if ($targetCommitish !== '' && preg_match('/\A[0-9a-f]{40}\z/', $targetCommitish)) {
                if (! str_starts_with($targetCommitish, strtolower($matches[3]))) {
                    return ['verified' => false, 'reason' => 'release-commit-mismatch'];
                }
            }

            $assets = is_array($release['assets'] ?? null) ? $release['assets'] : [];
            $apkAsset = collect($assets)->first(
                fn ($asset) => ($asset['name'] ?? null) === self::APK_ASSET_NAME
            );
            if (! is_array($apkAsset)) {
                return ['verified' => false, 'reason' => 'apk-asset-missing'];
            }

            $publishedDownloadUrl = trim((string) ($apkAsset['browser_download_url'] ?? ''));
            if ($publishedDownloadUrl === '' || ! hash_equals($publishedDownloadUrl, trim($downloadUrl))) {
                return ['verified' => false, 'reason' => 'apk-url-mismatch'];
            }

            $digest = strtolower(trim((string) ($apkAsset['digest'] ?? '')));
            if ($digest !== '') {
                if (! hash_equals('sha256:'.$sha256, $digest)) {
                    return ['verified' => false, 'reason' => 'apk-digest-mismatch'];
                }

                return ['verified' => true, 'reason' => 'github-asset-digest'];
            }

            $checksumAsset = collect($assets)->first(
                fn ($asset) => ($asset['name'] ?? null) === self::APK_CHECKSUM_ASSET_NAME
            );
            if (! is_array($checksumAsset) || empty($checksumAsset['url'])) {
                return ['verified' => false, 'reason' => 'checksum-asset-missing'];
            }

            $checksumResponse = Http::withHeaders(array_merge($headers, [
                'Accept' => 'application/octet-stream',
            ]))
                ->timeout(30)
                ->get((string) $checksumAsset['url']);

            if (! $checksumResponse->successful()) {
                return [
                    'verified' => false,
                    'reason' => 'checksum-download-failed',
                    'http_status' => $checksumResponse->status(),
                ];
            }

            $checksumLine = trim($checksumResponse->body());
            if (! preg_match(
                '/\A([a-f0-9]{64})[ \t]+\*?(?:.*[\\\\\/])?EduCore\.apk\z/i',
                $checksumLine,
                $checksumMatches,
            )) {
                return ['verified' => false, 'reason' => 'checksum-format-invalid'];
            }

            if (! hash_equals(strtolower($checksumMatches[1]), $sha256)) {
                return ['verified' => false, 'reason' => 'checksum-mismatch'];
            }

            return ['verified' => true, 'reason' => 'github-checksum-asset'];
        } catch (\Throwable $exception) {
            return [
                'verified' => false,
                'reason' => 'release-verification-exception',
            ];
        }
    }
}
