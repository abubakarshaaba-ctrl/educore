<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SelfGitCommitControllerTest extends TestCase
{
    private const DEPLOY_TOKEN = 'test-deploy-token';
    private const GITHUB_TOKEN = 'github-write-token';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.deploy_token', self::DEPLOY_TOKEN);
        config()->set('app.github_write_token', self::GITHUB_TOKEN);
        config()->set('app.github_commit_branches', ['mobile-overhaul']);
        config()->set('app.github_commit_allow_master', false);
    }

    public function test_repository_writer_requires_header_authentication(): void
    {
        $this->getJson('/deploy/repository/status?branch=mobile-overhaul')
            ->assertForbidden();
    }

    public function test_repository_writer_rejects_unapproved_branches_and_secret_paths(): void
    {
        $headers = ['X-EduCore-Deploy-Token' => self::DEPLOY_TOKEN];

        $this->getJson('/deploy/repository/status?branch=master', $headers)
            ->assertStatus(422);

        $this->postJson('/deploy/repository/commit', [
            'branch' => 'mobile-overhaul',
            'expected_head_sha' => str_repeat('a', 40),
            'message' => 'Attempt secret write',
            'changes' => [[
                'path' => 'educore/.env',
                'content' => 'APP_KEY=should-never-be-written',
            ]],
        ], $headers)->assertStatus(422);
    }

    public function test_repository_writer_creates_one_atomic_commit_and_fast_forwards_ref(): void
    {
        $head = str_repeat('a', 40);
        $baseTree = str_repeat('b', 40);
        $blob = str_repeat('c', 40);
        $newTree = str_repeat('d', 40);
        $newCommit = str_repeat('e', 40);

        Http::fake(function (ClientRequest $request) use ($head, $baseTree, $blob, $newTree, $newCommit) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/git/ref/heads/mobile-overhaul')) {
                return Http::response(['object' => ['sha' => $head]], 200);
            }
            if ($method === 'GET' && str_contains($url, '/git/commits/'.$head)) {
                return Http::response(['tree' => ['sha' => $baseTree], 'message' => 'Parent'], 200);
            }
            if ($method === 'GET' && str_contains($url, '/git/trees/'.$baseTree.'?recursive=1')) {
                return Http::response([
                    'tree' => [[
                        'path' => 'educore/routes/deploy.php',
                        'type' => 'blob',
                        'mode' => '100644',
                    ]],
                    'truncated' => false,
                ], 200);
            }
            if ($method === 'POST' && str_ends_with($url, '/git/blobs')) {
                return Http::response(['sha' => $blob], 201);
            }
            if ($method === 'POST' && str_ends_with($url, '/git/trees')) {
                return Http::response(['sha' => $newTree], 201);
            }
            if ($method === 'POST' && str_ends_with($url, '/git/commits')) {
                return Http::response(['sha' => $newCommit], 201);
            }
            if ($method === 'PATCH' && str_contains($url, '/git/refs/heads/mobile-overhaul')) {
                return Http::response(['object' => ['sha' => $newCommit]], 200);
            }

            return Http::response(['message' => 'Unexpected test request: '.$method.' '.$url], 500);
        });

        $response = $this->postJson('/deploy/repository/commit', [
            'branch' => 'mobile-overhaul',
            'expected_head_sha' => $head,
            'message' => 'Atomic test commit',
            'changes' => [[
                'path' => 'educore/routes/deploy.php',
                'content' => "<?php\n// changed\n",
            ]],
        ], [
            'X-EduCore-Deploy-Token' => self::DEPLOY_TOKEN,
        ]);

        $response->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('commit_sha', $newCommit)
            ->assertJsonPath('previous_head_sha', $head)
            ->assertJsonPath('actions_required', false)
            ->assertJsonPath('changed_paths.0', 'educore/routes/deploy.php');

        Http::assertSent(function (ClientRequest $request) use ($newCommit): bool {
            return $request->method() === 'PATCH'
                && str_contains($request->url(), '/git/refs/heads/mobile-overhaul')
                && $request['sha'] === $newCommit
                && $request['force'] === false;
        });
    }

    public function test_repository_writer_stops_on_stale_head_without_creating_blobs(): void
    {
        $expected = str_repeat('a', 40);
        $current = str_repeat('f', 40);

        Http::fake([
            'https://api.github.com/repos/*/git/ref/heads/mobile-overhaul' => Http::response([
                'object' => ['sha' => $current],
            ], 200),
        ]);

        $this->postJson('/deploy/repository/commit', [
            'branch' => 'mobile-overhaul',
            'expected_head_sha' => $expected,
            'message' => 'Stale write attempt',
            'changes' => [[
                'path' => 'docs/stale.md',
                'content' => 'stale',
            ]],
        ], [
            'X-EduCore-Deploy-Token' => self::DEPLOY_TOKEN,
        ])->assertStatus(409)
            ->assertJsonPath('error', 'branch_head_changed')
            ->assertJsonPath('current_head_sha', $current);

        Http::assertSentCount(1);
    }
}
