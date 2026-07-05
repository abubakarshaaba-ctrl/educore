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
    ];

    public function pull(Request $request)
    {
        $expected = (string) config('app.deploy_token', env('DEPLOY_TOKEN', ''));

        if ($expected === '' || !hash_equals($expected, (string) $request->query('token'))) {
            abort(403, 'Invalid deploy token.');
        }

        @set_time_limit(300);

        $docroot = dirname(base_path()); // public_html
        $work    = storage_path('app/self-deploy');
        $zipPath = $work . '/repo.zip';

        @mkdir($work, 0755, true);

        // 1. Download the master zipball from GitHub (public repo)
        $response = Http::withHeaders(['User-Agent' => 'educore-self-deploy'])
            ->timeout(180)
            ->get('https://codeload.github.com/' . self::REPO . '/zip/refs/heads/master');

        if (!$response->successful()) {
            return response()->json(['ok' => false, 'step' => 'download', 'status' => $response->status()], 502);
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

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        // 5. Tidy up the workspace
        @unlink($zipPath);
        $this->rrmdir($extractDir);

        return response()->json([
            'ok'       => true,
            'copied'   => $copied,
            'migrated' => mb_substr($migrated, 0, 500),
            'deployed_at' => now()->toDateTimeString(),
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
