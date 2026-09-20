<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\AssessmentType;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\GradingSystem;
use App\Models\ReportCardPublication;
use App\Models\Score;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Term;
use App\Services\Mobile\MobileIdempotencyService;
use App\Services\Scores\ObjectiveScoreResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ScoreController extends Controller
{
    private const ACADEMIC_SCORE_ROLES = [
        'admin',
        'principal',
        'head',
        'head_teacher',
        'head_of_school',
        'head_of_schools',
        'vice_principal',
        'vice_principal_academics',
        'vice_principal_administration',
        'assistant_principal',
        'assistant_head',
        'academic_head',
        'academic_administrator',
        'director_of_studies',
        'hod',
        'head_of_department',
        'teacher',
        'subject_teacher',
        'class_teacher',
        'form_teacher',
        'asst_form_teacher',
        'form_subject_teacher',
    ];

    private const SCORE_OVERSIGHT_ROLES = [
        'admin',
        'principal',
        'head',
        'head_teacher',
        'head_of_school',
        'head_of_schools',
        'vice_principal',
        'vice_principal_academics',
        'vice_principal_administration',
        'assistant_principal',
        'assistant_head',
        'academic_head',
        'academic_administrator',
        'director_of_studies',
        'hod',
        'head_of_department',
    ];

    public function __construct(private readonly MobileIdempotencyService $idempotency) {}

    public function teaching(Request $request)
    {
        $user = $request->user();
        $this->assertCanEnterScores($user);
        $session = AcademicSession::current()->first();
        $term = Term::current()->with('session')->first();

        $assignments = ClassArmSubject::with(['classArm.classLevel', 'subject'])
            ->when($session, fn ($query) => $query->where('session_id', $session->id))
            ->when(! $this->canEnterAll($user), fn ($query) => $query->where('teacher_id', $user->id))
            ->get()
            ->filter(fn (ClassArmSubject $assignment) => $assignment->classArm && $assignment->subject)
            ->unique(fn (ClassArmSubject $assignment) => $assignment->class_arm_id.':'.$assignment->subject_id)
            ->map(fn (ClassArmSubject $assignment) => [
                'class_arm_id' => $assignment->class_arm_id,
                'class_name' => $this->className($assignment->classArm),
                'subject_id' => $assignment->subject_id,
                'subject_name' => $assignment->subject->name,
            ])->values();

        return response()->json([
            'contract_version' => 2,
            'generated_at' => now()->toIso8601String(),
            'session' => $session?->only(['id', 'name']),
            'term' => $term ? ['id' => $term->id, 'name' => $term->name, 'session' => $term->session?->name] : null,
            'assignments' => $assignments,
        ]);
    }

    public function sheet(Request $request, ObjectiveScoreResolver $resolver)
    {
        $data = $request->validate([
            'class_arm_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
            'term_id' => ['nullable', 'integer'],
        ]);
        $user = $request->user();
        $this->assertCanEnterScores($user);
        $this->assertTeaches($user, (int) $data['class_arm_id'], (int) $data['subject_id']);

        $term = $this->resolveTerm($data['term_id'] ?? null);
        $classArm = ClassArm::with('classLevel')->findOrFail($data['class_arm_id']);
        $subject = Subject::findOrFail($data['subject_id']);
        $students = $this->studentsFor($classArm->id);
        $types = $this->assessmentTypesFor($term->id);
        $records = $this->scoresFor($students, $subject->id, $term->id);
        $published = $this->isPublished($classArm->id, $term->id);
        $recordsByCell = $records->keyBy(fn (Score $score) => $score->student_id.':'.$score->assessment_type_id);
        $objective = $this->objectiveContext($students, $types, $classArm, $subject, $term, $resolver);

        return response()->json([
            'contract_version' => 2,
            'generated_at' => now()->toIso8601String(),
            'version' => $this->sheetVersion($students, $types, $records, $published),
            'locked' => $published,
            'lock_reason' => $published ? 'These results are published. Unpublish the report cards before scores can change.' : null,
            'class' => ['id' => $classArm->id, 'name' => $this->className($classArm)],
            'subject' => ['id' => $subject->id, 'name' => $subject->name],
            'term' => ['id' => $term->id, 'name' => $term->name, 'session' => $term->session?->name],
            'assessment_types' => $types->map(fn (AssessmentType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'max' => (float) $type->weight_percentage,
                'is_exam' => (bool) $type->is_exam,
                'is_split' => $type->isSplit(),
                'objective_max' => $type->objective_max,
                'theory_max' => $type->theory_max,
                'input_mode' => $type->isSplit() ? 'theory' : 'total',
                'objective_source_available' => $type->isSplit() ? (bool) ($objective['exams'][$type->id] ?? false) : null,
            ])->values(),
            'students' => $students->map(function (Student $student) use ($types, $recordsByCell, $objective, $published) {
                $cells = $types->mapWithKeys(function (AssessmentType $type) use ($student, $recordsByCell, $objective, $published) {
                    $record = $recordsByCell->get($student->id.':'.$type->id);
                    $objectiveScore = $type->isSplit()
                        ? ($objective['scores'][$student->id][$type->id] ?? $record?->objective_score)
                        : null;

                    return [(string) $type->id => [
                        'total' => $record?->score,
                        'value' => $type->isSplit() ? $record?->theory_score : $record?->score,
                        'objective_score' => $objectiveScore,
                        'theory_score' => $record?->theory_score,
                        'locked' => $published || (bool) $record?->is_source_locked,
                        'source' => $record?->score_source,
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

    public function save(Request $request, ObjectiveScoreResolver $resolver)
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
        $this->assertTeaches($user, (int) $data['class_arm_id'], (int) $data['subject_id']);
        $term = $this->resolveTerm((int) $data['term_id']);
        $classArm = ClassArm::findOrFail($data['class_arm_id']);
        Subject::findOrFail($data['subject_id']);

        $response = $this->idempotency->execute(
            $user,
            "scores.{$classArm->id}.{$data['subject_id']}.{$term->id}.save",
            $data['request_id'],
            $data,
            function () use ($data, $term, $classArm, $user, $resolver): array {
                $saved = DB::transaction(function () use ($data, $term, $classArm, $user, $resolver) {
                    abort_if($this->isPublished($classArm->id, $term->id), 423, 'These results are published and locked. Unpublish the report cards before changing scores.');
                    $students = $this->studentsFor($classArm->id);
                    $types = $this->assessmentTypesFor($term->id);
                    $records = $this->scoresFor($students, (int) $data['subject_id'], $term->id, true);
                    $serverVersion = $this->sheetVersion($students, $types, $records, false);
                    abort_unless(hash_equals($serverVersion, (string) $data['version']), 409, 'Scores changed on the server. Reload the sheet before saving your draft.');

                    $studentsById = $students->keyBy('id');
                    $typesById = $types->keyBy('id');
                    $recordsByCell = $records->keyBy(fn (Score $score) => $score->student_id.':'.$score->assessment_type_id);
                    $errors = [];
                    foreach ($data['scores'] as $studentId => $values) {
                        if (! $studentsById->has((int) $studentId) || ! is_array($values)) {
                            $errors["scores.{$studentId}"][] = 'The selected student is not active in this class.';

                            continue;
                        }
                        foreach ($values as $typeId => $value) {
                            $type = $typesById->get((int) $typeId);
                            $path = "scores.{$studentId}.{$typeId}";
                            if (! $type) {
                                $errors[$path][] = 'The selected assessment does not belong to this term.';

                                continue;
                            }
                            if ($value === null || $value === '') {
                                continue;
                            }
                            if (! is_numeric($value)) {
                                $errors[$path][] = 'Enter a numeric score.';

                                continue;
                            }
                            $maximum = (float) ($type->isSplit() ? $type->theory_max : $type->weight_percentage);
                            if ((float) $value < 0 || (float) $value > $maximum) {
                                $errors[$path][] = "Enter a score between 0 and {$maximum}.";
                            }
                            if ((bool) $recordsByCell->get($studentId.':'.$typeId)?->is_source_locked) {
                                $errors[$path][] = 'This score is controlled by its source and cannot be edited manually.';
                            }
                        }
                    }
                    if ($errors !== []) {
                        throw ValidationException::withMessages($errors);
                    }

                    $savedCount = 0;
                    foreach ($data['scores'] as $studentId => $values) {
                        $student = $studentsById->get((int) $studentId);
                        foreach ($values as $typeId => $value) {
                            if ($value === null || $value === '') {
                                continue;
                            }
                            $type = $typesById->get((int) $typeId);
                            $attributes = ['session_id' => $term->session_id, 'entered_by' => $user->id, 'entered_at' => now()];
                            if ($type->isSplit()) {
                                $exam = $resolver->findExam($classArm->id, (int) $data['subject_id'], $term->id, $type);
                                $objective = $exam ? $resolver->resolve($student, $exam, $type) : null;
                                $theory = (float) $value;
                                $attributes += [
                                    'score' => min(($objective ?? 0) + $theory, (float) $type->weight_percentage),
                                    'objective_score' => $objective,
                                    'theory_score' => $theory,
                                    'cbt_exam_id' => $exam?->id,
                                ];
                            } else {
                                $attributes += ['score' => (float) $value, 'objective_score' => null, 'theory_score' => null, 'cbt_exam_id' => null];
                            }
                            Score::updateOrCreate([
                                'student_id' => $student->id,
                                'subject_id' => (int) $data['subject_id'],
                                'assessment_type_id' => $type->id,
                                'term_id' => $term->id,
                            ], $attributes);
                            $savedCount++;
                        }
                    }

                    return $savedCount;
                });

                $students = $this->studentsFor($classArm->id);
                $types = $this->assessmentTypesFor($term->id);
                $records = $this->scoresFor($students, (int) $data['subject_id'], $term->id);

                return [
                    'message' => "Saved {$saved} scores.", 'saved' => $saved, 'request_id' => $data['request_id'],
                    'version' => $this->sheetVersion($students, $types, $records, false), 'saved_at' => now()->toIso8601String(),
                ];
            },
        );

        return response()->json($response);
    }

    public function cumulativeBroadsheet(Request $request)
    {
        $user = $request->user();
        $this->assertCanViewCumulative($user);

        $tenantId = (int) $user->tenant_id;
        $classArms = $this->canEnterAll($user)
            ? ClassArm::with('classLevel')
                ->where('tenant_id', $tenantId)
                ->orderBy('class_level_id')
                ->orderBy('name')
                ->get()
            : ClassArm::with('classLevel')
                ->where('tenant_id', $tenantId)
                ->where(function ($query) use ($user): void {
                    $query->where('form_tutor_id', $user->id)
                        ->orWhereHas('teacherSubjectAssignments', fn ($subjects) =>
                            $subjects->where('class_arm_subjects.teacher_id', $user->id)
                        );
                })
                ->orderBy('class_level_id')
                ->orderBy('name')
                ->get();

        $sessions = AcademicSession::where('tenant_id', $tenantId)
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $classId = (int) $request->integer('class_arm_id');
        $sessionId = (int) $request->integer('session_id');
        $classArm = $classId ? $classArms->firstWhere('id', $classId) : null;
        $session = $sessionId ? $sessions->firstWhere('id', $sessionId) : null;

        abort_if($classId && ! $classArm, 403, 'You cannot view the selected class.');
        abort_if($sessionId && ! $session, 404, 'The selected academic session is unavailable.');

        $data = ($classArm && $session)
            ? $this->buildCumulativeBroadsheetData($classArm, $session)
            : [
                'terms' => collect(),
                'subjects' => collect(),
                'matrix' => collect(),
                'subjectStats' => collect(),
            ];

        return response()->json([
            'contract_version' => 1,
            'selected' => [
                'class_arm_id' => $classArm?->id,
                'session_id' => $session?->id,
            ],
            'class_arms' => $classArms->map(fn (ClassArm $arm) => [
                'id' => (int) $arm->id,
                'name' => trim(($arm->classLevel?->name ?? '').' '.$arm->name),
                'class_level_id' => (int) $arm->class_level_id,
            ])->values(),
            'sessions' => $sessions->map(fn (AcademicSession $item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'is_current' => (bool) $item->is_current,
            ])->values(),
            'terms' => $data['terms']->map(fn (Term $term) => [
                'id' => (int) $term->id,
                'name' => (string) $term->name,
            ])->values(),
            'subjects' => $data['subjects']->map(fn (Subject $subject) => [
                'id' => (int) $subject->id,
                'name' => (string) $subject->name,
                'code' => $subject->code,
                'stats' => $data['subjectStats']->get((int) $subject->id),
            ])->values(),
            'rows' => $data['matrix']->map(fn (array $row) => [
                'student_id' => (int) $row['student']->id,
                'student_name' => $row['student']->full_name,
                'admission_number' => $row['student']->admission_number,
                'subjects' => $row['subjects'],
                'term_averages' => $row['term_averages'],
                'total' => (float) $row['total'],
                'average' => $row['average'] !== null ? (float) $row['average'] : null,
                'position' => $row['position'],
            ])->values(),
        ]);
    }

    public function cumulativeBroadsheetPdf(Request $request)
    {
        $user = $request->user();
        $this->assertCanViewCumulative($user);
        $tenantId = (int) $user->tenant_id;

        $data = $request->validate([
            'class_arm_id' => ['required', 'integer'],
            'session_id' => ['required', 'integer'],
        ]);

        $classArm = ClassArm::with('classLevel')
            ->where('tenant_id', $tenantId)
            ->findOrFail((int) $data['class_arm_id']);
        $session = AcademicSession::where('tenant_id', $tenantId)
            ->findOrFail((int) $data['session_id']);

        $this->assertCanViewCumulativeClass($user, $classArm);

        $broadsheet = $this->buildCumulativeBroadsheetData(
            $classArm,
            $session
        );

        abort_unless(
            $broadsheet['matrix']->isNotEmpty(),
            422,
            'No cumulative score data is available for the selected class and session.'
        );

        $tenant = $user->tenant;
        $logoAbsPath = null;
        if (! empty($tenant?->logo_path)) {
            $cleanPath = preg_replace(
                '#^storage/#',
                '',
                ltrim($tenant->logo_path, '/')
            );
            $candidate = storage_path('app/public/'.$cleanPath);
            if (file_exists($candidate)) {
                $logoAbsPath = $candidate;
            }
        }

        $filename = 'Cumulative_Broadsheet_'.
            preg_replace(
                '/[^A-Za-z0-9_-]+/',
                '_',
                trim(($classArm->classLevel?->name ?? 'Class').' '.$classArm->name)
            ).'_'.preg_replace(
                '/[^A-Za-z0-9_-]+/',
                '_',
                (string) $session->name
            ).'.pdf';

        return Pdf::loadView(
            'scores.cumulative-broadsheet-pdf',
            array_merge(
                compact(
                    'classArm',
                    'session',
                    'tenant',
                    'logoAbsPath'
                ),
                $broadsheet
            )
        )->setPaper('a4', 'landscape')->download($filename);
    }

    private function buildCumulativeBroadsheetData(
        ClassArm $classArm,
        AcademicSession $session
    ): array {
        $tenantId = (int) $classArm->tenant_id;
        $terms = Term::with('session')
            ->where('tenant_id', $tenantId)
            ->where('session_id', $session->id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $studentIds = Schema::hasTable('student_enrollments')
            ? StudentEnrollment::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('class_arm_id', $classArm->id)
                ->where('session_id', $session->id)
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
            : collect();

        $students = $studentIds->isNotEmpty()
            ? Student::where('tenant_id', $tenantId)
                ->whereIn('id', $studentIds)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
            : Student::where('tenant_id', $tenantId)
                ->where('current_class_arm_id', $classArm->id)
                ->where('status', Student::STATUS_ACTIVE)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

        $studentIds = $students->pluck('id');
        $termIds = $terms->pluck('id');
        $allScores = ($studentIds->isNotEmpty() && $termIds->isNotEmpty())
            ? Score::whereIn('student_id', $studentIds)
                ->whereIn('term_id', $termIds)
                ->get()
            : collect();

        $subjects = Subject::whereIn(
                'id',
                $allScores->pluck('subject_id')->unique()
            )
            ->orderBy('name')
            ->get();

        $grades = GradingSystem::where(
                'class_level_id',
                $classArm->class_level_id
            )
            ->get();

        $matrix = $students->map(function (Student $student) use (
            $terms,
            $subjects,
            $allScores,
            $grades
        ): array {
            $subjectRows = [];
            $termAverages = [];

            foreach ($terms as $term) {
                $termScores = [];
                foreach ($subjects as $subject) {
                    $scores = $allScores
                        ->where('student_id', $student->id)
                        ->where('subject_id', $subject->id)
                        ->where('term_id', $term->id);
                    if ($scores->isNotEmpty()) {
                        $termScores[] = (float) $scores->sum('score');
                    }
                }
                $termAverages[(int) $term->id] = $termScores !== []
                    ? round(array_sum($termScores) / count($termScores), 1)
                    : null;
            }

            foreach ($subjects as $subject) {
                $termTotals = [];
                foreach ($terms as $term) {
                    $scores = $allScores
                        ->where('student_id', $student->id)
                        ->where('subject_id', $subject->id)
                        ->where('term_id', $term->id);
                    $termTotals[(int) $term->id] = $scores->isNotEmpty()
                        ? round((float) $scores->sum('score'), 1)
                        : null;
                }

                $available = collect($termTotals)
                    ->filter(fn ($score) => $score !== null)
                    ->map(fn ($score) => (float) $score);
                $average = $available->isNotEmpty()
                    ? round((float) $available->avg(), 1)
                    : null;
                $grade = $average === null
                    ? null
                    : $grades->first(fn ($item) =>
                        $average >= (float) $item->min_score
                        && $average <= (float) $item->max_score
                    );

                $subjectRows[(int) $subject->id] = [
                    'term_totals' => $termTotals,
                    'average' => $average,
                    'grade' => $grade?->grade_letter ?? '—',
                    'is_pass' => (bool) ($grade?->is_pass_grade ?? false),
                ];
            }

            $availableAverages = collect($subjectRows)
                ->pluck('average')
                ->filter(fn ($score) => $score !== null)
                ->map(fn ($score) => (float) $score);

            return [
                'student' => $student,
                'subjects' => $subjectRows,
                'term_averages' => $termAverages,
                'total' => round((float) $availableAverages->sum(), 1),
                'average' => $availableAverages->isNotEmpty()
                    ? round((float) $availableAverages->avg(), 1)
                    : null,
                'position' => null,
            ];
        })
            ->filter(fn (array $row) => $row['average'] !== null)
            ->sortByDesc('average')
            ->values();

        $previousAverage = null;
        $previousPosition = null;
        $matrix = $matrix->map(function (
            array $row,
            int $index
        ) use (&$previousAverage, &$previousPosition): array {
            $position = $index + 1;
            $average = (float) $row['average'];
            if (
                $previousAverage !== null
                && abs($average - $previousAverage) < 0.0001
            ) {
                $position = $previousPosition;
            }

            $row['position'] = $position;
            $previousAverage = $average;
            $previousPosition = $position;

            return $row;
        });

        $subjectStats = $subjects->mapWithKeys(function (Subject $subject) use ($matrix): array {
            $scores = $matrix
                ->map(fn (array $row) =>
                    $row['subjects'][(int) $subject->id]['average'] ?? null
                )
                ->filter(fn ($score) => $score !== null)
                ->map(fn ($score) => (float) $score)
                ->values();

            return [
                (int) $subject->id => [
                    'highest' => $scores->isNotEmpty()
                        ? round((float) $scores->max(), 1)
                        : null,
                    'lowest' => $scores->isNotEmpty()
                        ? round((float) $scores->min(), 1)
                        : null,
                    'avg' => $scores->isNotEmpty()
                        ? round((float) $scores->avg(), 1)
                        : null,
                ],
            ];
        });

        return compact('terms', 'subjects', 'matrix', 'subjectStats');
    }

    private function assertCanViewCumulative($user): void
    {
        abort_unless($user && $user->tenant_id, 403);
        abort_unless(
            $this->canEnterAll($user)
                || $user->canAccessExactModule('scores.view')
                || ClassArm::where('tenant_id', $user->tenant_id)
                    ->where('form_tutor_id', $user->id)
                    ->exists()
                || ClassArmSubject::where('tenant_id', $user->tenant_id)
                    ->where('teacher_id', $user->id)
                    ->exists(),
            403,
            'You do not have access to cumulative broadsheets.'
        );
    }

    private function assertCanViewCumulativeClass($user, ClassArm $classArm): void
    {
        if ($this->canEnterAll($user) || $user->canAccessExactModule('scores.view')) {
            return;
        }

        $allowed = (int) ($classArm->form_tutor_id ?? 0) === (int) $user->id
            || ClassArmSubject::where('tenant_id', $user->tenant_id)
                ->where('class_arm_id', $classArm->id)
                ->where('teacher_id', $user->id)
                ->exists();

        abort_unless($allowed, 403, 'You cannot view this class cumulative broadsheet.');
    }

    private function assertCanEnterScores($user): void
    {
        abort_if($user->isAccountant(), 403, 'Accountants cannot enter academic scores.');

        abort_unless(
            $this->isAcademicScoreStaff($user)
                || $user->canAccessExactModule('scores')
                || $user->canAccessExactModule('scores.entry'),
            403,
            'You do not have permission to enter scores.'
        );
    }

    private function assertTeaches($user, int $classArmId, int $subjectId): void
    {
        if ($this->canEnterAll($user)) {
            return;
        }
        $session = AcademicSession::current()->first();
        $allowed = ClassArmSubject::where('teacher_id', $user->id)->where('class_arm_id', $classArmId)
            ->where('subject_id', $subjectId)->when($session, fn ($query) => $query->where('session_id', $session->id))->exists();
        abort_unless($allowed, 403, 'You are not assigned to teach this subject in this class.');
    }

    private function canEnterAll($user): bool
    {
        return $user->isSuperAdmin()
            || $user->canAccessExactModule('scores')
            || $this->hasScoreRole($user, self::SCORE_OVERSIGHT_ROLES);
    }

    private function isAcademicScoreStaff($user): bool
    {
        return $this->hasScoreRole($user, self::ACADEMIC_SCORE_ROLES);
    }

    private function hasScoreRole($user, array $roles): bool
    {
        $roleKey = $user->roleKey();
        if ($roleKey !== null && in_array($roleKey, $roles, true)) {
            return true;
        }

        return $user->getRoleNames()
            ->map(fn ($role) => strtolower(str_replace(['-', ' '], '_', trim((string) $role))))
            ->contains(fn ($role) => in_array($role, $roles, true));
    }

    private function resolveTerm(?int $termId): Term
    {
        $term = $termId ? Term::with('session')->find($termId) : Term::current()->with('session')->first();
        abort_unless($term, 422, $termId ? 'The selected term is not available for this school.' : 'No current term is set.');

        return $term;
    }

    private function studentsFor(int $classArmId): Collection
    {
        return Student::where('current_class_arm_id', $classArmId)->where('status', Student::STATUS_ACTIVE)
            ->orderBy('last_name')->orderBy('first_name')->get();
    }

    private function assessmentTypesFor(int $termId): Collection
    {
        return AssessmentType::where('term_id', $termId)->orderBy('is_exam')->orderBy('weight_percentage')->orderBy('name')->get();
    }

    private function scoresFor(Collection $students, int $subjectId, int $termId, bool $lock = false): Collection
    {
        if ($students->isEmpty()) {
            return collect();
        }
        $query = Score::whereIn('student_id', $students->pluck('id'))->where('subject_id', $subjectId)
            ->where('term_id', $termId)->orderBy('student_id')->orderBy('assessment_type_id');

        return $lock ? $query->lockForUpdate()->get() : $query->get();
    }

    private function isPublished(int $classArmId, int $termId): bool
    {
        return ReportCardPublication::where('class_arm_id', $classArmId)->where('term_id', $termId)->where('status', 'published')->exists();
    }

    private function objectiveContext(Collection $students, Collection $types, ClassArm $classArm, Subject $subject, Term $term, ObjectiveScoreResolver $resolver): array
    {
        $scores = [];
        $exams = [];
        foreach ($types->filter->isSplit() as $type) {
            $exam = $resolver->findExam($classArm->id, $subject->id, $term->id, $type);
            $exams[$type->id] = (bool) $exam;
            if (! $exam) {
                continue;
            }
            foreach ($students as $student) {
                $scores[$student->id][$type->id] = $resolver->resolve($student, $exam, $type);
            }
        }

        return compact('scores', 'exams');
    }

    private function sheetVersion(Collection $students, Collection $types, Collection $records, bool $published): string
    {
        return hash('sha256', json_encode([
            'published' => $published,
            'students' => $students->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'types' => $types->map(fn (AssessmentType $type) => [$type->id, $type->weight_percentage, $type->objective_max, $type->theory_max])->all(),
            'scores' => $records->map(fn (Score $score) => [
                $score->id, $score->student_id, $score->assessment_type_id, $score->score, $score->objective_score,
                $score->theory_score, (bool) $score->is_source_locked, $score->updated_at?->format('Y-m-d H:i:s.u'),
            ])->all(),
        ], JSON_PRESERVE_ZERO_FRACTION));
    }

    private function className(ClassArm $classArm): string
    {
        return trim(($classArm->classLevel?->name ?? '').' '.$classArm->name);
    }
}
