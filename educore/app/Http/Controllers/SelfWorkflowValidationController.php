<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Shell-free validation fallback for EduCore's native Android workflow.
 *
 * GitHub Actions can occasionally fail before a runner is allocated. In that
 * case there are no executable steps or job logs, so the failure is not proof
 * that the Android source is broken. This controller independently downloads
 * an approved repository ref, validates the native source/workflow contracts,
 * classifies the latest GitHub run, and can request a rerun when a server-side
 * Actions-write token is configured.
 *
 * It deliberately does NOT claim that static/source validation replaces a
 * Gradle compile. Release readiness remains false until CI has actually run
 * and passed for the exact source SHA (or a separately verified local release
 * build is produced by mobile-native/tools/verify-native.ps1).
 *
 * GET /deploy/validate-android?token=<DEPLOY_TOKEN>&ref=mobile-overhaul
 * Optional: &retry=1
 */
class SelfWorkflowValidationController extends Controller
{
    private const REPO = 'abubakarshaaba-ctrl/educore';
    private const WORKFLOW = 'native-android.yml';
    private const ALLOWED_REFS = ['mobile-overhaul', 'master'];

    private const REQUIRED_FILES = [
        '.github/workflows/native-android.yml',
        'mobile-native/gradlew',
        'mobile-native/gradlew.bat',
        'mobile-native/gradle/wrapper/gradle-wrapper.properties',
        'mobile-native/settings.gradle.kts',
        'mobile-native/app/build.gradle.kts',
        'mobile-native/app/src/main/AndroidManifest.xml',
        'mobile-native/tools/verify-native.ps1',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/EduCoreFoundationApp.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/StaffWorkspaceShell.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/StaffClassesScreen.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/ClassesWorkspaceScreens.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/AcademicRepositoryScreens.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/LessonPlannerScreens.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/CommunicationScreens.kt',
        'educore/app/Http/Controllers/Api/StaffAttendanceApiController.php',
        'educore/routes/api.php',
    ];

    private const RETIRED_PATHS = [
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/StaffAuthorizedShell.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/StaffAuthorizedShowcaseShell.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/ShowcaseStaffScreens.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/ShowcaseClassesScreen.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/ShowcaseAcademicResourceDetailScreen.kt',
        'educore/.tmp/PsySH/psysh_history',
    ];

    public function android(Request $request)
    {
        $this->authorise($request);

        // Credentials must never be carried in a URL/query string. Only
        // server-side configuration may provide GitHub credentials.
        if ($request->has('gh') || $request->has('github_token')) {
            return response()->json([
                'ok' => false,
                'error' => 'GitHub credentials are not accepted in the request URL.',
            ], 400);
        }

        $ref = trim((string) $request->query('ref', 'mobile-overhaul'));
        if (! in_array($ref, self::ALLOWED_REFS, true)) {
            return response()->json(['ok' => false, 'error' => 'Invalid repository ref.'], 422);
        }

        @set_time_limit(0);
        @ignore_user_abort(true);

        $headers = $this->githubHeaders(false);
        $sourceSha = $this->resolveRefSha($headers, $ref);
        $ci = $this->latestCiState($headers, $ref, $sourceSha);
        $retry = null;

        if ($request->boolean('retry') && ($ci['classification'] ?? null) === 'runner_allocation_failure') {
            $retry = $this->retryRun((int) ($ci['run_id'] ?? 0));
        }

        $runId = (string) Str::uuid();
        $work = storage_path('app/self-workflow-validation/'.$runId);
        $zipPath = $work.'/repo.zip';
        $extractDir = $work.'/tree';
        @mkdir($work, 0755, true);

        try {
            $download = Http::withHeaders($headers)
                ->timeout(180)
                ->get('https://api.github.com/repos/'.self::REPO.'/zipball/'.rawurlencode($ref));

            if (! $download->successful()) {
                return response()->json([
                    'ok' => false,
                    'release_ready' => false,
                    'step' => 'download',
                    'ref' => $ref,
                    'source_sha' => $sourceSha,
                    'status' => $download->status(),
                    'ci' => $ci,
                    'retry' => $retry,
                    'message' => 'Unable to download the repository ref for self-validation.',
                ], 200);
            }

            file_put_contents($zipPath, $download->body());
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                return response()->json([
                    'ok' => false,
                    'release_ready' => false,
                    'step' => 'unzip',
                    'ref' => $ref,
                    'source_sha' => $sourceSha,
                    'ci' => $ci,
                    'retry' => $retry,
                ], 500);
            }

            @mkdir($extractDir, 0755, true);
            $zip->extractTo($extractDir);
            $zip->close();

            $roots = glob($extractDir.'/*', GLOB_ONLYDIR) ?: [];
            if (! $roots) {
                return response()->json([
                    'ok' => false,
                    'release_ready' => false,
                    'step' => 'locate-root',
                    'ref' => $ref,
                    'source_sha' => $sourceSha,
                    'ci' => $ci,
                    'retry' => $retry,
                ], 500);
            }

            $checks = $this->validateTree($roots[0], $ref);
            $errors = array_values(array_filter($checks, fn (array $check) => ! $check['ok'] && $check['severity'] === 'error'));
            $warnings = array_values(array_filter($checks, fn (array $check) => ! $check['ok'] && $check['severity'] === 'warning'));
            $sourceOk = count($errors) === 0;
            $ciClass = (string) ($ci['classification'] ?? 'unknown');
            $ciPassedForHead = $ciClass === 'passed' && ($ci['head_sha'] ?? null) === $sourceSha;
            $runnerUnavailable = $ciClass === 'runner_allocation_failure';

            return response()->json([
                'ok' => $sourceOk,
                'release_ready' => $sourceOk && $ciPassedForHead,
                'validation_mode' => $runnerUnavailable && $sourceOk ? 'source-fallback' : 'normal',
                'ref' => $ref,
                'source_sha' => $sourceSha,
                'source_validation' => [
                    'passed' => $sourceOk,
                    'checks' => count($checks),
                    'errors' => count($errors),
                    'warnings' => count($warnings),
                ],
                'ci' => $ci,
                'retry' => $retry,
                'decision' => $this->decision($sourceOk, $ciClass, $ciPassedForHead),
                'checks' => $checks,
                'validated_at' => now()->toIso8601String(),
                'note' => 'Source fallback validates repository structure and contracts only. A production APK still requires an executed Gradle build, tests, lint, signing and signature verification.',
            ], $sourceOk ? 200 : 422);
        } finally {
            @unlink($zipPath);
            $this->rrmdir($extractDir);
            @rmdir($work);
        }
    }

    private function authorise(Request $request): void
    {
        $expected = (string) (config('app.deploy_token') ?: SelfDeployController::derivedToken());
        $supplied = (string) $request->query('token');
        if ($expected === '' || ! hash_equals($expected, $supplied)) {
            abort(403, 'Invalid deploy token.');
        }
    }

    private function githubHeaders(bool $write): array
    {
        $workflowToken = (string) config('app.workflow_gh_token', '');
        $readToken = (string) config('app.deploy_gh_token', '');
        $token = $write ? $workflowToken : ($workflowToken ?: $readToken);

        $headers = [
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'educore-self-workflow-validator',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer '.$token;
        }
        return $headers;
    }

    private function resolveRefSha(array $headers, string $ref): ?string
    {
        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->get('https://api.github.com/repos/'.self::REPO.'/commits/'.rawurlencode($ref));
            return $response->successful() ? (string) $response->json('sha') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function latestCiState(array $headers, string $ref, ?string $sourceSha): array
    {
        try {
            $runs = Http::withHeaders($headers)
                ->timeout(30)
                ->get('https://api.github.com/repos/'.self::REPO.'/actions/workflows/'.self::WORKFLOW.'/runs', [
                    'branch' => $ref,
                    'per_page' => 1,
                ]);

            if (! $runs->successful()) {
                return ['classification' => 'unavailable', 'http_status' => $runs->status()];
            }

            $run = collect($runs->json('workflow_runs', []))->first();
            if (! $run) {
                return ['classification' => 'not_found'];
            }

            $headSha = (string) ($run['head_sha'] ?? '');
            if ($sourceSha && $headSha !== '' && ! hash_equals($sourceSha, $headSha)) {
                return [
                    'classification' => 'stale_run',
                    'run_id' => (int) ($run['id'] ?? 0),
                    'run_number' => (int) ($run['run_number'] ?? 0),
                    'head_sha' => $headSha,
                    'source_sha' => $sourceSha,
                    'status' => (string) ($run['status'] ?? 'unknown'),
                    'conclusion' => (string) ($run['conclusion'] ?? 'unknown'),
                    'html_url' => (string) ($run['html_url'] ?? ''),
                ];
            }

            $jobs = Http::withHeaders($headers)
                ->timeout(30)
                ->get((string) ($run['jobs_url'] ?? ''));
            $jobList = $jobs->successful() ? $jobs->json('jobs', []) : [];
            $validate = collect($jobList)->firstWhere('name', 'validate') ?: collect($jobList)->first();
            $steps = is_array($validate['steps'] ?? null) ? $validate['steps'] : [];
            $runnerId = (int) ($validate['runner_id'] ?? 0);
            $conclusion = (string) ($validate['conclusion'] ?? $run['conclusion'] ?? '');
            $status = (string) ($run['status'] ?? 'unknown');

            $classification = match (true) {
                $status !== 'completed' => 'in_progress',
                $conclusion === 'success' => 'passed',
                $conclusion === 'failure' && $runnerId === 0 && count($steps) === 0 => 'runner_allocation_failure',
                $conclusion === 'failure' && count($steps) > 0 => 'executed_failure',
                $conclusion === 'cancelled' => 'cancelled',
                default => 'unknown',
            };

            return [
                'classification' => $classification,
                'run_id' => (int) ($run['id'] ?? 0),
                'run_number' => (int) ($run['run_number'] ?? 0),
                'head_sha' => $headSha,
                'status' => $status,
                'conclusion' => (string) ($run['conclusion'] ?? 'unknown'),
                'runner_id' => $runnerId,
                'step_count' => count($steps),
                'display_title' => (string) ($run['display_title'] ?? ''),
                'html_url' => (string) ($run['html_url'] ?? ''),
            ];
        } catch (\Throwable $e) {
            return [
                'classification' => 'unavailable',
                'error' => mb_substr($e->getMessage(), 0, 300),
            ];
        }
    }

    private function retryRun(int $runId): array
    {
        if ($runId <= 0) {
            return ['status' => 'skipped', 'reason' => 'No workflow run id is available.'];
        }
        if ((string) config('app.workflow_gh_token', '') === '') {
            return [
                'status' => 'skipped',
                'reason' => 'A server-side WORKFLOW_GH_TOKEN with Actions write permission is not configured.',
            ];
        }

        try {
            $response = Http::withHeaders($this->githubHeaders(true))
                ->timeout(30)
                ->post('https://api.github.com/repos/'.self::REPO.'/actions/runs/'.$runId.'/rerun-failed-jobs');
            return [
                'status' => $response->successful() ? 'requested' : 'failed',
                'http_status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 300)];
        }
    }

    private function validateTree(string $root, string $ref): array
    {
        $checks = [];

        foreach (self::REQUIRED_FILES as $path) {
            $this->addCheck(
                $checks,
                'required-file:'.$path,
                is_file($root.'/'.$path),
                'error',
                'Required project file is present.',
                'Required project file is missing.'
            );
        }

        foreach (self::RETIRED_PATHS as $path) {
            $this->addCheck(
                $checks,
                'retired-absent:'.$path,
                ! file_exists($root.'/'.$path),
                'error',
                'Retired path is absent.',
                'Retired/deprecated path has reappeared.'
            );
        }

        $workflow = $this->read($root.'/.github/workflows/'.self::WORKFLOW);
        foreach ([
            'workflow:runner' => 'runs-on: ubuntu-latest',
            'workflow:checkout' => 'actions/checkout@v4',
            'workflow:jdk' => 'actions/setup-java@v4',
            'workflow:gradle' => 'gradle/actions/setup-gradle@v4',
            'workflow:dispatch' => 'workflow_dispatch:',
            'workflow:debug-build' => ':app:assembleDebug',
            'workflow:unit-tests' => ':app:testDebugUnitTest',
            'workflow:lint' => ':app:lintDebug',
        ] as $name => $marker) {
            $this->markerCheck($checks, $name, $workflow, $marker, 'error');
        }
        if ($ref === 'mobile-overhaul') {
            $this->markerCheck($checks, 'workflow:overhaul-branch', $workflow, '- mobile-overhaul', 'error');
        }

        $gradle = $this->read($root.'/mobile-native/app/build.gradle.kts');
        foreach ([
            'android:namespace' => 'namespace = "online.educoreng.educore"',
            'android:application-id' => 'applicationId = "online.educoreng.educore"',
            'android:compile-sdk' => 'compileSdk = 36',
            'android:target-sdk' => 'targetSdk = 36',
            'android:minify' => 'isMinifyEnabled = true',
            'android:shrink-resources' => 'isShrinkResources = true',
        ] as $name => $marker) {
            $this->markerCheck($checks, $name, $gradle, $marker, 'error');
        }
        $this->addCheck(
            $checks,
            'android:version-code',
            (bool) preg_match('/versionCode\s*=\s*[1-9][0-9]*/', $gradle),
            'error',
            'Positive Android versionCode is configured.',
            'A positive Android versionCode was not found.'
        );

        $manifest = $this->read($root.'/mobile-native/app/src/main/AndroidManifest.xml');
        $this->markerCheck($checks, 'android:no-backup', $manifest, 'android:allowBackup="false"', 'error');
        $this->markerCheck($checks, 'android:no-cleartext', $manifest, 'android:usesCleartextTraffic="false"', 'error');

        $staffShell = $this->read($root.'/mobile-native/app/src/main/java/online/educoreng/educore/presentation/StaffWorkspaceShell.kt');
        $this->markerCheck($checks, 'staff-shell:rbac-modules', $staffShell, 'session.modules', 'error');
        $this->addCheck(
            $checks,
            'staff-shell:no-browser-fallback',
            ! str_contains($staffShell, 'ACTION_VIEW') && ! str_contains($staffShell, 'onOpenWebModule'),
            'error',
            'Staff shell has no generic external-browser fallback.',
            'Staff shell contains a browser fallback.'
        );

        $notes = $this->read($root.'/mobile-native/app/src/main/java/online/educoreng/educore/presentation/AcademicRepositoryScreens.kt');
        foreach (['parseAcademicNote', 'AcademicNoteBlock.Heading', 'AcademicNoteBlock.Bullet', 'AcademicNoteBlock.Numbered'] as $marker) {
            $this->markerCheck($checks, 'notes:'.$marker, $notes, $marker, 'error');
        }

        $attendance = $this->read($root.'/educore/app/Http/Controllers/Api/StaffAttendanceApiController.php');
        $this->markerCheck(
            $checks,
            'attendance:early-counts-as-present',
            $attendance,
            "whereIn('status', ['early', 'present'])",
            'error'
        );

        $validator = $this->read($root.'/mobile-native/tools/verify-native.ps1');
        foreach ([':app:testDebugUnitTest', ':app:lintDebug', ':app:assembleDebug', 'AcademicRepositoryScreens.kt', 'StaffWorkspaceShell.kt'] as $marker) {
            $this->markerCheck($checks, 'local-validator:'.$marker, $validator, $marker, 'error');
        }

        $dirty = $this->findDeprecatedFiles($root);
        $this->addCheck(
            $checks,
            'repository:deprecated-files',
            count($dirty) === 0,
            'error',
            'No deprecated backup/temp/showcase source files were found.',
            'Deprecated files found: '.implode(', ', array_slice($dirty, 0, 30))
        );

        $conflicts = $this->findConflictMarkers($root);
        $this->addCheck(
            $checks,
            'repository:merge-conflicts',
            count($conflicts) === 0,
            'error',
            'No unresolved merge-conflict markers were found.',
            'Merge-conflict markers found: '.implode(', ', array_slice($conflicts, 0, 20))
        );

        return $checks;
    }

    private function findDeprecatedFiles(string $root): array
    {
        $found = [];
        $rootLength = strlen(rtrim($root, DIRECTORY_SEPARATOR)) + 1;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), $rootLength));
            $basename = $file->getBasename();
            $deprecated = preg_match('/(?:\.bak(?:[-_.].*)?$|\.old$|\.orig$|~$)/i', $basename)
                || str_contains($relative, '/.tmp/')
                || str_starts_with($relative, '.tmp/')
                || preg_match('/(^|\/)Showcase[^\/]*\.(kt|java)$/', $relative)
                || preg_match('/(^|\/)verify-phase\d+\.ps1$/i', $relative);
            if ($deprecated) {
                $found[] = $relative;
            }
        }
        sort($found);
        return $found;
    }

    private function findConflictMarkers(string $root): array
    {
        $found = [];
        $extensions = ['kt', 'kts', 'java', 'php', 'xml', 'yml', 'yaml', 'json', 'md', 'ps1'];
        $rootLength = strlen(rtrim($root, DIRECTORY_SEPARATOR)) + 1;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), $extensions, true)) {
                continue;
            }
            $contents = @file_get_contents($file->getPathname());
            if ($contents !== false && preg_match('/^(<<<<<<< |>>>>>>> )/m', $contents)) {
                $found[] = str_replace('\\', '/', substr($file->getPathname(), $rootLength));
            }
        }
        sort($found);
        return $found;
    }

    private function markerCheck(array &$checks, string $name, string $content, string $marker, string $severity): void
    {
        $this->addCheck(
            $checks,
            $name,
            $content !== '' && str_contains($content, $marker),
            $severity,
            'Expected marker is present.',
            'Expected marker is missing: '.$marker
        );
    }

    private function addCheck(
        array &$checks,
        string $name,
        bool $ok,
        string $severity,
        string $passMessage,
        string $failMessage
    ): void {
        $checks[] = [
            'name' => $name,
            'ok' => $ok,
            'severity' => $severity,
            'message' => $ok ? $passMessage : $failMessage,
        ];
    }

    private function read(string $path): string
    {
        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    private function decision(bool $sourceOk, string $ciClass, bool $ciPassedForHead): string
    {
        if (! $sourceOk) {
            return 'BLOCK: source validation failed.';
        }
        if ($ciPassedForHead) {
            return 'PASS: source validation and executed CI passed for this exact source SHA.';
        }
        return match ($ciClass) {
            'runner_allocation_failure' => 'SOURCE PASS / BUILD UNVERIFIED: GitHub failed before allocating a runner. Retry CI or run mobile-native/tools/verify-native.ps1 locally before release.',
            'stale_run' => 'SOURCE PASS / BUILD UNVERIFIED: latest CI run belongs to an older commit.',
            'executed_failure' => 'BLOCK: CI executed and failed. Fix the build/test failure before release.',
            'in_progress' => 'WAIT: CI is still running.',
            default => 'SOURCE PASS / BUILD UNVERIFIED: no successful executed CI is available for this source SHA.',
        };
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
