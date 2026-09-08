<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SelfSourceValidationControllerTest extends TestCase
{
    public function test_source_preflight_never_calls_actions_api(): void
    {
        config()->set('app.deploy_token', 'deploy-test-token');
        config()->set('app.github_write_token', 'github-read-token');

        $allMarkers = implode("\n", [
            "'/git/blobs'",
            "'/git/trees'",
            "'/git/commits'",
            "'/git/refs/heads/'",
            "'force' => false",
            'expected_head_sha',
            'GITHUB_WRITE_TOKEN',
            '/deploy/repository/status',
            '/deploy/repository/commit',
            'SelfGitCommitController',
            "env('GITHUB_WRITE_TOKEN')",
            "env('GITHUB_COMMIT_BRANCHES', 'mobile-overhaul')",
            'FILTER_VALIDATE_BOOLEAN',
            'public function options',
            'public function store',
            'teacherTeachesBank',
            'class_arm_ids',
            'createSectionsFromBank',
            'cbt.exam.created',
            "Route::get('options'",
            "Route::post('exams'",
            "prefix('api/v1/staff/cbt')",
            'AuthenticateApiToken::class',
            "group(base_path('routes/mobile-staff-cbt.php'))",
            '@GET("staff/cbt/options")',
            '@POST("staff/cbt/exams")',
            '@PATCH("staff/cbt/exams/{exam}/schedule")',
            'StaffCbtCreateScreen',
            'Create examination draft',
            'StaffCbtCreateRequestDto',
            'selectedClasses',
            'capabilities.createExam',
            'consumeCreatedExam',
            'assertNotSent',
            "'/actions/'",
            'branch_head_changed',
            'test_subject_teacher_receives_scoped_creation_options_and_can_create_draft',
            "capabilities.create_exam', true",
            'createOptions',
            'createExam',
            'staff/cbt/options',
        ]);

        Http::fake(function (ClientRequest $request) use ($allMarkers) {
            if (str_contains($request->url(), '/commits/mobile-overhaul')) {
                return Http::response(['sha' => str_repeat('a', 40)], 200);
            }

            if (str_contains($request->url(), '/contents/')) {
                return Http::response([
                    'encoding' => 'base64',
                    'content' => base64_encode($allMarkers),
                ], 200);
            }

            return Http::response(['message' => 'Unexpected request'], 500);
        });

        $this->getJson('/deploy/validate-source?ref=mobile-overhaul', [
            'X-EduCore-Deploy-Token' => 'deploy-test-token',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('release_ready', false)
            ->assertJsonPath('validation_mode', 'source-only-no-actions')
            ->assertJsonPath('actions_queried', false)
            ->assertJsonPath('checks_failed', 0);

        Http::assertNotSent(fn (ClientRequest $request): bool => str_contains($request->url(), '/actions/'));
    }

    public function test_source_preflight_requires_deploy_header(): void
    {
        config()->set('app.deploy_token', 'deploy-test-token');

        $this->getJson('/deploy/validate-source?ref=mobile-overhaul')
            ->assertForbidden();
    }
}
