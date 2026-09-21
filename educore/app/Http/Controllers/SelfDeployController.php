<?php

namespace App\Http\Controllers;

use App\Services\Notifications\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Shell-free deployment for shared hosting.
 *
 * cPanel's Git "Deploy HEAD Commit" requires shell access, which this host
 * does not grant, so .cpanel.yml never runs. This controller replaces it:
 * it downloads the GitHub zipball of master over HTTPS, extracts it, and
 * syncs the same paths .cpanel.yml would have rsynced, then clears caches.
 *
 * Trigger: GET /deploy/pull?token=<DEPLOY_TOKEN from .env>
 */
class SelfDeployController extends Controller
{
    private const REPO = 'abubakarshaaba-ctrl/educore';
    private const APK_ASSET_NAME = 'EduCore.apk';
    private const APK_CHECKSUM_ASSET_NAME = 'EduCore.apk.sha256';
    private const ANDROID_RELEASE_TAG_PREFIX = 'android-';
    private const MINIMUM_APK_BYTES = 1048576;

    /** Paths (relative to repo root) synced into the live tree. */
    private const SYNC_PATHS = [
        'educore/app/',
        'educore/routes/',
        'educore/resources/',
        'educore/config/',
        'educore/database/',
        'educore/bootstrap/app.php',
        'educore/bootstrap/providers.php',
        'educore/tools/',
        'educore/public/',
        'brand/',
        '.htaccess',
        'index.php',
        '.user.ini',
    ];

    /** Live files that must never be overwritten by stale Git-tracked copies. */
    private const PRESERVED_LIVE_PATHS = [
        'educore/public/downloads/EduCore.apk',
    ];

    /** Files intentionally retired from the live tree after a successful sync. */
    private const DEPRECATED_PATHS = [
        'brand/educore-premium-landing.png',
        'educore/public/brand/educore-premium-landing.png',
        'educore/app/Http/Controllers/ExamBodyRegistrationController.php',
        'educore/app/Models/ExamBodyRegistration.php',
        'educore/resources/views/exam-bodies/index.blade.php',
    ];

    public function pull(Request $request, PushNotificationService $push)
    {
        $expected = (string) (config('app.deploy_token') ?: self::derivedToken());

        if ($expected === '' || !hash_equals($expected, (string) $request->query('token'))) {
            abort(403, 'Invalid deploy token.');
        }

        // Keep running even if the gateway (Cloudflare) times out the request.
        @set_time_limit(0);
        @ignore_user_abort(true);

        $docroot = dirname(base_path()); // public_html
        $work    = storage_path('app/self-deploy');
        $zipPath = $work . '/repo.zip';

        @mkdir($work, 0755, true);

        // 1. Download the master zipball from GitHub. Public repos work
        //    anonymously; private repos use a server-side read-only token.
        //    Credentials are never accepted in the deployment URL.
        $ghToken = (string) config('app.deploy_gh_token', env('DEPLOY_GH_TOKEN', ''));

        $headers = ['User-Agent' => 'educore-self-deploy'];
        if ($ghToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $ghToken;
        }

        // API zipball endpoint honours the Authorization header for private repos.
        $response = Http::withHeaders($headers)
            ->timeout(180)
            ->get('https://api.github.com/repos/' . self::REPO . '/zipball/master');

        if (!$response->successful()) {
            return response()->json([
                'ok'    => false,
                'step'  => 'download',
                'status'=> $response->status(),
                'hint'  => $response->status() === 404
                    ? 'Repository download failed. Configure a server-side read-only GitHub token if the repository is private.'
                    : 'GitHub download failed.',
            ], 200);
        }

        file_put_contents($zipPath, $response->body());

        // 2. Extract. Shared-host ZipArchive::extractTo() has intermittently
        // failed to create nested directories, so extract each entry manually.
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return response()->json(['ok' => false, 'step' => 'unzip'], 500);
        }

        $extractDir = $work . '/tree';
        $this->rrmdir($extractDir);
        if (!$this->ensureDirectory($extractDir)) {
            $zip->close();
            return response()->json([
                'ok' => false,
                'step' => 'prepare-extract-dir',
                'hint' => 'Unable to create the self-deploy extraction directory.',
            ], 500);
        }

        try {
            $this->extractZipSafely($zip, $extractDir);
        } catch (\Throwable $e) {
            $zip->close();
            $this->rrmdir($extractDir);
            return response()->json([
                'ok' => false,
                'step' => 'unzip',
                'hint' => mb_substr($e->getMessage(), 0, 250),
            ], 500);
        }
        $zip->close();

        // Zipball wraps everything in "<repo>-master/"
        $roots = glob($extractDir . '/*', GLOB_ONLYDIR);
        if (!$roots) {
            return response()->json(['ok' => false, 'step' => 'locate-root'], 500);
        }
        $srcRoot = $roots[0];

        // 3. Sync the deployable paths
        $copied = 0;
        foreach (self::SYNC_PATHS as $path) {
            $repoPath = rtrim($path, '/');
            $src = $srcRoot . '/' . $repoPath;
            $dst = $docroot . '/' . $repoPath;

            if (is_dir($src)) {
                $copied += $this->copyTree($src, $dst, $repoPath);
            } elseif (is_file($src) && !in_array($repoPath, self::PRESERVED_LIVE_PATHS, true)) {
                if ($this->ensureDirectory(dirname($dst)) && copy($src, $dst)) {
                    $copied++;
                }
            }
        }

        // Shared-host copies do not prune removed source files. Delete only
        // explicitly retired paths so obsolete public assets cannot linger.
        $removed = 0;
        foreach (self::DEPRECATED_PATHS as $path) {
            $target = $docroot . '/' . $path;
            if (is_file($target) && @unlink($target)) {
                $removed++;
            }
        }

        // 4. Clear every Laravel cache before loading the new route/controller tree.
        // Manually deleting the common cache files is not sufficient when a
        // previous deployment created additional compiled caches (or when a
        // shared host keeps stale route/config bytecode alive). optimize:clear
        // is safe here and ensures /parallel-curriculum executes the version
        // just copied from master.
        foreach (glob(storage_path('framework/views') . '/*.php') ?: [] as $f) @unlink($f);
        foreach (['routes-v7.php', 'config.php', 'events.php', 'services.php', 'packages.php'] as $f) {
            @unlink(base_path('bootstrap/cache/' . $f));
        }

        try {
            Artisan::call('optimize:clear');
        } catch (\Throwable $e) {
            // Cache cleanup must never prevent the database migration from
            // running; the explicit file cleanup above remains the fallback.
        }

        $migrated = 'skipped';
        $migrationOk = true;
        try {
            Artisan::call('migrate', ['--force' => true]);
            $migrated = trim(Artisan::output()) ?: 'nothing to migrate';
        } catch (\Throwable $e) {
            $migrationOk = false;
            $migrated = 'error: ' . $e->getMessage();
        }

        if (! $migrationOk) {
            $opcacheReset = function_exists('opcache_reset') ? opcache_reset() : null;

            @unlink($zipPath);
            $this->rrmdir($extractDir);

            return response()->json([
                'ok' => false,
                'step' => 'migrate',
                'copied' => $copied,
                'removed' => $removed,
                'opcache_reset' => $opcacheReset,
                'migrated' => mb_substr($migrated, 0, 1000),
                'deployed_at' => now()->toDateTimeString(),
                'hint' => 'Code was copied, but a required database migration failed. Resolve the migration error before using the updated application.',
            ], 500);
        }

        // 5. Pull and verify the newest signed Android production release.
        // The last known-good APK remains untouched until the new APK and its
        // independently published checksum have both been validated.
        $apk = $this->syncLatestApk($headers, $docroot);

        // Only announce an Android update after the canonical /download/app
        // asset has been atomically replaced and checksum-verified. This avoids
        // notifying users while the public download URL still serves the
        // previous APK.
        $mobileRelease = $this->recordAndNotifyMobileRelease($apk, $push);

        // opcache_reset() only clears the CURRENT PHP-FPM worker's cache —
        // other workers keep serving stale bytecode until they individually
        // revalidate. The shipped .user.ini (opcache.validate_timestamps=1)
        // is the actual fix; this call is just a best-effort nudge for the
        // worker handling this request.
        $opcacheReset = function_exists('opcache_reset') ? opcache_reset() : null;

        // 6. Tidy up the workspace
        @unlink($zipPath);
        $this->rrmdir($extractDir);

        return response()->json([
            'ok'       => true,
            'copied'   => $copied,
            'removed'  => $removed,
            'apk'      => $apk,
            'mobile_release' => $mobileRelease,
            'opcache_reset' => $opcacheReset,
            'migrated' => mb_substr($migrated, 0, 500),
            'deployed_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Deterministic fallback token derived from APP_KEY, so no .env edit is
     * needed on the server (cPanel editor risks BOM corruption). Retrieve it
     * once via tools/show-deploy-token.php or artisan tinker.
     */
    public static function derivedToken(): string
    {
        return hash_hmac('sha256', 'educore-self-deploy', (string) config('app.key'));
    }

    /**
     * Extract a GitHub zipball one entry at a time. This avoids the shared-host
     * ZipArchive::extractTo() failure where nested parent directories sometimes
     * do not exist when a file entry is written. Entry paths are validated to
     * prevent path traversal before anything is created.
     */
    private function extractZipSafely(\ZipArchive $zip, string $extractDir): void
    {
        $base = rtrim(str_replace('\\', '/', $extractDir), '/');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!is_string($name) || $name === '') {
                continue;
            }

            $name = str_replace('\\', '/', $name);
            $name = ltrim($name, '/');

            if ($name === '' || str_contains($name, "\0")) {
                throw new \RuntimeException('Archive contains an invalid path.');
            }

            $segments = array_values(array_filter(explode('/', $name), static fn ($segment) => $segment !== ''));
            if (in_array('..', $segments, true)) {
                throw new \RuntimeException('Archive contains an unsafe relative path.');
            }

            // GitHub wraps every zipball in one generated root directory.
            // Create that root, then ignore repository paths that never
            // contribute to the live deployment.
            if (count($segments) === 1) {
                if (str_ends_with($name, '/') && !$this->ensureDirectory($base . '/' . $segments[0])) {
                    throw new \RuntimeException('Unable to create archive root directory.');
                }
                continue;
            }

            $repoRelative = implode('/', array_slice($segments, 1));
            if (!$this->shouldExtractRepoPath($repoRelative)) {
                continue;
            }

            $target = $base . '/' . implode('/', $segments);
            if (str_ends_with($name, '/')) {
                if (!$this->ensureDirectory($target)) {
                    throw new \RuntimeException('Unable to create archive directory: ' . $repoRelative);
                }
                continue;
            }

            if (!$this->ensureDirectory(dirname($target))) {
                throw new \RuntimeException('Unable to create parent directory for: ' . $repoRelative);
            }

            $source = $zip->getStream($name);
            if ($source === false) {
                throw new \RuntimeException('Unable to read archive entry: ' . $repoRelative);
            }

            $destination = @fopen($target, 'wb');
            if ($destination === false) {
                fclose($source);
                throw new \RuntimeException(
                    'Unable to create extracted file: ' . $repoRelative
                    . ' (free bytes: ' . (string) (@disk_free_space($extractDir) ?: 'unknown') . ')'
                );
            }

            try {
                if (stream_copy_to_stream($source, $destination) === false) {
                    throw new \RuntimeException('Unable to extract archive entry: ' . $repoRelative);
                }
            } finally {
                fclose($source);
                fclose($destination);
            }
        }
    }

    /**
     * Extract only paths that can contribute to SYNC_PATHS. Parent directories
     * remain eligible so deployable descendants can be created normally.
     */
    private function shouldExtractRepoPath(string $repoPath): bool
    {
        $repoPath = trim(str_replace('\\', '/', $repoPath), '/');
        if ($repoPath === '') {
            return true;
        }

        foreach (self::SYNC_PATHS as $syncPath) {
            $syncPath = trim(str_replace('\\', '/', $syncPath), '/');

            if ($repoPath === $syncPath
                || str_starts_with($repoPath, $syncPath . '/')
                || str_starts_with($syncPath, $repoPath . '/')) {
                return true;
            }
        }

        return false;
    }

    private function ensureDirectory(string $dir): bool
    {
        if (is_dir($dir)) {
            return true;
        }

        if (@mkdir($dir, 0755, true)) {
            return true;
        }

        clearstatcache(true, $dir);
        return is_dir($dir);
    }

    /**
     * Download the latest published Android release's exact APK and checksum,
     * then atomically replace the canonical public download only after both
     * the asset content and checksum are valid.
     */
    private function syncLatestApk(array $headers, string $docroot): array
    {
        try {
            $release = Http::withHeaders($headers)
                ->timeout(60)
                ->get('https://api.github.com/repos/' . self::REPO . '/releases/latest');

            if ($release->status() === 404) {
                return ['status' => 'skipped', 'reason' => 'no-release'];
            }

            if (!$release->successful()) {
                return [
                    'status' => 'skipped',
                    'reason' => 'release-query-failed',
                    'http_status' => $release->status(),
                ];
            }

            $releaseData = $release->json();
            $releaseTag = (string) ($releaseData['tag_name'] ?? '');
            if (($releaseData['draft'] ?? false)
                || ($releaseData['prerelease'] ?? false)
                || !str_starts_with($releaseTag, self::ANDROID_RELEASE_TAG_PREFIX)) {
                return [
                    'status' => 'skipped',
                    'reason' => 'not-an-educore-android-production-release',
                    'release' => $releaseTag ?: null,
                ];
            }

            $assets = is_array($releaseData['assets'] ?? null) ? $releaseData['assets'] : [];
            $apkAsset = collect($assets)->first(
                fn ($candidate) => ($candidate['name'] ?? null) === self::APK_ASSET_NAME
            );
            $checksumAsset = collect($assets)->first(
                fn ($candidate) => ($candidate['name'] ?? null) === self::APK_CHECKSUM_ASSET_NAME
            );

            if (!$apkAsset || empty($apkAsset['url'])) {
                return [
                    'status' => 'skipped',
                    'reason' => 'apk-asset-missing',
                    'release' => $releaseTag,
                ];
            }
            if (!$checksumAsset || empty($checksumAsset['url'])) {
                return [
                    'status' => 'skipped',
                    'reason' => 'checksum-asset-missing',
                    'release' => $releaseTag,
                ];
            }

            $assetHeaders = array_merge($headers, [
                'Accept' => 'application/octet-stream',
            ]);

            $checksumDownload = Http::withHeaders($assetHeaders)
                ->timeout(60)
                ->get($checksumAsset['url']);
            if (!$checksumDownload->successful()) {
                return [
                    'status' => 'skipped',
                    'reason' => 'checksum-download-failed',
                    'http_status' => $checksumDownload->status(),
                    'release' => $releaseTag,
                ];
            }

            $checksumLine = trim($checksumDownload->body());
            // Accept both canonical "sha256  EduCore.apk" checksum files and
            // sha256sum output that includes the build-relative path. Older
            // alpha05 was published in the latter form, so rejecting it left
            // /download/app pinned to the previous APK even though deployment
            // itself completed successfully.
            if (!preg_match(
                '/\A([a-f0-9]{64})[ \t]+\*?(?:.*[\\\\\/])?EduCore\.apk\z/i',
                $checksumLine,
                $matches,
            )) {
                return [
                    'status' => 'skipped',
                    'reason' => 'checksum-format-invalid',
                    'release' => $releaseTag,
                    'checksum_preview' => mb_substr($checksumLine, 0, 160),
                ];
            }
            $expectedSha256 = strtolower($matches[1]);

            $download = Http::withHeaders($assetHeaders)
                ->timeout(180)
                ->get($apkAsset['url']);

            if (!$download->successful()) {
                return [
                    'status' => 'skipped',
                    'reason' => 'apk-download-failed',
                    'http_status' => $download->status(),
                    'release' => $releaseTag,
                ];
            }

            $apkBody = $download->body();
            $apkSize = strlen($apkBody);
            if ($apkSize < self::MINIMUM_APK_BYTES || strncmp($apkBody, "PK\x03\x04", 4) !== 0) {
                return [
                    'status' => 'skipped',
                    'reason' => 'apk-content-invalid',
                    'release' => $releaseTag,
                ];
            }

            $actualSha256 = hash('sha256', $apkBody);
            if (!hash_equals($expectedSha256, $actualSha256)) {
                return [
                    'status' => 'skipped',
                    'reason' => 'apk-checksum-mismatch',
                    'release' => $releaseTag,
                ];
            }

            $target = $docroot . '/educore/public/downloads/' . self::APK_ASSET_NAME;
            @mkdir(dirname($target), 0755, true);

            $tmp = $target . '.tmp-' . bin2hex(random_bytes(6));
            $bytesWritten = file_put_contents($tmp, $apkBody, LOCK_EX);
            unset($apkBody);
            if ($bytesWritten !== $apkSize) {
                @unlink($tmp);
                return ['status' => 'skipped', 'reason' => 'apk-write-failed'];
            }

            if (!hash_equals($expectedSha256, (string) hash_file('sha256', $tmp))) {
                @unlink($tmp);
                return ['status' => 'skipped', 'reason' => 'written-apk-checksum-mismatch'];
            }

            @chmod($tmp, 0644);
            if (!@rename($tmp, $target)) {
                @unlink($tmp);
                return ['status' => 'skipped', 'reason' => 'apk-replace-failed'];
            }

            return [
                'status' => 'updated',
                'release' => $releaseTag,
                'size' => filesize($target) ?: null,
                'sha256' => $actualSha256,
                'checksum_verified' => true,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'skipped',
                'reason' => 'exception',
                'message' => mb_substr($e->getMessage(), 0, 200),
            ];
        }
    }

    /**
     * Record the newly deployed Android release for the native app's polling
     * endpoint and notify the global update topic. This deliberately runs after
     * syncLatestApk() succeeds so a notification can never point at a stale APK.
     */
    private function recordAndNotifyMobileRelease(array $apk, PushNotificationService $push): array
    {
        if (($apk['status'] ?? null) !== 'updated') {
            return [
                'status' => 'skipped',
                'reason' => 'apk-not-updated',
            ];
        }

        $tag = trim((string) ($apk['release'] ?? ''));
        if (!preg_match('/^android-v(.+)-code(\d+)-b\d+-[0-9a-f]{7,40}$/i', $tag, $matches)) {
            return [
                'status' => 'skipped',
                'reason' => 'release-tag-unparseable',
                'release' => $tag ?: null,
            ];
        }

        $versionName = trim($matches[1]);
        $versionCode = (int) $matches[2];
        if ($versionName === '' || $versionCode < 1) {
            return [
                'status' => 'skipped',
                'reason' => 'release-version-invalid',
                'release' => $tag,
            ];
        }

        $releaseFile = 'mobile-releases/android.json';
        $previous = null;
        if (Storage::disk('local')->exists($releaseFile)) {
            $decoded = json_decode((string) Storage::disk('local')->get($releaseFile), true);
            if (is_array($decoded)) {
                $previous = $decoded;
            }
        }

        $pushAlreadySent = (
            ($previous['source_release_tag'] ?? null) === $tag
            && (int) ($previous['version_code'] ?? 0) === $versionCode
            && ! empty($previous['push_sent_at'])
        );

        $downloadUrl = url('/download/app');
        $body = "EduCore {$versionName} is ready to install. Tap to update.";
        $release = [
            'platform' => 'android',
            'version_name' => $versionName,
            'version_code' => $versionCode,
            'minimum_supported_version_code' => 1,
            'download_url' => $downloadUrl,
            'message' => $body,
            'force_update' => false,
            'published_at' => now()->toIso8601String(),
            'source_release_tag' => $tag,
            'sha256' => $apk['sha256'] ?? null,
            'push_sent_at' => $pushAlreadySent ? (string) $previous['push_sent_at'] : null,
        ];

        Storage::disk('local')->put(
            $releaseFile,
            json_encode($release, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        if ($pushAlreadySent) {
            return [
                'status' => 'recorded',
                'release' => $tag,
                'version_name' => $versionName,
                'version_code' => $versionCode,
                'push' => 'skipped-already-notified',
                'push_sent_at' => $release['push_sent_at'],
            ];
        }

        try {
            $delivered = $push->sendToTopic(
                'educore_app_updates',
                'EduCore update available',
                $body,
                [
                    'type' => 'app_update',
                    'version_name' => $versionName,
                    'version_code' => (string) $versionCode,
                    'minimum_supported_version_code' => '1',
                    'download_url' => $downloadUrl,
                    'force_update' => 'false',
                ],
            );

            if ($delivered) {
                $release['push_sent_at'] = now()->toIso8601String();
                Storage::disk('local')->put(
                    $releaseFile,
                    json_encode($release, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                );
            }

            return [
                'status' => 'recorded',
                'release' => $tag,
                'version_name' => $versionName,
                'version_code' => $versionCode,
                'push' => $delivered ? 'sent' : 'failed',
                'push_sent_at' => $release['push_sent_at'],
                'retryable' => ! $delivered,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'recorded',
                'release' => $tag,
                'version_name' => $versionName,
                'version_code' => $versionCode,
                'push' => 'failed',
                'message' => mb_substr($e->getMessage(), 0, 200),
            ];
        }
    }

    private function copyTree(string $src, string $dst, string $repoPath): int
    {
        $count = 0;
        $this->ensureDirectory($dst);

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $item) {
            $subPath = str_replace('\\', '/', $it->getSubPathname());
            $sourcePath = $repoPath . '/' . $subPath;
            if (in_array($sourcePath, self::PRESERVED_LIVE_PATHS, true)) {
                continue;
            }

            $target = $dst . '/' . $it->getSubPathname();
            if ($item->isDir()) {
                $this->ensureDirectory($target);
            } else {
                // Skip files that are byte-identical in size — avoids re-copying
                // large unchanged assets on every deploy.
                if (is_file($target)
                    && filesize($target) === $item->getSize()
                    && hash_file('sha256', $target) === hash_file('sha256', $item->getPathname())) {
                    continue;
                }
                if ($this->ensureDirectory(dirname($target)) && copy($item->getPathname(), $target)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
