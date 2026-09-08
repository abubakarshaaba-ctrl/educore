<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Shell-free Android workflow/source validation for shared hosting.
 *
 * This does not pretend to replace a real Gradle compile. Its purpose is to
 * give EduCore a deterministic fallback when GitHub Actions fails before a
 * runner executes any step. It downloads the requested repository ref,
 * validates the native Android project/workflow/API contracts without shell
 * access, and classifies the latest GitHub Actions result so runner-infra
 * failures are distinguishable from actual source/build failures.
 *
 * Trigger:
 *   GET /deploy/validate-android?token=<DEPLOY_TOKEN>&ref=mobile-overhaul
 */
class SelfWorkflowValidationController extends Controller
{
    private const REPO = 'abubakarshaaba-ctrl/educore';
    private const WORKFLOW = 'native-android.yml';
    private const DEFAULT_REF = 'master';

    private const REQUIRED_FILES = [
        '.github/workflows/native-android.yml',
        'mobile-native/gradlew',
        'mobile-native/gradle/wrapper/gradle-wrapper.properties',
        'mobile-native/settings.gradle.kts',
        'mobile-native/app/build.gradle.kts',
        'mobile-native/app/src/main/AndroidManifest.xml',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/EduCoreFoundationApp.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/StaffWorkspaceShell.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/StaffClassesScreen.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/ClassesWorkspaceScreens.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/AcademicRepositoryScreens.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/LessonPlannerScreens.kt',
        'mobile-native/app/src/main/java/online/educoreng/educore/presentation/CommunicationScreens.kt',
        'educore/routes/api.php',
    ];

    /** Files that must never reappear once their replacements are canonical. */
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
        $expected = (string) (config('app.deploy_token') ?: SelfDeployController::derivedToken());
        if ($expected === '' || ! hash_equals($expected, (string) $request->query('token'))) {
            abort(403, 'Invalid deploy token.');
        }

        $ref = trim((string) $request->query('ref', self::DEFAULT_REF));
        if (! preg_match('/^[A-Za-z0-9._\/-]{1,120}$/', $ref) || str_contains($ref, '..')) {
            return response()->json(['ok' => false, 'error' => 'Invalid repository ref.'], 422);
        }

        @set_time_limit(0);
        @ignore_user_abort(true);

        $runId = (string) Str::uuid();
        $work = storage_path('app/self-workflow-validation/' . $runId);
        $zipPath = $work . '/repo.zip';
        $extractDir = $work . '/tree';
        @mkdir($work, 0755, true);

        $ghToken = (string) ($request->query('gh') ?: config('app.deploy_gh_token', env('DEPLOY_GH_TOKEN', '')));
        $headers = ['User-Agent' => 'educore-self-workflow-validator'];
        if ($ghToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $ghToken;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(180)
                ->get('https://api.github.com/repos/' . self::REPO . '/zipball/' . rawurlencode($ref));

            if (! $response->successful()) {
                return response()->json([
                    'ok' => false,
                    'step' => 'download',
                    'ref' => $ref,
                    'status' => $response->status(),
                    'message' => 'Unable to download the repository ref for validation.',
                ], 200);
            }

            file_put_contents($zipPath, $response->body());
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                return response()->json(['ok' => false, 'step' => 'unzip', 'ref' => $ref], 500);
            }

            @mkdir($extractDir, 0755, true);
            $zip->extractTo($extractDir);
            $zip->close();

            $roots = glob($extractDir . '/*', GLOB_ONLYDIR) ?: [];
            if (! $roots) {
                return response()->json(['ok' => false, 'step' => 'locate-root', 'ref' => $ref], 500);
            }
            $root = $roots[0];

            $checks = [];
            $this->validateRequiredFiles($root, $checks);
            $this->validateWorkflow($root, $ref, $checks);
            $this->validateGradleProject($root, $checks);
            $this->validateNativeArchitecture($root, $checks);
            $this->validateApiContracts($root, $checks);
            $this->validateRepositoryCleanliness($root, $checks);
            $this->scanConflictMarkers($root, $checks);

            $ci = $this->latestCiState($headers, $ref);
            $errors = array_values(array_filter($checks, fn (array $check) => ! $check['ok'] && $check['severity'] === 'error'));
            $warnings = array_values(array_filter($checks, fn (array $check) => ! $check['ok'] && $check['severity'] === 'warning'));
            $sourceOk = count($errors) === 0;

            $ciClass = (string) ($ci['classification'] ?? 'unknown');
            $ciPassed = $ciClass === 'passed';
            $runnerUnavailable = $ciClass === 'runner_allocation_failure';

            return response()->json([
                'ok' => $sourceOk,
                'release_ready' => $sourceOk && $ciPassed,
                'validation_mode' => $runnerUnavailable && $sourceOk ? 'source-fallback' : 'normal',
                'ref' => $ref,
                'source_validation' => [
                    'passed' => $sourceOk,
                    'checks' => count($checks),
                    'errors' => count($errors),
                    'warnings' => count($warnings),
                ],
                'ci' => $ci,
                'decision' => $this->decision($sourceOk, $ciClass),
                'checks' => $checks,
                'validated_at' => now()->toIso8601String(),
                'note' => 'Source fallback validates repository structure and contracts only. A signed production APK still requires a successful Gradle build/signature verification before release.',
            ], $sourceOk ? 200 : 422);
        } finally {
            @unlink($zipPath);
            $this->rrmdir($extractDir);
            @rmdir($work);
        }
    }

    private function validateRequiredFiles(string $root, array &$checks): void
    {
        foreach (self::REQUIRED_FILES as $path) {
            $this->addCheck(
                $checks,
                'required-file:' . $path,
                is_file($root . '/' . $path),
                'error',
                'Required project file is present.',
                'Required project file is missing.'
            );
        }
    }

    private function validateWorkflow(string $root, string $ref, array &$checks): void
    {
        $path = $root . '/.github/workflows/' . self::WORKFLOW;
        if (! is_file($path)) {
            return;
        }
        $workflow = (string) file_get_contents($path);
        $markers = [
            'ubuntu runner' => 'runs-on: ubuntu-latest',
            'checkout' => 'actions/checkout@v4',
            'JDK setup' => 'actions/setup-java@v4',
            'Gradle setup' => 'gradle/actions/setup-gradle@v4',
            'unit tests' => ':app:testDebugUnitTest',
            'lint' => ':app:lintDebug',
            'debug assemble' => ':app:assembleDebug',
            'release signing gate' => 'Prepare protected release signing',
            'signed release verification' => 'apksigner',
        ];
        foreach ($markers as $label => $needle) {
            $this->containsCheck($checks, 'workflow:' . $label, $workflow, $needle, 'error');
        }

        $this->containsCheck(
            $checks,
            'workflow:development-ref',
            $workflow,
            $ref,
            $ref === 'master' ? 'warning' : 'error'
        );

        $this->addCheck(
            $checks,
            'workflow:no-release-on-development-branch',
            str_contains($workflow, "github.ref == 'refs/heads/master'"),
            'error',
            'Release deployment is restricted to master.',
            'Release deployment is not visibly restricted to master.'
        );
    }

    private function validateGradleProject(string $root, array &$checks): void
    {
        $settingsPath = $root . '/mobile-native/settings.gradle.kts';
        $buildPath = $root . '/mobile-native/app/build.gradle.kts';
        $wrapperPath = $root . '/mobile-native/gradle/wrapper/gradle-wrapper.properties';

        $settings = is_file($settingsPath) ? (string) file_get_contents($settingsPath) : '';
        foreach ([
            ':app', ':core:common', ':core:designsystem', ':core:model', ':core:network', ':core:security', ':core:data',
        ] as $module) {
            $this->containsCheck($checks, 'gradle:module:' . $module, $settings, 'include("' . $module . '")', 'error');
        }

        $build = is_file($buildPath) ? (string) file_get_contents($buildPath) : '';
        foreach ([
            'compileSdk 36' => 'compileSdk = 36',
            'targetSdk 36' => 'targetSdk = 36',
            'Java 17 source' => 'JavaVersion.VERSION_17',
            'Compose enabled' => 'compose = true',
            'release minification' => 'isMinifyEnabled = true',
            'release shrink resources' => 'isShrinkResources = true',
        ] as $label => $needle) {
            $this->containsCheck($checks, 'gradle:' . $label, $build, $needle, 'error');
        }

        $wrapper = is_file($wrapperPath) ? (string) file_get_contents($wrapperPath) : '';
        $validDistribution = preg_match('/distributionUrl=.*gradle-[0-9.]+-(?:all|bin)\.zip/', $wrapper) === 1;
        $this->addCheck(
            $checks,
            'gradle:wrapper-distribution',
            $validDistribution,
            'error',
            'Gradle wrapper distribution is configured.',
            'Gradle wrapper distribution is missing or malformed.'
        );
    }

    private function validateNativeArchitecture(string $root, array &$checks): void
    {
        $foundationPath = $root . '/mobile-native/app/src/main/java/online/educoreng/educore/presentation/EduCoreFoundationApp.kt';
        $staffShellPath = $root . '/mobile-native/app/src/main/java/online/educoreng/educore/presentation/StaffWorkspaceShell.kt';
        $foundation = is_file($foundationPath) ? (string) file_get_contents($foundationPath) : '';
        $staffShell = is_file($staffShellPath) ? (string) file_get_contents($staffShellPath) : '';

        $this->containsCheck($checks, 'architecture:staff-shell-active', $foundation, 'StaffWorkspaceShell', 'error');
        $this->addCheck(
            $checks,
            'architecture:staff-shell-no-browser-fallback',
            ! str_contains($staffShell, 'ACTION_VIEW') && ! str_contains($staffShell, 'onOpenWebModule'),
            'error',
            'Staff workspace has no generic browser fallback.',
            'Staff workspace contains a browser/web-module fallback.'
        );

        $notePath = $root . '/mobile-native/app/src/main/java/online/educoreng/educore/presentation/AcademicRepositoryScreens.kt';
        $note = is_file($notePath) ? (string) file_get_contents($notePath) : '';
        foreach (['parseAcademicNote', 'AcademicNoteBlock.Heading', 'AcademicNoteBlock.Bullet', 'AcademicNoteBlock.Numbered'] as $needle) {
            $this->containsCheck($checks, 'notes:' . $needle, $note, $needle, 'error');
        }
    }

    private function validateApiContracts(string $root, array &$checks): void
    {
        $path = $root . '/educore/routes/api.php';
        $api = is_file($path) ? (string) file_get_contents($path) : '';
        $required = [
            "Route::get('bootstrap'",
            "Route::get('dashboard'",
            "Route::get('classes'",
            "Route::get('classes/{classArm}/students'",
            "Route::get('staff-attendance'",
            "Route::post('staff-attendance/clock-in'",
            "Route::post('staff-attendance/clock-out'",
            "Route::prefix('academic-repository')",
            "Route::prefix('lesson-plans')",
            "Route::get('schedule'",
        ];
        foreach ($required as $needle) {
            $this->containsCheck($checks, 'api:' . $needle, $api, $needle, 'error');
        }

        $attendancePath = $root . '/educore/app/Http/Controllers/Api/StaffAttendanceApiController.php';
        if (is_file($attendancePath)) {
            $attendance = (string) file_get_contents($attendancePath);
            $this->containsCheck(
                $checks,
                'attendance:early-counts-as-present',
                $attendance,
                "whereIn('status', ['early', 'present'])->count()",
                'error'
            );
        } else {
            $this->addCheck($checks, 'attendance:controller-present', false, 'error', '', 'Staff attendance API controller is missing.');
        }
    }

    private function validateRepositoryCleanliness(string $root, array &$checks): void
    {
        foreach (self::RETIRED_PATHS as $path) {
            $this->addCheck(
                $checks,
                'retired-file:' . $path,
                ! file_exists($root . '/' . $path),
                'error',
                'Retired file is absent.',
                'Retired file has reappeared in the repository.'
            );
        }

        $backupFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root . '/educore', \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if (! $item->isFile()) {
                continue;
            }
            $name = $item->getFilename();
            if (preg_match('/(?:\.bak(?:-|$)|\.old$|\.orig$|~$)/i', $name)) {
                $backupFiles[] = str_replace($root . '/', '', $item->getPathname());
                if (count($backupFiles) >= 25) {
                    break;
                }
            }
        }
        $this->addCheck(
            $checks,
            'repository:no-backup-files',
            count($backupFiles) === 0,
            'warning',
            'No backup/deprecated files detected in the Laravel tree.',
            'Backup/deprecated files detected: ' . implode(', ', $backupFiles)
        );
    }

    private function scanConflictMarkers(string $root, array &$checks): void
    {
        $targets = [
            $root . '/mobile-native/app/src/main/java',
            $root . '/mobile-native/core',
            $root . '/educore/app',
            $root . '/educore/routes',
        ];
        $hits = [];
        foreach ($targets as $target) {
            if (! is_dir($target)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $item) {
                if (! $item->isFile() || $item->getSize() > 2_000_000) {
                    continue;
                }
                $content = (string) @file_get_contents($item->getPathname());
                if (str_contains($content, '<<<<<<< ') || str_contains($content, '>>>>>>> ')) {
                    $hits[] = str_replace($root . '/', '', $item->getPathname());
                    if (count($hits) >= 10) {
                        break 2;
                    }
                }
            }
        }
        $this->addCheck(
            $checks,
            'repository:no-merge-conflict-markers',
            count($hits) === 0,
            'error',
            'No unresolved merge-conflict markers detected.',
            'Merge-conflict markers detected: ' . implode(', ', $hits)
        );
    }

    private function latestCiState(array $headers, string $ref): array
    {
        try {
            $runs = Http::withHeaders($headers)->acceptJson()->timeout(30)->get(
                'https://api.github.com/repos/' . self::REPO . '/actions/workflows/' . self::WORKFLOW . '/runs',
                ['branch' => $ref, 'per_page' => 1]
            );
            if (! $runs->successful()) {
                return [
                    'classification' => 'unavailable',
                    'http_status' => $runs->status(),
                    'message' => 'GitHub Actions status could not be read.',
                ];
            }

            $run = data_get($runs->json(), 'workflow_runs.0');
            if (! is_array($run)) {
                return ['classification' => 'not_run', 'message' => 'No Android workflow run exists for this ref.'];
            }

            $runId = (int) ($run['id'] ?? 0);
            $jobsResponse = Http::withHeaders($headers)->acceptJson()->timeout(30)->get(
                'https://api.github.com/repos/' . self::REPO . '/actions/runs/' . $runId . '/jobs',
                ['per_page' => 20]
            );
            $jobs = $jobsResponse->successful() ? (array) data_get($jobsResponse->json(), 'jobs', []) : [];
            $steps = [];
            foreach ($jobs as $job) {
                foreach ((array) ($job['steps'] ?? []) as $step) {
                    $steps[] = $step;
                }
            }

            $status = (string) ($run['status'] ?? 'unknown');
            $conclusion = (string) ($run['conclusion'] ?? '');
            $classification = match (true) {
                $status !== 'completed' => 'running',
                $conclusion === 'success' => 'passed',
                $conclusion === 'failure' && count($steps) === 0 => 'runner_allocation_failure',
                $conclusion === 'failure' => 'build_failure',
                $conclusion === 'cancelled' => 'cancelled',
                default => $conclusion !== '' ? $conclusion : 'unknown',
            };

            return [
                'classification' => $classification,
                'run_id' => $runId,
                'head_sha' => $run['head_sha'] ?? null,
                'status' => $status,
                'conclusion' => $conclusion ?: null,
                'jobs' => count($jobs),
                'executed_steps' => count($steps),
                'url' => $run['html_url'] ?? null,
                'message' => $classification === 'runner_allocation_failure'
                    ? 'GitHub Actions failed before any step executed; this is classified as runner/workflow infrastructure failure rather than a source compile failure.'
                    : null,
            ];
        } catch (\Throwable $e) {
            return [
                'classification' => 'unavailable',
                'message' => 'CI status lookup failed: ' . $e->getMessage(),
            ];
        }
    }

    private function decision(bool $sourceOk, string $ciClass): string
    {
        if (! $sourceOk) {
            return 'STOP: source validation failed. Fix the reported errors before merge or deployment.';
        }
        return match ($ciClass) {
            'passed' => 'PASS: source validation and GitHub Android CI both passed.',
            'runner_allocation_failure' => 'CONTINUE DEVELOPMENT: source validation passed and CI failed before a runner executed. Do not release an APK until a real Gradle build later succeeds.',
            'running' => 'WAIT: source validation passed; GitHub Android CI is still running.',
            'build_failure' => 'STOP RELEASE: GitHub executed build steps and failed. Inspect the failing job before merge/release.',
            default => 'SOURCE PASS ONLY: continue development, but require a real Gradle build before production release.',
        };
    }

    private function containsCheck(array &$checks, string $name, string $haystack, string $needle, string $severity): void
    {
        $this->addCheck(
            $checks,
            $name,
            $haystack !== '' && str_contains($haystack, $needle),
            $severity,
            'Expected contract marker is present.',
            'Expected contract marker is missing: ' . $needle
        );
    }

    private function addCheck(
        array &$checks,
        string $name,
        bool $ok,
        string $severity,
        string $passMessage,
        string $failMessage,
    ): void {
        $checks[] = [
            'name' => $name,
            'ok' => $ok,
            'severity' => $severity,
            'message' => $ok ? $passMessage : $failMessage,
        ];
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
