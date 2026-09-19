<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumArmSubjectTeacher;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumComposite;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumGrade;
use App\Models\ParallelCurriculumIntegration;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\ParallelCurriculumScore;
use App\Models\ParallelCurriculumSubject;
use App\Models\Term;
use App\Models\User;
use App\Services\ParallelCurriculumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ParallelCurriculumDiagnosticsController extends Controller
{
    public function __invoke(
        Request $request,
        ParallelCurriculumService $service
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            $user
                && ($user->isSuperAdmin() || $user->canAccessExactModule('scores')),
            403
        );

        $tenantId = (int) $user->tenant_id;
        $checks = [];

        $probe = function (string $name, callable $callback) use (&$checks): void {
            try {
                $result = $callback();

                $checks[$name] = [
                    'ok' => true,
                    'result' => $result,
                ];
            } catch (\Throwable $e) {
                report($e);

                $checks[$name] = [
                    'ok' => false,
                    'exception' => get_class($e),
                    'message' => mb_substr($e->getMessage(), 0, 1500),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ];
            }
        };

        $parallelTables = [
            'parallel_curricula',
            'parallel_curriculum_subjects',
            'parallel_curriculum_classes',
            'parallel_curriculum_class_subjects',
            'parallel_curriculum_enrolments',
            'parallel_curriculum_scores',
            'parallel_curriculum_integrations',
            'parallel_curriculum_composites',
            'parallel_curriculum_grades',
            'parallel_curriculum_report_publications',
            'parallel_curriculum_class_arms',
            'parallel_curriculum_class_grades',
            'parallel_curriculum_promotion_rules',
            'parallel_curriculum_promotions',
            'parallel_curriculum_transfers',
            'parallel_curriculum_arm_subject_teachers',
        ];

        $schema = [];
        foreach ($parallelTables as $table) {
            try {
                $exists = Schema::hasTable($table);
                $schema[$table] = [
                    'exists' => $exists,
                    'columns' => $exists ? Schema::getColumnListing($table) : [],
                ];
            } catch (\Throwable $e) {
                $schema[$table] = [
                    'exists' => null,
                    'error' => mb_substr($e->getMessage(), 0, 700),
                ];
            }
        }

        $probe('module_enabled', fn () => $service->enabledForTenant($tenantId));
        $probe('current_session', fn () => AcademicSession::current()->first()?->only(['id', 'name']));
        $probe('current_term', fn () => Term::current()->first()?->only(['id', 'session_id', 'name']));

        $probe('parallel_migrations', function () {
            if (! Schema::hasTable('migrations')) {
                return [];
            }

            return DB::table('migrations')
                ->where('migration', 'like', '%parallel_curriculum%')
                ->orderBy('id')
                ->get(['migration', 'batch'])
                ->map(fn ($row) => (array) $row)
                ->all();
        });

        $probe('curricula_base', fn () => ParallelCurriculum::query()
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'default_assessment_template_id', 'is_active'])
            ->toArray());

        $probe('core_eager_load', fn () => ParallelCurriculum::with([
                'defaultAssessmentTemplate',
                'classes.assessmentTemplate',
                'classes.subjectAssignments.teacher',
            ])
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (ParallelCurriculum $curriculum) => [
                'id' => $curriculum->id,
                'name' => $curriculum->name,
                'classes' => $curriculum->classes->count(),
                'assignments' => $curriculum->classes
                    ->sum(fn ($class) => $class->subjectAssignments->count()),
            ])
            ->values()
            ->all());

        $probe('subjects', fn () => ParallelCurriculumSubject::query()->count());
        $probe('class_subjects', fn () => ParallelCurriculumClassSubject::with(['subject', 'teacher'])
            ->limit(20)
            ->get()
            ->count());
        $probe('grades', fn () => ParallelCurriculumGrade::query()->count());
        $probe('arms', fn () => ParallelCurriculumClassArm::query()->count());
        $probe('arm_teachers', fn () => ParallelCurriculumArmSubjectTeacher::query()->count());
        $probe('enrolments', fn () => ParallelCurriculumEnrolment::query()->count());
        $probe('scores', fn () => ParallelCurriculumScore::query()->count());
        $probe('integrations', fn () => ParallelCurriculumIntegration::query()->count());
        $probe('composites', fn () => ParallelCurriculumComposite::query()->count());
        $probe('report_publications', fn () => ParallelCurriculumReportPublication::query()->count());

        $probe('staff_permission_filter', function () use ($tenantId) {
            return User::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereIn('role', User::staffRoleNames())
                ->orderBy('name')
                ->limit(100)
                ->get()
                ->filter(fn (User $person) =>
                    ! $person->isAccountant()
                    && (
                        $person->canAccessExactModule('scores')
                        || $person->canAccessExactModule('scores.entry')
                    )
                )
                ->count();
        });

        $probe('routes', function () {
            $routes = app('router')->getRoutes();

            return collect([
                'scores.index',
                'scores.broadsheet',
                'parallel-curriculum.index',
                'parallel-curriculum.student-assignments',
                'parallel-curriculum.lifecycle.index',
                'parallel-curriculum.results.index',
                'parallel-curriculum.score-sheet',
            ])->mapWithKeys(
                fn (string $name) => [$name => (bool) $routes->getByName($name)]
            )->all();
        });

        $probe('views', fn () => [
            'index' => view()->exists('parallel-curriculum.index'),
            'global_ui' => view()->exists('parallel-curriculum.partials.global-ui'),
        ]);

        // Reproduce the real workspace request and force Blade compilation.
        // This is the most important probe: it captures the exact exception that
        // currently turns /parallel-curriculum into a generic HTTP 500.
        $probe('workspace_render', function () {
            $response = app(ParallelCurriculumController::class)->index();

            if ($response instanceof \Illuminate\View\View) {
                $html = $response->render();

                return [
                    'type' => get_class($response),
                    'rendered_bytes' => strlen($html),
                ];
            }

            return [
                'type' => is_object($response) ? get_class($response) : gettype($response),
            ];
        });

        $failedDetails = collect($checks)
            ->filter(fn (array $check) => ! ($check['ok'] ?? false));

        $failed = $failedDetails->keys()->values()->all();
        $primaryFailure = $failedDetails->get('workspace_render')
            ?? $failedDetails->first();

        $summary = [
            'ok' => $failed === [],
            'diagnostic' => 'parallel-curriculum',
            'primary_failure' => $primaryFailure,
            'failed_checks' => $failed,
            'failures' => $failedDetails->all(),
            'generated_at' => now()->toIso8601String(),
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
        ];

        if ($request->boolean('compact')) {
            return response()->json(
                $summary,
                $failed === [] ? 200 : 500,
                [],
                JSON_PRETTY_PRINT
            );
        }

        return response()->json([
            ...$summary,
            'schema' => $schema,
            'checks' => $checks,
        ], $failed === [] ? 200 : 500, [], JSON_PRETTY_PRINT);
    }
}
