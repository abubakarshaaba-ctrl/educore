<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumScore;
use App\Models\ParallelCurriculumSubject;
use App\Models\Term;
use App\Services\Mobile\MobileIdempotencyService;
use App\Services\ParallelCurriculumService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParallelCurriculumScoreController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumService $parallel,
        private readonly MobileIdempotencyService $idempotency,
    ) {}

    public function teaching(Request $request)
    {
        $user = $request->user();
        $this->assertCanEnterScores($user);
        $tenantId = (int) $user->tenant_id;
        $session = AcademicSession::current()->first();
        $term = Term::current()->with('session')->first();

        if (! $this->parallel->enabledForTenant($tenantId)) {
            return response()->json([
                'contract_version' => 4,
                'generated_at' => now()->toIso8601String(),
                'term' => $term ? ['id' => $term->id, 'name' => $term->name, 'session' => $term->session?->name] : null,
                'assignments' => [],
            ]);
        }

        $assignments = ParallelCurriculumClassSubject::with([
                'curriculumClass.curriculum',
                'curriculumClass.arms',
                'subject',
            ])
            ->where('is_active', true)
            ->get()
            ->filter(fn (ParallelCurriculumClassSubject $assignment) =>
                $assignment->curriculumClass?->is_active
                && $assignment->curriculumClass?->curriculum?->is_active
                && $assignment->subject?->is_active
            )
            ->flatMap(function (ParallelCurriculumClassSubject $assignment) use ($user) {
                $arms = $assignment->curriculumClass->arms
                    ->where('is_active', true)
                    ->values();

                // Before the lifecycle migration runs, keep a legacy all-arms
                // workspace rather than disappearing from older deployments.
                if ($arms->isEmpty()) {
                    $effectiveTeacherId = $this->parallel->effectiveTeacherId(
                        $assignment,
                        null
                    );

                    if (
                        ! $this->canEnterAll($user)
                        && (int) $effectiveTeacherId !== (int) $user->id
                    ) {
                        return [];
                    }

                    return [[
                        'workspace_type' => 'parallel_curriculum',
                        'class_arm_id' => $assignment->parallel_curriculum_class_id,
                        'class_name' => trim(
                            $assignment->curriculumClass->curriculum->name.
                            ' · '.$assignment->curriculumClass->name
                        ),
                        'subject_id' => $assignment->parallel_curriculum_subject_id,
                        'subject_name' => $assignment->subject->name,
                    ]];
                }

                return $arms
                    ->filter(function (ParallelCurriculumClassArm $arm) use ($assignment, $user): bool {
                        if ($this->canEnterAll($user)) {
                            return true;
                        }

                        return (int) $this->parallel->effectiveTeacherId(
                            $assignment,
                            $arm
                        ) === (int) $user->id;
                    })
                    ->map(fn (ParallelCurriculumClassArm $arm) => [
                        'workspace_type' => 'parallel_curriculum',
                        // Parallel arm IDs live in their own API namespace so old
                        // cached class-level IDs cannot collide with real arm IDs.
                        'class_arm_id' => self::PARALLEL_ARM_API_OFFSET + (int) $arm->id,
                        'class_name' => trim(
                            $assignment->curriculumClass->curriculum->name.
                            ' · '.$assignment->curriculumClass->name.
                            ' '.$arm->name
                        ),
                        'subject_id' => $assignment->parallel_curriculum_subject_id,
                        'subject_name' => $assignment->subject->name,
                    ])
                    ->values()
                    ->all();
            })
            ->values();

        return response()->json([
            'contract_version' => 4,
            'generated_at' => now()->toIso8601String(),
            'session' => $session?->only(['id', 'name']),
            'term' => $term ? ['id' => $term->id, 'name' => $term->name, 'session' => $term->session?->name] : null,
            'assignments' => $assignments,
        ]);
    }

    public function sheet(Request $request)
    {
        $data = $request->validate([
            'class_arm_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
            'term_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $this->assertCanEnterScores($user);
        $this->assertEnabled($user);
        [$class, $arm] = $this->resolveParallelWorkspace((int) $data['class_arm_id']);
        $subject = ParallelCurriculumSubject::findOrFail($data['subject_id']);
        $this->assertAssignment($user, $class, $subject, $arm);

        $term = $this->resolveTerm($data['term_id'] ?? null);
        $components = $this->parallel->componentsForClass($class);
        abort_if($components->isEmpty(), 422, 'This parallel class has no usable Assessment Template.');

        $students = $this->enrolmentsFor($class, $term, $arm);
        $lockedStudents = $students->mapWithKeys(fn (ParallelCurriculumEnrolment $enrolment) => [
            (int) $enrolment->student_id => $this->parallel->scoreEntryLocked($enrolment, $term),
        ]);
        $records = $this->scoresFor($class, $subject, $term, $students);
        $byCell = $records->keyBy(fn (ParallelCurriculumScore $score) =>
            $score->student_id.':'.$score->assessment_template_component_id
        );

        return response()->json([
            'contract_version' => 4,
            'workspace_type' => 'parallel_curriculum',
            'generated_at' => now()->toIso8601String(),
            'version' => $this->sheetVersion($students, $components, $records, $lockedStudents),
            'locked' => $students->isNotEmpty() && $lockedStudents->every(fn ($locked) => $locked),
            'lock_reason' => $students->isNotEmpty() && $lockedStudents->every(fn ($locked) => $locked)
                ? 'A published result depends on these source scores. Unpublish the relevant parallel or conventional result before changing them.'
                : null,
            'class' => [
                'id' => (int) $data['class_arm_id'],
                'name' => trim($class->curriculum->name.' · '.$class->name.($arm ? ' '.$arm->name : '')),
            ],
            'subject' => ['id' => $subject->id, 'name' => $subject->name],
            'term' => ['id' => $term->id, 'name' => $term->name, 'session' => $term->session?->name],
            'assessment_types' => $components->map(fn ($component) => [
                'id' => $component->id,
                'name' => $component->name,
                'max' => (float) $component->weight_percentage,
                'is_exam' => false,
                'is_split' => false,
                'objective_max' => null,
                'theory_max' => null,
                'objective_source_available' => null,
            ])->values(),
            'students' => $students->map(function (ParallelCurriculumEnrolment $enrolment) use ($components, $byCell, $lockedStudents) {
                $student = $enrolment->student;
                $locked = (bool) $lockedStudents->get((int) $enrolment->student_id, false);
                $cells = $components->mapWithKeys(function ($component) use ($student, $byCell, $locked) {
                    $record = $byCell->get($student->id.':'.$component->id);

                    return [(string) $component->id => [
                        'total' => $record?->score,
                        'value' => $record?->score,
                        'objective_score' => null,
                        'theory_score' => null,
                        'locked' => $locked,
                        'source' => 'parallel_curriculum_entry',
                    ]];
                });

                return [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'admission_number' => $student->admission_number,
                    'scores' => (object) $cells->all(),
                ];
            })->values(),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'class_arm_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
            'version' => ['required', 'string', 'max:128'],
            'request_id' => ['required', 'uuid'],
            'scores' => ['required', 'array', 'min:1'],
        ]);

        $user = $request->user();
        $this->assertCanEnterScores($user);
        $this->assertEnabled($user);
        [$class, $arm] = $this->resolveParallelWorkspace((int) $data['class_arm_id']);
        $subject = ParallelCurriculumSubject::findOrFail($data['subject_id']);
        $this->assertAssignment($user, $class, $subject);
        $term = $this->resolveTerm((int) $data['term_id']);

        $response = $this->idempotency->execute(
            $user,
            "parallel-scores.{$data['class_arm_id']}.{$subject->id}.{$term->id}.save",
            $data['request_id'],
            $data,
            function () use ($data, $class, $arm, $subject, $term, $user): array {
                $enrolments = $this->enrolmentsFor($class, $term, $arm);
                $components = $this->parallel->componentsForClass($class);
                abort_if($components->isEmpty(), 422, 'This parallel class has no usable Assessment Template.');

                $lockedStudents = $enrolments->mapWithKeys(fn (ParallelCurriculumEnrolment $enrolment) => [
                    (int) $enrolment->student_id => $this->parallel->scoreEntryLocked($enrolment, $term),
                ]);

                $saved = DB::transaction(function () use (
                    $data, $class, $subject, $term, $user, $enrolments, $components, $lockedStudents
                ): int {
                    $records = $this->scoresFor($class, $subject, $term, $enrolments, true);
                    $serverVersion = $this->sheetVersion($enrolments, $components, $records, $lockedStudents);
                    abort_unless(
                        hash_equals($serverVersion, (string) $data['version']),
                        409,
                        'Parallel scores changed on the server. Reload the sheet before saving your draft.'
                    );

                    $enrolmentsByStudent = $enrolments->keyBy('student_id');
                    $componentsById = $components->keyBy('id');
                    $errors = [];

                    foreach ($data['scores'] as $studentId => $values) {
                        if (! $enrolmentsByStudent->has((int) $studentId) || ! is_array($values)) {
                            $errors["scores.{$studentId}"][] = 'The student is not active in this parallel class.';
                            continue;
                        }

                        if ((bool) $lockedStudents->get((int) $studentId, false)) {
                            $errors["scores.{$studentId}"][] =
                                'This student\'s conventional report card is published. Unpublish it before changing parallel source scores.';
                            continue;
                        }

                        foreach ($values as $componentId => $value) {
                            $component = $componentsById->get((int) $componentId);
                            $path = "scores.{$studentId}.{$componentId}";
                            if (! $component) {
                                $errors[$path][] = 'This assessment component is not part of the parallel class template.';
                                continue;
                            }
                            if ($value === null || $value === '') {
                                continue;
                            }
                            if (! is_numeric($value)) {
                                $errors[$path][] = 'Enter a numeric score.';
                                continue;
                            }
                            $maximum = (float) $component->weight_percentage;
                            if ((float) $value < 0 || (float) $value > $maximum) {
                                $errors[$path][] = "Enter a score between 0 and {$maximum}.";
                            }
                        }
                    }

                    if ($errors !== []) {
                        throw ValidationException::withMessages($errors);
                    }

                    $savedCount = 0;
                    foreach ($data['scores'] as $studentId => $values) {
                        foreach ($values as $componentId => $value) {
                            $component = $componentsById->get((int) $componentId);
                            $key = [
                                'tenant_id' => $class->tenant_id,
                                'parallel_curriculum_id' => $class->parallel_curriculum_id,
                                'student_id' => (int) $studentId,
                                'parallel_curriculum_subject_id' => $subject->id,
                                'assessment_template_component_id' => $component->id,
                                'term_id' => $term->id,
                            ];

                            if ($value === null || $value === '') {
                                $savedCount += ParallelCurriculumScore::where($key)->delete();
                                continue;
                            }

                            ParallelCurriculumScore::updateOrCreate($key, [
                                'parallel_curriculum_class_id' => $class->id,
                                'session_id' => $term->session_id,
                                'entered_by' => $user->id,
                                'score' => round((float) $value, 2),
                                'entered_at' => now(),
                            ]);
                            $savedCount++;
                        }
                    }

                    return $savedCount;
                });

                foreach ($enrolments as $enrolment) {
                    $this->parallel->syncStudent($enrolment, $term, false);
                }

                $freshRecords = $this->scoresFor($class, $subject, $term, $enrolments);

                return [
                    'message' => "Saved {$saved} parallel curriculum scores.",
                    'saved' => $saved,
                    'request_id' => $data['request_id'],
                    'version' => $this->sheetVersion($enrolments, $components, $freshRecords, $lockedStudents),
                    'saved_at' => now()->toIso8601String(),
                ];
            }
        );

        return response()->json($response);
    }

    private function assertCanEnterScores($user): void
    {
        abort_if($user->isAccountant(), 403, 'Accountants cannot enter academic scores.');

        abort_unless(
            $user->isSuperAdmin()
                || $user->canAccessExactModule('scores')
                || $user->canAccessExactModule('scores.entry'),
            403,
            'You do not have permission to enter parallel curriculum scores.'
        );
    }

    private function assertEnabled($user): void
    {
        abort_unless(
            $user->tenant_id && $this->parallel->enabledForTenant((int) $user->tenant_id),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );
    }

    private function assertAssignment(
        $user,
        ParallelCurriculumClass $class,
        ParallelCurriculumSubject $subject,
        ?ParallelCurriculumClassArm $arm = null
    ): void {
        abort_unless(
            (int) $subject->parallel_curriculum_id === (int) $class->parallel_curriculum_id,
            422,
            'The selected subject does not belong to this parallel curriculum.'
        );

        if ($this->canEnterAll($user)) {
            return;
        }

        $assignment = ParallelCurriculumClassSubject::where(
                'parallel_curriculum_class_id',
                $class->id
            )
            ->where('parallel_curriculum_subject_id', $subject->id)
            ->where('is_active', true)
            ->first();

        $allowed = $assignment
            && (int) $this->parallel->effectiveTeacherId($assignment, $arm)
                === (int) $user->id;

        abort_unless(
            $allowed,
            403,
            'You are not assigned to this parallel curriculum class arm and subject.'
        );
    }

    private function canEnterAll($user): bool
    {
        return $user->isSuperAdmin() || $user->canAccessExactModule('scores');
    }

    private function resolveTerm(?int $termId): Term
    {
        $term = $termId ? Term::with('session')->find($termId) : Term::current()->with('session')->first();
        abort_unless($term, 422, $termId ? 'The selected term is not available for this school.' : 'No current term is set.');

        return $term;
    }

    private function enrolmentsFor(
        ParallelCurriculumClass $class,
        Term $term,
        ?ParallelCurriculumClassArm $arm = null
    ): Collection {
        return ParallelCurriculumEnrolment::with(['student', 'curriculumClassArm'])
            ->where('parallel_curriculum_class_id', $class->id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
            ->when(
                $arm,
                fn ($query) => $query->where(
                    'parallel_curriculum_class_arm_id',
                    $arm->id
                )
            )
            ->get()
            ->filter(fn (ParallelCurriculumEnrolment $enrolment) => $enrolment->student?->status === 'active')
            ->sortBy(fn (ParallelCurriculumEnrolment $enrolment) =>
                strtolower(($enrolment->student?->last_name ?? '').' '.($enrolment->student?->first_name ?? ''))
            )
            ->values();
    }

    /**
     * API v4 uses a namespaced synthetic ID for a real parallel class arm.
     * Values below the offset remain valid legacy class-level workspaces.
     *
     * @return array{0: ParallelCurriculumClass, 1: ?ParallelCurriculumClassArm}
     */
    private function resolveParallelWorkspace(int $workspaceId): array
    {
        if ($workspaceId >= self::PARALLEL_ARM_API_OFFSET) {
            $armId = $workspaceId - self::PARALLEL_ARM_API_OFFSET;

            $arm = ParallelCurriculumClassArm::with([
                    'curriculumClass.curriculum',
                    'curriculumClass.assessmentTemplate',
                    'curriculumClass.curriculum.defaultAssessmentTemplate',
                ])
                ->where('is_active', true)
                ->findOrFail($armId);

            return [$arm->curriculumClass, $arm];
        }

        $class = ParallelCurriculumClass::with([
                'curriculum',
                'assessmentTemplate',
                'curriculum.defaultAssessmentTemplate',
            ])
            ->findOrFail($workspaceId);

        return [$class, null];
    }

    private function scoresFor(
        ParallelCurriculumClass $class,
        ParallelCurriculumSubject $subject,
        Term $term,
        Collection $enrolments,
        bool $lock = false,
    ): Collection {
        if ($enrolments->isEmpty()) {
            return collect();
        }

        $query = ParallelCurriculumScore::where('parallel_curriculum_class_id', $class->id)
            ->where('parallel_curriculum_subject_id', $subject->id)
            ->where('term_id', $term->id)
            ->whereIn('student_id', $enrolments->pluck('student_id'))
            ->orderBy('student_id')
            ->orderBy('assessment_template_component_id');

        return $lock ? $query->lockForUpdate()->get() : $query->get();
    }

    private const PARALLEL_ARM_API_OFFSET = 1000000000;

    private function sheetVersion(
        Collection $enrolments,
        Collection $components,
        Collection $records,
        ?Collection $lockedStudents = null,
    ): string {
        return hash('sha256', json_encode([
            'students' => $enrolments->pluck('student_id')->map(fn ($id) => (int) $id)->all(),
            'components' => $components->map(fn ($component) => [
                $component->id,
                (float) $component->weight_percentage,
                $component->name,
            ])->all(),
            'locks' => ($lockedStudents ?? collect())->map(fn ($locked) => (bool) $locked)->all(),
            'scores' => $records->map(fn (ParallelCurriculumScore $score) => [
                $score->id,
                $score->student_id,
                $score->assessment_template_component_id,
                $score->score,
                $score->updated_at?->format('Y-m-d H:i:s.u'),
            ])->all(),
        ], JSON_PRESERVE_ZERO_FRACTION));
    }
}
