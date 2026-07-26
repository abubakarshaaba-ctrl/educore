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
 * Trigger: GET /deploy/pull?token=<DEPLOY_TOKEN from .env>
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
        '.htaccess',
        'index.php',
        '.user.ini',
    ];

    public function pull(Request $request)
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

        // 1. Download the master zipball from GitHub.
        //    Public repos work anonymously; private repos need a read-only
        //    token, supplied as ?gh=<token> or config('app.deploy_gh_token').
        $ghToken = (string) ($request->query('gh') ?: config('app.deploy_gh_token', env('DEPLOY_GH_TOKEN', '')));

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
                    ? 'Repo is private — append &gh=<github read token> to the deploy URL, or make the repo public.'
                    : 'GitHub download failed.',
            ], 200);
        }

        file_put_contents($zipPath, $response->body());

        // 2. Extract
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return response()->json(['ok' => false, 'step' => 'unzip'], 500);
        }

        $extractDir = $work . '/tree';
        $this->rrmdir($extractDir);
        @mkdir($extractDir, 0755, true);
        $zip->extractTo($extractDir);
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
            $src = $srcRoot . '/' . rtrim($path, '/');
            $dst = $docroot . '/' . rtrim($path, '/');

            if (is_dir($src)) {
                $copied += $this->copyTree($src, $dst);
            } elseif (is_file($src)) {
                @mkdir(dirname($dst), 0755, true);
                copy($src, $dst) && $copied++;
            }
        }

        // 4. Clear caches + run migrations
        foreach (glob(storage_path('framework/views') . '/*.php') ?: [] as $f) @unlink($f);
        foreach (['routes-v7.php', 'config.php', 'events.php'] as $f) @unlink(base_path('bootstrap/cache/' . $f));

        $migrated = 'skipped';
        try {
            Artisan::call('migrate', ['--force' => true]);
            $migrated = trim(Artisan::output()) ?: 'nothing to migrate';
        } catch (\Throwable $e) {
            $migrated = 'error: ' . $e->getMessage();
        }

        // opcache_reset() only clears the CURRENT PHP-FPM worker's cache —
        // other workers keep serving stale bytecode until they individually
        // revalidate. The shipped .user.ini (opcache.validate_timestamps=1)
        // is the actual fix; this call is just a best-effort nudge for the
        // worker handling this request.
        $opcacheReset = function_exists('opcache_reset') ? opcache_reset() : null;

        // 5. Tidy up the workspace
        @unlink($zipPath);
        $this->rrmdir($extractDir);

        return response()->json([
            'ok'       => true,
            'copied'   => $copied,
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
                // Skip files that are byte-identical in size — avoids re-copying
                // large unchanged binaries (e.g. the APK) on every deploy.
                if (is_file($target) && filesize($target) === $item->getSize()) {
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
