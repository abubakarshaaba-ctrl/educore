<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Lightweight source preflight that never calls GitHub Actions.
 *
 * This endpoint validates the branch head and critical source contracts through
 * GitHub's repository/contents APIs only. It is useful before or after an
 * atomic SelfGitCommitController write when Actions runners are unavailable.
 * It intentionally does not claim to replace Gradle, PHPUnit, lint, signing or
 * an install-on-device QA pass.
 */
class SelfSourceValidationController extends Controller
{
    private const REPO = 'abubakarshaaba-ctrl/educore';
    private const ALLOWED_REFS = ['mobile-overhaul', 'master'];

    public function android(Request $request)
    {
        $this->authorise($request);
        $ref = trim((string) $request->query('ref', 'mobile-overhaul'));
        abort_unless(in_array($ref, self::ALLOWED_REFS, true), 422, 'Invalid repository ref.');

        $client = $this->githubClient();
        $commit = $client->get('https://api.github.com/repos/'.self::REPO.'/commits/'.rawurlencode($ref));
        if (! $commit->successful()) {
            return response()->json([
                'ok' => false,
                'release_ready' => false,
                'ref' => $ref,
                'step' => 'resolve-ref',
                'github_status' => $commit->status(),
                'message' => 'Unable to resolve the repository ref.',
            ], 502);
        }

        $sha = (string) $commit->json('sha');
        $checks = [];

        foreach ($this->contracts() as $path => $contract) {
            $content = $this->readRepositoryFile($client, $path, $ref);
            $exists = $content !== null;
            $checks[] = $this->check(
                'file:'.$path,
                $exists,
                $exists ? 'Required source file is present.' : 'Required source file is missing.'
            );
            if (! $exists) {
                continue;
            }

            foreach ($contract['contains'] ?? [] as $marker) {
                $checks[] = $this->check(
                    'marker:'.$path.':'.$marker,
                    str_contains($content, $marker),
                    str_contains($content, $marker)
                        ? 'Expected source marker is present.'
                        : 'Expected source marker is missing: '.$marker
                );
            }

            foreach ($contract['excludes'] ?? [] as $marker) {
                $checks[] = $this->check(
                    'excluded:'.$path.':'.$marker,
                    ! str_contains($content, $marker),
                    ! str_contains($content, $marker)
                        ? 'Forbidden/retired marker is absent.'
                        : 'Forbidden/retired marker is present: '.$marker
                );
            }
        }

        $errors = array_values(array_filter($checks, fn (array $check): bool => ! $check['ok']));
        $ok = count($errors) === 0;

        return response()->json([
            'ok' => $ok,
            'release_ready' => false,
            'validation_mode' => 'source-only-no-actions',
            'repository' => self::REPO,
            'ref' => $ref,
            'source_sha' => $sha,
            'actions_queried' => false,
            'checks_total' => count($checks),
            'checks_failed' => count($errors),
            'checks' => $checks,
            'decision' => $ok
                ? 'SOURCE PASS / BUILD UNVERIFIED: critical contracts are present. Run local Gradle/PHP validation before release.'
                : 'BLOCK: source preflight failed.',
            'validated_at' => now()->toIso8601String(),
        ], $ok ? 200 : 422);
    }

    /** @return array<string,array{contains?:array<int,string>,excludes?:array<int,string>}> */
    private function contracts(): array
    {
        return [
            'educore/app/Http/Controllers/SelfGitCommitController.php' => [
                'contains' => [
                    "'/git/blobs'",
                    "'/git/trees'",
                    "'/git/commits'",
                    "'/git/refs/heads/'",
                    "'force' => false",
                    'expected_head_sha',
                    'GITHUB_WRITE_TOKEN',
                ],
                'excludes' => [
                    '/actions/runs/',
                    'force' . ' => true',
                ],
            ],
            'educore/routes/deploy.php' => [
                'contains' => [
                    '/deploy/repository/status',
                    '/deploy/repository/commit',
                    'SelfGitCommitController',
                ],
            ],
            'educore/config/app.php' => [
                'contains' => [
                    "env('GITHUB_WRITE_TOKEN')",
                    "env('GITHUB_COMMIT_BRANCHES', 'mobile-overhaul')",
                    'FILTER_VALIDATE_BOOLEAN',
                ],
            ],
            'educore/app/Http/Controllers/Api/StaffCbtCreateApiController.php' => [
                'contains' => [
                    'public function options',
                    'public function store',
                    'teacherTeachesBank',
                    'class_arm_ids',
                    'createSectionsFromBank',
                    'cbt.exam.created',
                ],
            ],
            'educore/routes/mobile-staff-cbt.php' => [
                'contains' => [
                    "Route::get('options'",
                    "Route::post('exams'",
                ],
            ],
            'educore/bootstrap/app.php' => [
                'contains' => [
                    "prefix('api/v1/staff/cbt')",
                    'AuthenticateApiToken::class',
                    "group(base_path('routes/mobile-staff-cbt.php'))",
                ],
            ],
            'mobile-native/core/network/src/main/java/online/educoreng/educore/core/network/StaffCbtApi.kt' => [
                'contains' => [
                    '@GET("staff/cbt/options")',
                    '@POST("staff/cbt/exams")',
                    '@PATCH("staff/cbt/exams/{exam}/schedule")',
                ],
                'excludes' => [
                    'staff/cbt/exams/{exam}/reschedule',
                ],
            ],
            'mobile-native/app/src/main/java/online/educoreng/educore/presentation/StaffCbtCreateScreen.kt' => [
                'contains' => [
                    'StaffCbtCreateScreen',
                    'Create examination draft',
                    'StaffCbtCreateRequestDto',
                    'selectedClasses',
                ],
            ],
            'mobile-native/app/src/main/java/online/educoreng/educore/presentation/StaffModulesHubScreen.kt' => [
                'contains' => [
                    'StaffCbtCreateScreen',
                    'capabilities.createExam',
                    'consumeCreatedExam',
                ],
            ],
            'educore/tests/Feature/SelfGitCommitControllerTest.php' => [
                'contains' => [
                    'assertNotSent',
                    "'/actions/'",
                    'branch_head_changed',
                ],
            ],
            'educore/tests/Feature/MobileStaffCbtTest.php' => [
                'contains' => [
                    'test_subject_teacher_receives_scoped_creation_options_and_can_create_draft',
                    "capabilities.create_exam', true",
                    'cbt.exam.created',
                ],
            ],
            'mobile-native/core/network/src/test/java/online/educoreng/educore/core/network/StaffCbtApiContractTest.kt' => [
                'contains' => [
                    'createOptions',
                    'createExam',
                    'staff/cbt/options',
                ],
            ],
        ];
    }

    private function readRepositoryFile(PendingRequest $client, string $path, string $ref): ?string
    {
        $response = $client->get(
            'https://api.github.com/repos/'.self::REPO.'/contents/'.implode('/', array_map('rawurlencode', explode('/', $path))),
            ['ref' => $ref]
        );

        if (! $response->successful()) {
            return null;
        }

        $encoded = preg_replace('/\s+/', '', (string) $response->json('content', ''));
        $decoded = base64_decode($encoded, true);

        return $decoded === false ? null : $decoded;
    }

    private function githubClient(): PendingRequest
    {
        $token = (string) (config('app.github_write_token') ?: config('app.deploy_gh_token', ''));
        $client = Http::acceptJson()
            ->withHeaders([
                'User-Agent' => 'educore-source-preflight',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->timeout(30)
            ->retry(2, 250, throw: false);

        return $token !== '' ? $client->withToken($token) : $client;
    }

    private function authorise(Request $request): void
    {
        $expected = (string) (config('app.deploy_token') ?: SelfDeployController::derivedToken());
        $supplied = (string) ($request->header('X-EduCore-Deploy-Token') ?: $request->bearerToken());
        if ($expected === '' || $supplied === '' || ! hash_equals($expected, $supplied)) {
            abort(403, 'Invalid deploy token.');
        }

        if ($request->has('gh') || $request->has('github_token') || $request->hasHeader('X-GitHub-Token')) {
            abort(400, 'GitHub credentials are server-side only.');
        }
    }

    /** @return array{name:string,ok:bool,message:string} */
    private function check(string $name, bool $ok, string $message): array
    {
        return compact('name', 'ok', 'message');
    }
}
