<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Shell-free repository writer for EduCore shared hosting.
 *
 * This controller does not invoke git, a shell, cPanel Git deployment, or
 * GitHub Actions. It writes directly through GitHub's Git Database API:
 *
 *   ref -> parent commit -> blobs -> tree -> commit -> fast-forward ref
 *
 * That produces one atomic multi-file commit and advances the selected branch
 * only after every blob/tree/commit operation has succeeded.
 *
 * Security rules:
 * - incoming authorization is the EduCore deploy token in a request header;
 * - GitHub credentials are server-side only (GITHUB_WRITE_TOKEN);
 * - branches are allow-listed;
 * - writable repository paths are allow-listed;
 * - .env, secrets and arbitrary filesystem paths can never be submitted;
 * - ref updates are non-forced, so concurrent branch changes fail safely.
 */
class SelfGitCommitController extends Controller
{
    private const REPO = 'abubakarshaaba-ctrl/educore';
    private const DEFAULT_BRANCH = 'mobile-overhaul';
    private const MAX_CHANGES = 50;
    private const MAX_TOTAL_BYTES = 2_000_000;

    private const ALLOWED_PATH_PREFIXES = [
        'mobile-native/',
        'educore/',
        'docs/',
        'brand/',
        '.github/workflows/',
    ];

    private const ALLOWED_ROOT_FILES = [
        '.cpanel.yml',
        '.htaccess',
        '.user.ini',
        'index.php',
    ];

    /**
     * Return the current branch head. Call this before commit and send the
     * returned head_sha back as expected_head_sha for optimistic locking.
     */
    public function status(Request $request)
    {
        $this->authorise($request);
        $branch = $this->approvedBranch((string) $request->query('branch', self::DEFAULT_BRANCH));

        $ref = $this->github('GET', '/repos/'.self::REPO.'/git/ref/heads/'.$this->encodeRef($branch));
        $this->assertGithub($ref, 'read branch ref');

        $headSha = (string) $ref->json('object.sha');
        $commit = $this->github('GET', '/repos/'.self::REPO.'/git/commits/'.$headSha);
        $this->assertGithub($commit, 'read branch commit');

        return response()->json([
            'ok' => true,
            'repository' => self::REPO,
            'branch' => $branch,
            'head_sha' => $headSha,
            'tree_sha' => (string) $commit->json('tree.sha'),
            'message' => (string) $commit->json('message'),
            'committed_at' => $commit->json('committer.date'),
            'actions_required' => false,
        ]);
    }

    /**
     * Create one atomic commit from UTF-8 text changes and fast-forward branch.
     *
     * Payload:
     * {
     *   "branch": "mobile-overhaul",
     *   "expected_head_sha": "<40-char SHA>",
     *   "message": "Describe the change",
     *   "changes": [
     *      {"path":"educore/routes/web.php","content":"..."},
     *      {"path":"docs/old.md","delete":true}
     *   ]
     * }
     */
    public function commit(Request $request)
    {
        $this->authorise($request);

        $data = $request->validate([
            'branch' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._\/-]+$/'],
            'expected_head_sha' => ['required', 'string', 'regex:/^[a-fA-F0-9]{40}$/'],
            'message' => ['required', 'string', 'min:3', 'max:240'],
            'changes' => ['required', 'array', 'min:1', 'max:'.self::MAX_CHANGES],
            'changes.*.path' => ['required', 'string', 'max:500'],
            'changes.*.content' => ['nullable', 'string'],
            'changes.*.delete' => ['nullable', 'boolean'],
            'changes.*.mode' => ['nullable', 'in:100644,100755'],
        ]);

        $branch = $this->approvedBranch((string) ($data['branch'] ?? self::DEFAULT_BRANCH));
        $message = trim((string) $data['message']);
        $expectedHead = strtolower((string) $data['expected_head_sha']);
        $changes = $this->normaliseChanges($data['changes']);

        $ref = $this->github('GET', '/repos/'.self::REPO.'/git/ref/heads/'.$this->encodeRef($branch));
        $this->assertGithub($ref, 'read branch ref');
        $headSha = strtolower((string) $ref->json('object.sha'));

        if (! hash_equals($expectedHead, $headSha)) {
            return response()->json([
                'ok' => false,
                'error' => 'branch_head_changed',
                'message' => 'The branch changed since it was inspected. Refresh repository status and retry.',
                'expected_head_sha' => $expectedHead,
                'current_head_sha' => $headSha,
            ], 409);
        }

        $parent = $this->github('GET', '/repos/'.self::REPO.'/git/commits/'.$headSha);
        $this->assertGithub($parent, 'read parent commit');
        $baseTreeSha = (string) $parent->json('tree.sha');

        // Preserve executable modes on existing files when the caller does not
        // explicitly provide a mode. A truncated recursive tree simply falls
        // back to 100644 for previously unseen paths.
        $existingModes = $this->existingBlobModes($baseTreeSha);
        $treeEntries = [];

        foreach ($changes as $change) {
            $path = $change['path'];
            if ($change['delete']) {
                $treeEntries[] = [
                    'path' => $path,
                    'mode' => $change['mode'] ?? ($existingModes[$path] ?? '100644'),
                    'type' => 'blob',
                    'sha' => null,
                ];
                continue;
            }

            $blob = $this->github('POST', '/repos/'.self::REPO.'/git/blobs', [
                'content' => base64_encode($change['content']),
                'encoding' => 'base64',
            ]);
            $this->assertGithub($blob, 'create blob for '.$path);

            $treeEntries[] = [
                'path' => $path,
                'mode' => $change['mode'] ?? ($existingModes[$path] ?? '100644'),
                'type' => 'blob',
                'sha' => (string) $blob->json('sha'),
            ];
        }

        $tree = $this->github('POST', '/repos/'.self::REPO.'/git/trees', [
            'base_tree' => $baseTreeSha,
            'tree' => $treeEntries,
        ]);
        $this->assertGithub($tree, 'create commit tree');
        $newTreeSha = (string) $tree->json('sha');

        if ($newTreeSha !== '' && hash_equals($baseTreeSha, $newTreeSha)) {
            return response()->json([
                'ok' => true,
                'noop' => true,
                'repository' => self::REPO,
                'branch' => $branch,
                'head_sha' => $headSha,
                'message' => 'Submitted files are identical to the current branch tree; no commit was created.',
            ]);
        }

        $commit = $this->github('POST', '/repos/'.self::REPO.'/git/commits', [
            'message' => $message,
            'tree' => $newTreeSha,
            'parents' => [$headSha],
        ]);
        $this->assertGithub($commit, 'create commit');
        $newCommitSha = strtolower((string) $commit->json('sha'));

        $update = $this->github('PATCH', '/repos/'.self::REPO.'/git/refs/heads/'.$this->encodeRef($branch), [
            'sha' => $newCommitSha,
            'force' => false,
        ]);

        // GitHub rejects a non-fast-forward ref update if another writer moved
        // the branch after our initial head check. Never force through it.
        if ($update->status() === 409 || $update->status() === 422) {
            return response()->json([
                'ok' => false,
                'error' => 'concurrent_branch_update',
                'message' => 'The commit was created but the branch advanced concurrently, so the ref was not forced. Refresh status and retry.',
                'orphan_commit_sha' => $newCommitSha,
                'previous_head_sha' => $headSha,
            ], 409);
        }
        $this->assertGithub($update, 'advance branch ref');

        Log::notice('Shell-free GitHub commit completed.', [
            'repository' => self::REPO,
            'branch' => $branch,
            'previous_head' => $headSha,
            'commit_sha' => $newCommitSha,
            'paths' => array_column($changes, 'path'),
            'request_ip' => $request->ip(),
        ]);

        return response()->json([
            'ok' => true,
            'noop' => false,
            'repository' => self::REPO,
            'branch' => $branch,
            'previous_head_sha' => $headSha,
            'commit_sha' => $newCommitSha,
            'tree_sha' => $newTreeSha,
            'changed_paths' => array_column($changes, 'path'),
            'actions_required' => false,
            'committed_at' => now()->toIso8601String(),
        ], 201);
    }

    private function authorise(Request $request): void
    {
        $expected = (string) (config('app.deploy_token') ?: SelfDeployController::derivedToken());
        $supplied = (string) ($request->header('X-EduCore-Deploy-Token') ?: $request->bearerToken());

        if ($expected === '' || $supplied === '' || ! hash_equals($expected, $supplied)) {
            abort(403, 'Invalid deploy token.');
        }

        if ($request->has('gh') || $request->has('github_token') || $request->hasHeader('X-GitHub-Token')) {
            abort(400, 'GitHub credentials are server-side only and must never be supplied in the request.');
        }
    }

    private function approvedBranch(string $branch): string
    {
        $branch = trim($branch);
        if ($branch === '' || ! preg_match('/^[A-Za-z0-9._\/-]+$/', $branch) || str_contains($branch, '..')) {
            throw new HttpException(422, 'Invalid repository branch.');
        }

        $configured = config('app.github_commit_branches', [self::DEFAULT_BRANCH]);
        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }
        $allowed = array_values(array_unique(array_filter(array_map(
            fn ($value) => trim((string) $value),
            is_array($configured) ? $configured : [self::DEFAULT_BRANCH]
        ))));

        if ((bool) config('app.github_commit_allow_master', false)) {
            $allowed[] = 'master';
        }

        if (! in_array($branch, $allowed, true)) {
            throw new HttpException(422, 'This branch is not approved for shell-free commits.');
        }

        return $branch;
    }

    /** @return array<int,array{path:string,content:string,delete:bool,mode:?string}> */
    private function normaliseChanges(array $changes): array
    {
        $seen = [];
        $normalised = [];
        $totalBytes = 0;

        foreach ($changes as $change) {
            $path = $this->approvedPath((string) ($change['path'] ?? ''));
            if (isset($seen[$path])) {
                throw new HttpException(422, 'Duplicate repository path in one commit: '.$path);
            }
            $seen[$path] = true;

            $delete = (bool) ($change['delete'] ?? false);
            $hasContent = array_key_exists('content', $change) && $change['content'] !== null;
            if ($delete && $hasContent) {
                throw new HttpException(422, 'A deleted file must not also contain replacement content: '.$path);
            }
            if (! $delete && ! $hasContent) {
                throw new HttpException(422, 'Replacement content is required for '.$path);
            }

            $content = $delete ? '' : (string) $change['content'];
            $totalBytes += strlen($content);
            if ($totalBytes > self::MAX_TOTAL_BYTES) {
                throw new HttpException(413, 'Commit payload exceeds the 2 MB shell-free write limit.');
            }

            $normalised[] = [
                'path' => $path,
                'content' => $content,
                'delete' => $delete,
                'mode' => isset($change['mode']) ? (string) $change['mode'] : null,
            ];
        }

        return $normalised;
    }

    private function approvedPath(string $path): string
    {
        $path = trim($path);
        if ($path === ''
            || str_starts_with($path, '/')
            || str_contains($path, '\\')
            || str_contains($path, "\0")
            || preg_match('#(^|/)\.\.?(/|$)#', $path)
        ) {
            throw new HttpException(422, 'Invalid repository path.');
        }

        // Explicitly prevent credentials/runtime state from ever entering Git.
        $lower = strtolower($path);
        if ($lower === '.env' || str_ends_with($lower, '/.env') || str_contains($lower, '/storage/')) {
            throw new HttpException(422, 'Runtime configuration and storage files cannot be committed.');
        }

        $allowed = in_array($path, self::ALLOWED_ROOT_FILES, true);
        foreach (self::ALLOWED_PATH_PREFIXES as $prefix) {
            $allowed = $allowed || str_starts_with($path, $prefix);
        }

        if (! $allowed) {
            throw new HttpException(422, 'Repository path is outside the shell-free commit allow-list: '.$path);
        }

        return $path;
    }

    /** @return array<string,string> */
    private function existingBlobModes(string $baseTreeSha): array
    {
        $response = $this->github('GET', '/repos/'.self::REPO.'/git/trees/'.$baseTreeSha.'?recursive=1');
        if (! $response->successful()) {
            return [];
        }

        $modes = [];
        foreach ((array) $response->json('tree', []) as $entry) {
            if (($entry['type'] ?? null) !== 'blob') {
                continue;
            }
            $path = (string) ($entry['path'] ?? '');
            $mode = (string) ($entry['mode'] ?? '');
            if ($path !== '' && in_array($mode, ['100644', '100755'], true)) {
                $modes[$path] = $mode;
            }
        }

        return $modes;
    }

    private function github(string $method, string $path, array $payload = []): HttpResponse
    {
        $token = (string) config('app.github_write_token', '');
        if ($token === '') {
            throw new HttpException(503, 'GITHUB_WRITE_TOKEN is not configured on the server.');
        }

        $client = Http::withToken($token)
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => 'educore-shell-free-git-writer',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->timeout(45)
            ->retry(2, 250, throw: false);

        $url = 'https://api.github.com'.$path;

        return match (strtoupper($method)) {
            'GET' => $client->get($url),
            'POST' => $client->post($url, $payload),
            'PATCH' => $client->patch($url, $payload),
            default => throw new \InvalidArgumentException('Unsupported GitHub method.'),
        };
    }

    private function assertGithub(HttpResponse $response, string $step): void
    {
        if ($response->successful()) {
            return;
        }

        $message = trim((string) $response->json('message', 'GitHub API request failed.'));
        throw new HttpException(
            502,
            ucfirst($step).' failed (GitHub HTTP '.$response->status().'): '.mb_substr($message, 0, 220)
        );
    }

    private function encodeRef(string $branch): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $branch)));
    }
}
