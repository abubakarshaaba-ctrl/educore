<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Commit selected live-server files back to GitHub without the git CLI or
 * GitHub Actions.
 *
 * This endpoint is deliberately narrow: callers must authenticate with the
 * EduCore deploy token, GitHub credentials stay server-side, only allow-listed
 * repository paths can be selected, and .env / storage / user uploads / build
 * artefacts are never eligible for commit.
 *
 * POST /api/deploy/commit
 * {
 *   "token": "<DEPLOY_TOKEN or derived token>",
 *   "message": "Sync production hotfix",
 *   "paths": ["educore/app/Http/Controllers/FooController.php"],
 *   "dry_run": false,
 *   "prune": false
 * }
 */
class GitHubCommitController extends Controller
{
    private const DEFAULT_REPOSITORY = 'abubakarshaaba-ctrl/educore';
    private const DEFAULT_BRANCH = 'master';

    private const MAX_SELECTED_PATHS = 25;
    private const MAX_FILE_BYTES = 20 * 1024 * 1024; // 20 MiB
    private const MAX_TOTAL_BYTES = 60 * 1024 * 1024; // 60 MiB

    /**
     * Paths relative to the repository root which may be committed.
     * Keep this aligned with the deployable source tree, not runtime data.
     */
    private const ALLOWED_PATHS = [
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

    /** Runtime, secret, generated, user-content, and credential paths. */
    private const BLOCKED_SEGMENTS = [
        '/.env',
        '/.git/',
        '/storage/',
        '/vendor/',
        '/node_modules/',
        '/.gradle/',
        '/build/',
        '/.idea/',
        '/public/storage/',
        '/public/uploads/',
        '/public/downloads/',
    ];

    private const BLOCKED_BASENAMES = [
        '.env',
        'local.properties',
        'google-services.json',
    ];

    private const BLOCKED_EXTENSIONS = [
        'jks', 'keystore', 'p12', 'pfx', 'pem', 'key',
    ];

    public function commit(Request $request)
    {
        $this->assertDeployToken($request);

        $writeToken = trim((string) config('app.github_write_token'));
        if ($writeToken === '') {
            return response()->json([
                'ok' => false,
                'step' => 'configuration',
                'message' => 'GITHUB_WRITE_TOKEN is not configured on this server.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:200'],
            'paths' => ['required', 'array', 'min:1', 'max:' . self::MAX_SELECTED_PATHS],
            'paths.*' => ['required', 'string', 'max:500'],
            'dry_run' => ['sometimes', 'boolean'],
            'prune' => ['sometimes', 'boolean'],
        ]);

        $repository = trim((string) config('app.github_repository', self::DEFAULT_REPOSITORY));
        $branch = trim((string) config('app.github_branch', self::DEFAULT_BRANCH));
        $message = trim((string) ($validated['message'] ?? 'Sync EduCore server changes'));
        $dryRun = (bool) ($validated['dry_run'] ?? false);
        $prune = (bool) ($validated['prune'] ?? false);

        if (!preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repository)) {
            return response()->json([
                'ok' => false,
                'step' => 'configuration',
                'message' => 'Invalid GITHUB_REPOSITORY configuration.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if ($branch === '' || str_contains($branch, '..') || str_starts_with($branch, '/')) {
            return response()->json([
                'ok' => false,
                'step' => 'configuration',
                'message' => 'Invalid GITHUB_BRANCH configuration.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $selected = [];
        foreach ($validated['paths'] as $path) {
            $normalized = $this->normalizeRepoPath($path);
            if (!$this->isAllowedPath($normalized)) {
                return response()->json([
                    'ok' => false,
                    'step' => 'validation',
                    'message' => "Path is not eligible for repository sync: {$path}",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $selected[$normalized] = $normalized;
        }
        $selected = array_values($selected);

        $docroot = dirname(base_path()); // repository root as deployed in public_html
        [$localFiles, $totalBytes] = $this->collectLocalFiles($docroot, $selected);

        $github = $this->github($writeToken);

        try {
            $ref = $this->githubJson(
                $github->get("https://api.github.com/repos/{$repository}/git/ref/heads/" . $this->encodeBranch($branch)),
                'read-ref'
            );

            $headSha = data_get($ref, 'object.sha');
            if (!is_string($headSha) || $headSha === '') {
                throw new \RuntimeException('GitHub branch reference did not contain a commit SHA.');
            }

            $headCommit = $this->githubJson(
                $github->get("https://api.github.com/repos/{$repository}/git/commits/{$headSha}"),
                'read-commit'
            );
            $baseTreeSha = data_get($headCommit, 'tree.sha');
            if (!is_string($baseTreeSha) || $baseTreeSha === '') {
                throw new \RuntimeException('GitHub commit did not contain a tree SHA.');
            }

            $remoteTreePayload = $this->githubJson(
                $github->get("https://api.github.com/repos/{$repository}/git/trees/{$baseTreeSha}", [
                    'recursive' => 1,
                ]),
                'read-tree'
            );

            if ((bool) ($remoteTreePayload['truncated'] ?? false)) {
                return response()->json([
                    'ok' => false,
                    'step' => 'read-tree',
                    'message' => 'GitHub returned a truncated repository tree; refusing an incomplete sync.',
                ], Response::HTTP_CONFLICT);
            }

            $remote = [];
            foreach (($remoteTreePayload['tree'] ?? []) as $entry) {
                if (($entry['type'] ?? null) !== 'blob') {
                    continue;
                }
                $remotePath = $this->normalizeRepoPath((string) ($entry['path'] ?? ''));
                if ($remotePath === '' || $this->isBlockedPath($remotePath)) {
                    continue;
                }
                $remote[$remotePath] = [
                    'sha' => (string) ($entry['sha'] ?? ''),
                    'mode' => (string) ($entry['mode'] ?? '100644'),
                ];
            }

            $changed = [];
            $unchanged = [];
            foreach ($localFiles as $repoPath => $localPath) {
                $contents = file_get_contents($localPath);
                if ($contents === false) {
                    throw new \RuntimeException("Unable to read local file: {$repoPath}");
                }

                $localGitSha = sha1('blob ' . strlen($contents) . "\0" . $contents);
                if (($remote[$repoPath]['sha'] ?? null) === $localGitSha) {
                    $unchanged[] = $repoPath;
                    continue;
                }

                $changed[$repoPath] = [
                    'local_path' => $localPath,
                    'bytes' => strlen($contents),
                    'mode' => is_executable($localPath) ? '100755' : '100644',
                ];
            }

            $deleted = [];
            if ($prune) {
                foreach ($remote as $repoPath => $entry) {
                    if (!$this->matchesSelection($repoPath, $selected)) {
                        continue;
                    }
                    if (!array_key_exists($repoPath, $localFiles)) {
                        $deleted[$repoPath] = $entry;
                    }
                }
            }

            if ($dryRun) {
                return response()->json([
                    'ok' => true,
                    'dry_run' => true,
                    'repository' => $repository,
                    'branch' => $branch,
                    'head' => $headSha,
                    'selected_paths' => $selected,
                    'changed' => array_keys($changed),
                    'deleted' => array_keys($deleted),
                    'unchanged_count' => count($unchanged),
                    'local_bytes_scanned' => $totalBytes,
                ]);
            }

            if ($changed === [] && $deleted === []) {
                return response()->json([
                    'ok' => true,
                    'committed' => false,
                    'message' => 'No repository changes detected in the selected paths.',
                    'repository' => $repository,
                    'branch' => $branch,
                    'head' => $headSha,
                    'unchanged_count' => count($unchanged),
                ]);
            }

            $treeEntries = [];
            foreach ($changed as $repoPath => $meta) {
                $contents = file_get_contents($meta['local_path']);
                if ($contents === false) {
                    throw new \RuntimeException("Unable to read local file: {$repoPath}");
                }

                $blob = $this->githubJson(
                    $github->post("https://api.github.com/repos/{$repository}/git/blobs", [
                        'content' => base64_encode($contents),
                        'encoding' => 'base64',
                    ]),
                    "create-blob:{$repoPath}"
                );

                $blobSha = $blob['sha'] ?? null;
                if (!is_string($blobSha) || $blobSha === '') {
                    throw new \RuntimeException("GitHub did not return a blob SHA for {$repoPath}.");
                }

                $treeEntries[] = [
                    'path' => $repoPath,
                    'mode' => $meta['mode'],
                    'type' => 'blob',
                    'sha' => $blobSha,
                ];
            }

            foreach ($deleted as $repoPath => $entry) {
                $treeEntries[] = [
                    'path' => $repoPath,
                    'mode' => $entry['mode'] ?: '100644',
                    'type' => 'blob',
                    'sha' => null,
                ];
            }

            $newTree = $this->githubJson(
                $github->post("https://api.github.com/repos/{$repository}/git/trees", [
                    'base_tree' => $baseTreeSha,
                    'tree' => $treeEntries,
                ]),
                'create-tree'
            );
            $newTreeSha = $newTree['sha'] ?? null;
            if (!is_string($newTreeSha) || $newTreeSha === '') {
                throw new \RuntimeException('GitHub did not return the new tree SHA.');
            }

            $newCommit = $this->githubJson(
                $github->post("https://api.github.com/repos/{$repository}/git/commits", [
                    'message' => $message !== '' ? $message : 'Sync EduCore server changes',
                    'tree' => $newTreeSha,
                    'parents' => [$headSha],
                ]),
                'create-commit'
            );
            $newCommitSha = $newCommit['sha'] ?? null;
            if (!is_string($newCommitSha) || $newCommitSha === '') {
                throw new \RuntimeException('GitHub did not return the new commit SHA.');
            }

            // force=false protects newer GitHub work. A concurrent change causes
            // a conflict rather than silently overwriting another commit.
            $this->githubJson(
                $github->patch("https://api.github.com/repos/{$repository}/git/refs/heads/" . $this->encodeBranch($branch), [
                    'sha' => $newCommitSha,
                    'force' => false,
                ]),
                'update-ref'
            );

            Log::notice('EduCore server changes committed to GitHub', [
                'repository' => $repository,
                'branch' => $branch,
                'commit' => $newCommitSha,
                'changed_count' => count($changed),
                'deleted_count' => count($deleted),
            ]);

            return response()->json([
                'ok' => true,
                'committed' => true,
                'repository' => $repository,
                'branch' => $branch,
                'previous_head' => $headSha,
                'commit' => $newCommitSha,
                'commit_url' => "https://github.com/{$repository}/commit/{$newCommitSha}",
                'changed' => array_keys($changed),
                'deleted' => array_keys($deleted),
                'unchanged_count' => count($unchanged),
                'local_bytes_scanned' => $totalBytes,
            ]);
        } catch (\Throwable $e) {
            Log::error('EduCore GitHub commit controller failed', [
                'repository' => $repository,
                'branch' => $branch,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'step' => 'github-sync',
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    private function assertDeployToken(Request $request): void
    {
        $expected = (string) (config('app.deploy_token') ?: SelfDeployController::derivedToken());
        $provided = (string) (
            $request->header('X-Deploy-Token')
            ?: $request->input('token')
            ?: $request->query('token')
        );

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            abort(Response::HTTP_FORBIDDEN, 'Invalid deploy token.');
        }
    }

    private function github(string $token): PendingRequest
    {
        return Http::withToken($token)
            ->acceptJson()
            ->withHeaders([
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'educore-server-sync',
            ])
            ->timeout(180)
            ->retry(2, 500, throw: false);
    }

    /** @return array<string,mixed> */
    private function githubJson($response, string $step): array
    {
        if (!$response->successful()) {
            $message = (string) data_get($response->json(), 'message', 'GitHub API request failed.');
            throw new \RuntimeException("{$step} failed ({$response->status()}): {$message}");
        }

        $json = $response->json();
        if (!is_array($json)) {
            throw new \RuntimeException("{$step} returned an invalid GitHub response.");
        }

        return $json;
    }

    /**
     * @param  array<int,string>  $selected
     * @return array{0: array<string,string>, 1: int}
     */
    private function collectLocalFiles(string $docroot, array $selected): array
    {
        $files = [];
        $totalBytes = 0;

        foreach ($selected as $repoPath) {
            $absolute = $docroot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $repoPath);

            if (is_file($absolute)) {
                $this->addLocalFile($files, $totalBytes, $repoPath, $absolute);
                continue;
            }

            if (!is_dir($absolute)) {
                // Missing paths matter only when prune=true, which is evaluated
                // against the remote tree later.
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $item) {
                if (!$item->isFile() || $item->isLink()) {
                    continue;
                }

                $relative = ltrim(str_replace('\\', '/', Str::after($item->getPathname(), $docroot)), '/');
                $relative = $this->normalizeRepoPath($relative);

                if (!$this->isAllowedPath($relative) || $this->isBlockedPath($relative)) {
                    continue;
                }

                $this->addLocalFile($files, $totalBytes, $relative, $item->getPathname());
            }
        }

        ksort($files);

        return [$files, $totalBytes];
    }

    /** @param array<string,string> $files */
    private function addLocalFile(array &$files, int &$totalBytes, string $repoPath, string $absolute): void
    {
        if ($this->isBlockedPath($repoPath)) {
            return;
        }

        $size = filesize($absolute);
        if ($size === false) {
            throw new \RuntimeException("Unable to determine file size: {$repoPath}");
        }
        if ($size > self::MAX_FILE_BYTES) {
            throw new \RuntimeException("File exceeds the 20 MiB repository-sync limit: {$repoPath}");
        }

        $totalBytes += $size;
        if ($totalBytes > self::MAX_TOTAL_BYTES) {
            throw new \RuntimeException('Selected files exceed the 60 MiB repository-sync limit. Commit smaller groups.');
        }

        $files[$repoPath] = $absolute;
    }

    private function normalizeRepoPath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $path = preg_replace('#/+#', '/', $path) ?? $path;
        $path = ltrim($path, '/');

        if ($path === '' || $path === '.' || str_contains($path, "\0")) {
            return '';
        }

        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '..' || $segment === '') {
                return '';
            }
        }

        return $path;
    }

    private function isAllowedPath(string $path): bool
    {
        if ($path === '' || $this->isBlockedPath($path)) {
            return false;
        }

        foreach (self::ALLOWED_PATHS as $allowed) {
            $isDirectoryRule = str_ends_with($allowed, '/');
            $root = rtrim($allowed, '/');

            if ($path === $root || ($isDirectoryRule && str_starts_with($path, $root . '/'))) {
                return true;
            }
        }

        return false;
    }

    private function isBlockedPath(string $path): bool
    {
        $normalized = '/' . strtolower(trim(str_replace('\\', '/', $path), '/'));
        $basename = strtolower(basename($normalized));
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

        if (in_array($basename, self::BLOCKED_BASENAMES, true)) {
            return true;
        }
        if ($extension !== '' && in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            return true;
        }

        $haystack = $normalized . (str_ends_with($normalized, '/') ? '' : '/');
        foreach (self::BLOCKED_SEGMENTS as $blocked) {
            if (str_contains($haystack, strtolower($blocked))) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int,string> $selected */
    private function matchesSelection(string $repoPath, array $selected): bool
    {
        foreach ($selected as $selection) {
            $root = rtrim($selection, '/');
            if ($repoPath === $root || str_starts_with($repoPath, $root . '/')) {
                return !$this->isBlockedPath($repoPath);
            }
        }

        return false;
    }

    private function encodeBranch(string $branch): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $branch)));
    }
}
