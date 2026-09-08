<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

/**
 * Shell-free deployment for shared hosting.
 *
 * cPanel's Git "Deploy HEAD Commit" requires shell access, which this host
 * does not grant, so .cpanel.yml never runs. This controller replaces it:
 * it downloads the GitHub zipball of master over HTTPS, extracts it, and
 * syncs the same paths .cpanel.yml would have rsynced, then clears caches.
 *
 * Preferred trigger: GET /deploy/pull with X-Deploy-Token or Bearer token.
 * Legacy ?token= remains supported so existing shared-host bookmarks keep
 * working, but secrets for GitHub itself are never accepted in the URL.
 */
class SelfDeployController extends Controller
{
    private const REPO = 'abubakarshaaba-ctrl/educore';

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

    /** Files intentionally retired from the live tree after a successful sync. */
    private const DEPRECATED_PATHS = [
        'brand/educore-premium-landing.png',
        'educore/public/brand/educore-premium-landing.png',
        'educore/app/Http/Controllers/ExamBodyRegistrationController.php',
        'educore/app/Models/ExamBodyRegistration.php',
        'educore/resources/views/exam-bodies/index.blade.php',
    ];

    public function pull(Request $request)
    {
        $expected = (string) (config('app.deploy_token') ?: self::derivedToken());
        $presented = (string) (
            $request->header('X-Deploy-Token')
            ?: $request->bearerToken()
            ?: $request->query('token', '')
        );

        if ($expected === '' || $presented === '' || !hash_equals($expected, $presented)) {
            abort(403, 'Invalid deploy token.');
        }

        // Keep running even if the gateway (Cloudflare) times out the request.
        @set_time_limit(0);
        @ignore_user_abort(true);

        $docroot = dirname(base_path()); // public_html
        $work    = storage_path('app/self-deploy');
        $zipPath = $work . '/repo.zip';

        @mkdir($work, 0755, true);

        // Download the master zipball from GitHub. A private-repository token,
        // if ever needed, must come from server configuration only. Query-string
        // credentials are intentionally rejected because URLs are commonly logged.
        $ghToken = (string) config('app.deploy_gh_token', env('DEPLOY_GH_TOKEN', ''));
        $headers = ['User-Agent' => 'educore-self-deploy'];
        if ($ghToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $ghToken;
        }

        $response = Http::withHeaders($headers)
            ->timeout(180)
            ->get('https://api.github.com/repos/' . self::REPO . '/zipball/master');

        if (!$response->successful()) {
            return $this->json([
                'ok'     => false,
                'step'   => 'download',
                'status' => $response->status(),
                'hint'   => $response->status() === 404
                    ? 'Repository download is unavailable. If the repository becomes private, configure DEPLOY_GH_TOKEN on the server.'
                    : 'GitHub download failed.',
            ]);
        }

        file_put_contents($zipPath, $response->body());

        // Extract the trusted GitHub-generated archive.
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return $this->json(['ok' => false, 'step' => 'unzip'], 500);
        }

        $extractDir = $work . '/tree';
        $this->rrmdir($extractDir);
        @mkdir($extractDir, 0755, true);
        $zip->extractTo($extractDir);
        $zip->close();

        // Zipball wraps everything in "<repo>-master/".
        $roots = glob($extractDir . '/*', GLOB_ONLYDIR);
        if (!$roots) {
            return $this->json(['ok' => false, 'step' => 'locate-root'], 500);
        }
        $srcRoot = $roots[0];

        // Sync the deployable paths.
        $copied = 0;
        foreach (self::SYNC_PATHS as $path) {
            $src = $srcRoot . '/' . rtrim($path, '/');
            $dst = $docroot . '/' . rtrim($path, '/');

            if (is_dir($src)) {
                $copied += $this->copyTree($src, $dst);
            } elseif (is_file($src)) {
                @mkdir(dirname($dst), 0755, true);
                copy($src, $dst) && $copied++;
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

        // Clear caches + run migrations.
        foreach (glob(storage_path('framework/views') . '/*.php') ?: [] as $f) @unlink($f);
        foreach (['routes-v7.php', 'config.php', 'events.php'] as $f) @unlink(base_path('bootstrap/cache/' . $f));

        $migrated = 'skipped';
        $migrationOk = true;
        try {
            Artisan::call('migrate', ['--force' => true]);
            $migrated = trim(Artisan::output()) ?: 'nothing to migrate';
        } catch (\Throwable $e) {
            $migrationOk = false;
            $migrated = 'error: ' . $e->getMessage();
        }

        $opcacheReset = function_exists('opcache_reset') ? opcache_reset() : null;

        // Tidy up the workspace even when migrations report a failure.
        @unlink($zipPath);
        $this->rrmdir($extractDir);

        return $this->json([
            'ok'       => $migrationOk,
            'step'     => $migrationOk ? 'complete' : 'migrate',
            'copied'   => $copied,
            'removed'  => $removed,
            'opcache_reset' => $opcacheReset,
            'migrated' => mb_substr($migrated, 0, 500),
            'deployed_at' => now()->toDateTimeString(),
        ], $migrationOk ? 200 : 500);
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

    private function json(array $payload, int $status = 200)
    {
        return response()->json($payload, $status, [
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function copyTree(string $src, string $dst): int
    {
        $count = 0;
        @mkdir($dst, 0755, true);

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $item) {
            $target = $dst . '/' . $it->getSubPathname();
            if ($item->isDir()) {
                @mkdir($target, 0755, true);
            } else {
                if (is_file($target)
                    && filesize($target) === $item->getSize()
                    && hash_file('sha256', $target) === hash_file('sha256', $item->getPathname())) {
                    continue;
                }
                @mkdir(dirname($target), 0755, true);
                copy($item->getPathname(), $target) && $count++;
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
