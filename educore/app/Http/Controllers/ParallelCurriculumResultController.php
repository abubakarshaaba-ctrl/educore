<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\ParallelCurriculumResultComment;
use App\Models\Student;
use App\Models\Term;
use App\Services\GuardianNotifier;
use App\Services\ParallelCurriculumResultService;
use App\Services\ParallelCurriculumService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParallelCurriculumResultController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumService $parallel,
        private readonly ParallelCurriculumResultService $results,
    ) {}

    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function assertAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $this->parallel->canManageLifecycle($user),
            403,
            'You do not have permission to manage parallel curriculum results.'
        );

        abort_unless(
            $this->parallel->enabledForTenant($this->tenantId()),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );
    }

    public function index(Request $request)
    {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        $terms = Term::with('session')->orderByDesc('id')->get();
        $classes = ParallelCurriculumClass::with('curriculum')
            ->where('is_active', true)
            ->whereHas('curriculum', fn ($query) => $query->where('is_active', true))
            ->orderBy('parallel_curriculum_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $currentTerm = Term::current()->with('session')->first();
        $termId = (int) ($request->integer('term_id') ?: ($currentTerm?->id ?? $terms->first()?->id ?? 0));
        $classId = (int) ($request->integer('class_id') ?: ($classes->first()?->id ?? 0));

        $term = $termId ? $terms->firstWhere('id', $termId) : null;
        $class = $classId ? $classes->firstWhere('id', $classId) : null;

        abort_if($termId && ! $term, 404, 'The selected term is unavailable.');
        abort_if($classId && ! $class, 404, 'The selected parallel class is unavailable.');

        $report = ($term && $class) ? $this->results->classReport($class, $term) : null;

        return view('parallel-curriculum.results.index', compact(
            'terms',
            'classes',
            'currentTerm',
            'termId',
            'classId',
            'term',
            'class',
            'report'
        ));
    }

    public function student(Request $request, ParallelCurriculumClass $class, Student $student)
    {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        abort_unless((int) $class->tenant_id === $tenantId && (int) $student->tenant_id === $tenantId, 403);

        $termId = $request->integer('term_id');
        $term = $termId
            ? Term::with('session')->findOrFail($termId)
            : Term::current()->with('session')->first();

        abort_unless($term, 422, 'Select an academic term before opening the report.');

        $presentation = $this->results->conventionalStyleStudentReport(
            $class,
            $term,
            (int) $student->id
        );
        abort_unless(
            $presentation,
            404,
            'This student is not enrolled in the selected parallel class for this academic session.'
        );

        $presentation['tenant'] = auth()->user()->tenant;

        return view('parallel-curriculum.results.student', $presentation);
    }

    public function pdf(Request $request, ParallelCurriculumClass $class, Student $student)
    {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        abort_unless((int) $class->tenant_id === $tenantId && (int) $student->tenant_id === $tenantId, 403);

        $termId = $request->integer('term_id');
        $term = $termId
            ? Term::with('session')->findOrFail($termId)
            : Term::current()->with('session')->first();

        abort_unless($term, 422, 'Select an academic term before downloading the report.');

        $presentation = $this->results->conventionalStyleStudentReport(
            $class,
            $term,
            (int) $student->id
        );
        abort_unless(
            $presentation,
            404,
            'This student is not enrolled in the selected parallel class for this academic session.'
        );

        $presentation['tenant'] = auth()->user()->tenant;
        $programmeName = strtoupper(
            trim((string) ($presentation['parallelProgrammeName'] ?? $class->curriculum?->name ?? 'Parallel Curriculum'))
        );
        $presentation['reportDocumentTitle'] = $presentation['isThirdTerm']
            ? $programmeName.' Cumulative Student Performance Report'
            : $programmeName.' Student Termly Performance Report';

        $filename = str($student->admission_number ?: $student->full_name)
            ->slug('_')
            ->append('_', str($class->curriculum?->name)->slug('_'))
            ->append('_result.pdf')
            ->toString();

        return Pdf::loadView('reports.pdf', $presentation)
            ->setPaper(
                'a4',
                $presentation['isThirdTerm'] ? 'landscape' : 'portrait'
            )
            ->download($filename);
    }

    public function formTeacherComments(Request $request)
    {
        $user = auth()->user();
        $tenantId = $this->tenantId();

        abort_unless(
            $user && $this->parallel->enabledForTenant($tenantId),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );

        abort_unless(
            Schema::hasTable('parallel_curriculum_result_comments')
                && Schema::hasColumn('parallel_curriculum_class_arms', 'teaching_assignment_mode')
                && Schema::hasColumn('parallel_curriculum_class_arms', 'class_teacher_id'),
            503,
            'Parallel form-teacher comments are unavailable until the latest database migration has been applied.'
        );

        $canManage = $this->parallel->canManageLifecycle($user);

        $arms = $canManage
            ? ParallelCurriculumClassArm::with('curriculumClass.curriculum')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('teaching_assignment_mode', 'class_teacher')
                ->whereNotNull('class_teacher_id')
                ->whereHas('curriculumClass', function ($query): void {
                    $query->where('is_active', true)
                        ->whereHas('curriculum', fn ($curriculum) => $curriculum->where('is_active', true));
                })
                ->orderBy('parallel_curriculum_class_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
            : $this->parallel->formTeacherArmsForUser($user);

        abort_unless(
            $canManage || $arms->isNotEmpty(),
            403,
            'You are not assigned as the form teacher of a parallel curriculum class arm.'
        );

        $terms = Term::with('session')->orderByDesc('id')->get();
        $currentTerm = $terms->firstWhere('is_current', true);
        $termId = (int) ($request->integer('term_id') ?: ($currentTerm?->id ?? $terms->first()?->id ?? 0));
        $armId = (int) ($request->integer('arm_id') ?: ($arms->first()?->id ?? 0));

        $term = $termId ? $terms->firstWhere('id', $termId) : null;
        $arm = $armId ? $arms->firstWhere('id', $armId) : null;

        abort_if($termId && ! $term, 404, 'The selected academic term is unavailable.');
        abort_if($armId && ! $arm, 403, 'You cannot manage comments for the selected parallel class arm.');

        $enrolments = collect();
        $comments = collect();
        $publication = null;

        if ($arm && $term) {
            $enrolments = ParallelCurriculumEnrolment::with('student')
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_class_id', $arm->parallel_curriculum_class_id)
                ->where('parallel_curriculum_class_arm_id', $arm->id)
                ->where('session_id', $term->session_id)
                ->where('is_active', true)
                ->whereHas('student', fn ($query) => $query->where('status', Student::STATUS_ACTIVE))
                ->get()
                ->sortBy(fn (ParallelCurriculumEnrolment $enrolment) =>
                    strtolower(($enrolment->student?->last_name ?? '').' '.($enrolment->student?->first_name ?? ''))
                )
                ->values();

            $comments = ParallelCurriculumResultComment::with('formTeacher')
                ->where('tenant_id', $tenantId)
                ->where('term_id', $term->id)
                ->whereIn('parallel_curriculum_enrolment_id', $enrolments->pluck('id'))
                ->get()
                ->keyBy('parallel_curriculum_enrolment_id');

            $publication = ParallelCurriculumReportPublication::where(
                    'parallel_curriculum_class_id',
                    $arm->parallel_curriculum_class_id
                )
                ->where('term_id', $term->id)
                ->first();
        }

        $isPublished = $publication?->isPublished() ?? false;

        return view('parallel-curriculum.form-teacher-comments', compact(
            'arms',
            'terms',
            'currentTerm',
            'termId',
            'armId',
            'term',
            'arm',
            'enrolments',
            'comments',
            'isPublished',
            'canManage'
        ));
    }

    public function saveFormTeacherComments(Request $request)
    {
        $user = auth()->user();
        $tenantId = $this->tenantId();

        abort_unless(
            $user && $this->parallel->enabledForTenant($tenantId),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );

        abort_unless(
            Schema::hasTable('parallel_curriculum_result_comments'),
            503,
            'Parallel form-teacher comments are unavailable until the latest database migration has been applied.'
        );

        $data = $request->validate([
            'arm_id' => [
                'required',
                Rule::exists('parallel_curriculum_class_arms', 'id')->where('tenant_id', $tenantId),
            ],
            'term_id' => [
                'required',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
            'comments' => ['nullable', 'array'],
            'comments.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $arm = ParallelCurriculumClassArm::with('curriculumClass.curriculum')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->findOrFail($data['arm_id']);
        $term = Term::with('session')->findOrFail($data['term_id']);

        abort_unless(
            $this->parallel->canManageLifecycle($user)
                || $this->parallel->isFormTeacherForArm($user, $arm),
            403,
            'You are not the form teacher of the selected parallel curriculum class arm.'
        );

        $publication = ParallelCurriculumReportPublication::where(
                'parallel_curriculum_class_id',
                $arm->parallel_curriculum_class_id
            )
            ->where('term_id', $term->id)
            ->first();

        abort_if(
            $publication?->isPublished(),
            423,
            'This parallel result is published. Unpublish it before changing form-teacher comments.'
        );

        $enrolments = ParallelCurriculumEnrolment::where('tenant_id', $tenantId)
            ->where('parallel_curriculum_class_id', $arm->parallel_curriculum_class_id)
            ->where('parallel_curriculum_class_arm_id', $arm->id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $submitted = $data['comments'] ?? [];
        foreach (array_keys($submitted) as $enrolmentId) {
            abort_unless(
                $enrolments->has((int) $enrolmentId),
                422,
                'One or more submitted students are not active in the selected parallel class arm.'
            );
        }

        foreach ($submitted as $enrolmentId => $comment) {
            $comment = trim((string) ($comment ?? ''));

            if ($comment === '') {
                ParallelCurriculumResultComment::where(
                        'parallel_curriculum_enrolment_id',
                        (int) $enrolmentId
                    )
                    ->where('term_id', $term->id)
                    ->delete();
                continue;
            }

            ParallelCurriculumResultComment::updateOrCreate(
                [
                    'parallel_curriculum_enrolment_id' => (int) $enrolmentId,
                    'term_id' => $term->id,
                ],
                [
                    'tenant_id' => $tenantId,
                    'form_teacher_id' => $arm->class_teacher_id,
                    'form_teacher_comment' => $comment,
                ]
            );
        }

        return redirect()
            ->route('parallel-curriculum.form-teacher-comments.index', [
                'arm_id' => $arm->id,
                'term_id' => $term->id,
            ])
            ->with('success', 'Parallel form-teacher comments saved.');
    }

    public function cumulative(
        Request $request,
        ParallelCurriculumClass $class,
        Student $student
    ) {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        abort_unless(
            (int) $class->tenant_id === $tenantId
                && (int) $student->tenant_id === $tenantId,
            403
        );

        $sessionId = (int) (
            $request->integer('session_id')
            ?: AcademicSession::where('tenant_id', $tenantId)
                ->where('is_current', true)
                ->value('id')
            ?: AcademicSession::where('tenant_id', $tenantId)
                ->orderByDesc('id')
                ->value('id')
        );

        $session = AcademicSession::where('tenant_id', $tenantId)
            ->findOrFail($sessionId);

        $term = Term::with('session')
            ->where('tenant_id', $tenantId)
            ->where('session_id', $session->id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->last();

        abort_unless(
            $term,
            422,
            'The selected session has no academic term available for cumulative reporting.'
        );

        $presentation = $this->results->conventionalStyleStudentReport(
            $class,
            $term,
            (int) $student->id,
            true
        );

        abort_unless(
            $presentation,
            404,
            'This student is not enrolled in the selected parallel class for this academic session.'
        );

        $presentation['tenant'] = auth()->user()->tenant;
        $presentation['reportDocumentTitle'] = strtoupper(
            trim((string) ($presentation['parallelProgrammeName'] ?? 'Parallel Curriculum'))
        ).' Cumulative Student Performance Report';

        return view('parallel-curriculum.results.student', $presentation);
    }

    public function cumulativePdf(
        Request $request,
        ParallelCurriculumClass $class,
        Student $student
    ) {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        abort_unless(
            (int) $class->tenant_id === $tenantId
                && (int) $student->tenant_id === $tenantId,
            403
        );

        $sessionId = (int) (
            $request->integer('session_id')
            ?: AcademicSession::where('tenant_id', $tenantId)
                ->where('is_current', true)
                ->value('id')
            ?: AcademicSession::where('tenant_id', $tenantId)
                ->orderByDesc('id')
                ->value('id')
        );

        $session = AcademicSession::where('tenant_id', $tenantId)
            ->findOrFail($sessionId);

        $term = Term::with('session')
            ->where('tenant_id', $tenantId)
            ->where('session_id', $session->id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->last();

        abort_unless(
            $term,
            422,
            'The selected session has no academic term available for cumulative reporting.'
        );

        $presentation = $this->results->conventionalStyleStudentReport(
            $class,
            $term,
            (int) $student->id,
            true
        );

        abort_unless(
            $presentation,
            404,
            'This student is not enrolled in the selected parallel class for this academic session.'
        );

        $presentation['tenant'] = auth()->user()->tenant;
        $programmeName = strtoupper(
            trim((string) ($presentation['parallelProgrammeName'] ?? 'Parallel Curriculum'))
        );
        $presentation['reportDocumentTitle'] =
            $programmeName.' Cumulative Student Performance Report';

        $filename = str($student->admission_number ?: $student->full_name)
            ->slug('_')
            ->append('_', str($class->curriculum?->name)->slug('_'))
            ->append('_cumulative_result.pdf')
            ->toString();

        return Pdf::loadView('reports.pdf', $presentation)
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    public function broadsheet(Request $request)
    {
        $this->assertAccess();

        $context = $this->broadsheetContext($request);

        return view('parallel-curriculum.results.broadsheet', $context);
    }

    public function broadsheetPdf(Request $request)
    {
        $this->assertAccess();

        $context = $this->broadsheetContext($request, true);
        abort_unless(
            $context['selectedClass'] && $context['matrix']->isNotEmpty(),
            422,
            'Select a parallel class and academic period with result data before exporting the broadsheet.'
        );

        $tenant = auth()->user()->tenant;
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

        $context['tenant'] = $tenant;
        $context['logoAbsPath'] = $logoAbsPath;

        $periodLabel = $context['mode'] === 'cumulative'
            ? ($context['selectedSession']?->name ?? 'Session')
            : (($context['selectedTerm']?->name ?? 'Term')
                .' '.($context['selectedTerm']?->session?->name ?? ''));

        $filename = str(
            ($context['selectedClass']->curriculum?->name ?? 'parallel')
            .'-'.$context['selectedClass']->name
            .'-'.$periodLabel
            .'-broadsheet'
        )->slug('_')->append('.pdf')->toString();

        return Pdf::loadView(
            'parallel-curriculum.results.broadsheet-pdf',
            $context
        )->setPaper('a4', 'landscape')->download($filename);
    }

    private function broadsheetContext(
        Request $request,
        bool $forPdf = false
    ): array {
        $tenantId = $this->tenantId();
        $mode = $request->input('mode') === 'cumulative'
            ? 'cumulative'
            : 'termly';

        $classes = ParallelCurriculumClass::with([
                'curriculum',
                'arms' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereHas(
                'curriculum',
                fn ($query) => $query->where('is_active', true)
            )
            ->orderBy('parallel_curriculum_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $sessions = AcademicSession::where('tenant_id', $tenantId)
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $terms = Term::with('session')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->get();

        $classId = (int) (
            $request->integer('class_id')
            ?: ($classes->first()?->id ?? 0)
        );
        $selectedClass = $classId
            ? $classes->firstWhere('id', $classId)
            : null;

        abort_if(
            $classId && ! $selectedClass,
            404,
            'The selected parallel class is unavailable.'
        );

        $armId = (int) $request->integer('arm_id');
        $selectedArm = null;
        if ($selectedClass && $armId) {
            $selectedArm = $selectedClass->arms->firstWhere('id', $armId);
            abort_unless(
                $selectedArm,
                404,
                'The selected parallel class arm is unavailable.'
            );
        }

        $selectedTerm = null;
        $selectedSession = null;
        $periodTerms = collect();

        if ($mode === 'cumulative') {
            $sessionId = (int) (
                $request->integer('session_id')
                ?: ($sessions->firstWhere('is_current', true)?->id
                    ?? $sessions->first()?->id
                    ?? 0)
            );
            $selectedSession = $sessionId
                ? $sessions->firstWhere('id', $sessionId)
                : null;

            abort_if(
                $sessionId && ! $selectedSession,
                404,
                'The selected academic session is unavailable.'
            );

            if ($selectedSession) {
                $periodTerms = $terms
                    ->where('session_id', $selectedSession->id)
                    ->sortBy(fn (Term $term) =>
                        ($term->start_date?->format('Y-m-d') ?? '9999-12-31')
                        .'-'.str_pad((string) $term->id, 10, '0', STR_PAD_LEFT)
                    )
                    ->values();
            }
        } else {
            $currentTerm = $terms->firstWhere('is_current', true);
            $termId = (int) (
                $request->integer('term_id')
                ?: ($currentTerm?->id ?? $terms->first()?->id ?? 0)
            );
            $selectedTerm = $termId
                ? $terms->firstWhere('id', $termId)
                : null;

            abort_if(
                $termId && ! $selectedTerm,
                404,
                'The selected academic term is unavailable.'
            );

            if ($selectedTerm) {
                $selectedSession = $selectedTerm->session;
                $periodTerms = collect([$selectedTerm]);
            }
        }

        $subjects = collect();
        $matrix = collect();
        $subjectStats = collect();

        if ($selectedClass && $periodTerms->isNotEmpty()) {
            $reports = $periodTerms
                ->mapWithKeys(fn (Term $term) => [
                    (int) $term->id =>
                        $this->results->classReport($selectedClass, $term),
                ]);

            $firstReport = $reports->first();
            $subjects = collect($firstReport['subject_assignments'] ?? [])
                ->map(fn ($assignment) => $assignment->subject)
                ->filter()
                ->unique('id')
                ->sortBy('name')
                ->values();

            if ($mode === 'cumulative') {
                $matrix = $this->buildCumulativeBroadsheetMatrix(
                    $reports,
                    $periodTerms,
                    $subjects,
                    $armId
                );
            } else {
                $matrix = $this->buildTermlyBroadsheetMatrix(
                    $firstReport,
                    $subjects,
                    $armId
                );
            }

            $subjectStats = $this->broadsheetSubjectStats(
                $matrix,
                $subjects
            );
        }

        return compact(
            'mode',
            'classes',
            'sessions',
            'terms',
            'selectedClass',
            'selectedArm',
            'selectedTerm',
            'selectedSession',
            'periodTerms',
            'subjects',
            'matrix',
            'subjectStats',
            'forPdf'
        );
    }

    private function buildTermlyBroadsheetMatrix(
        array $report,
        Collection $subjects,
        int $armId
    ): Collection {
        $rows = collect($report['results'] ?? []);

        if ($armId) {
            $rows = $rows->filter(
                fn (array $row) =>
                    (int) (
                        $row['enrolment']
                            ->parallel_curriculum_class_arm_id
                        ?? 0
                    ) === $armId
            );
        }

        $matrix = $rows->map(function (array $row) use ($subjects): array {
            $subjectMap = collect($row['subjects'])->keyBy('subject_id');
            $scores = [];

            foreach ($subjects as $subject) {
                $subjectRow = $subjectMap->get((int) $subject->id);
                $scores[(int) $subject->id] = [
                    'score' => $subjectRow['percentage'] ?? null,
                    'grade' => $subjectRow['grade'] ?? '—',
                    'complete' => (bool) ($subjectRow['complete'] ?? false),
                ];
            }

            return [
                'student' => $row['student'],
                'enrolment' => $row['enrolment'],
                'subjects' => $scores,
                'term_averages' => [],
                'total' => (float) ($row['grand_total'] ?? 0),
                'average' => $row['average'] === null
                    ? null
                    : (float) $row['average'],
                'position' => null,
            ];
        })->values();

        return $this->rankBroadsheetRows($matrix);
    }

    private function buildCumulativeBroadsheetMatrix(
        Collection $reports,
        Collection $periodTerms,
        Collection $subjects,
        int $armId
    ): Collection {
        $rowsByTerm = $reports->map(function (array $report) use ($armId) {
            $rows = collect($report['results'] ?? []);

            if ($armId) {
                $rows = $rows->filter(
                    fn (array $row) =>
                        (int) (
                            $row['enrolment']
                                ->parallel_curriculum_class_arm_id
                            ?? 0
                        ) === $armId
                );
            }

            return $rows->keyBy(fn (array $row) => (int) $row['student']->id);
        });

        $studentIds = $rowsByTerm
            ->flatMap(fn (Collection $rows) => $rows->keys())
            ->unique()
            ->values();

        $matrix = $studentIds->map(function ($studentId) use (
            $rowsByTerm,
            $periodTerms,
            $subjects
        ): array {
            $firstRow = null;
            foreach ($periodTerms as $term) {
                $candidate = $rowsByTerm
                    ->get((int) $term->id, collect())
                    ->get((int) $studentId);
                if ($candidate) {
                    $firstRow = $candidate;
                    break;
                }
            }

            $subjectRows = [];
            foreach ($subjects as $subject) {
                $termScores = [];
                foreach ($periodTerms as $term) {
                    $studentRow = $rowsByTerm
                        ->get((int) $term->id, collect())
                        ->get((int) $studentId);
                    $subjectRow = $studentRow
                        ? collect($studentRow['subjects'])->first(
                            fn (array $item) =>
                                (int) $item['subject_id']
                                    === (int) $subject->id
                        )
                        : null;

                    $termScores[(int) $term->id] =
                        $subjectRow['percentage'] ?? null;
                }

                $available = collect($termScores)
                    ->filter(fn ($score) => $score !== null)
                    ->map(fn ($score) => (float) $score);

                $subjectRows[(int) $subject->id] = [
                    'score' => $available->isNotEmpty()
                        ? round((float) $available->avg(), 1)
                        : null,
                    'term_scores' => $termScores,
                    'grade' => '—',
                    'complete' => $available->count() === $periodTerms->count(),
                ];
            }

            $termAverages = [];
            foreach ($periodTerms as $term) {
                $studentRow = $rowsByTerm
                    ->get((int) $term->id, collect())
                    ->get((int) $studentId);
                $termAverages[(int) $term->id] =
                    $studentRow['average'] ?? null;
            }

            $availableSubjectScores = collect($subjectRows)
                ->pluck('score')
                ->filter(fn ($score) => $score !== null)
                ->map(fn ($score) => (float) $score);

            return [
                'student' => $firstRow['student'],
                'enrolment' => $firstRow['enrolment'],
                'subjects' => $subjectRows,
                'term_averages' => $termAverages,
                'total' => round((float) $availableSubjectScores->sum(), 1),
                'average' => $availableSubjectScores->isNotEmpty()
                    ? round((float) $availableSubjectScores->avg(), 1)
                    : null,
                'position' => null,
            ];
        })->filter(fn (array $row) => ! empty($row['student']))->values();

        return $this->rankBroadsheetRows($matrix);
    }

    private function rankBroadsheetRows(Collection $rows): Collection
    {
        $ranked = $rows
            ->sortByDesc(fn (array $row) => $row['average'] ?? -1)
            ->values();

        $previousAverage = null;
        $previousPosition = null;

        return $ranked->map(function (array $row, int $index) use (
            &$previousAverage,
            &$previousPosition
        ): array {
            if ($row['average'] === null) {
                $row['position'] = null;
                return $row;
            }

            $average = (float) $row['average'];
            $position = $index + 1;

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
    }

    private function broadsheetSubjectStats(
        Collection $matrix,
        Collection $subjects
    ): Collection {
        return $subjects->mapWithKeys(function ($subject) use ($matrix): array {
            $scores = $matrix
                ->map(fn (array $row) =>
                    $row['subjects'][(int) $subject->id]['score'] ?? null
                )
                ->filter(fn ($score) => $score !== null)
                ->map(fn ($score) => (float) $score)
                ->values();

            return [
                (int) $subject->id => [
                    'highest' => $scores->isNotEmpty()
                        ? round((float) $scores->max(), 1)
                        : '—',
                    'lowest' => $scores->isNotEmpty()
                        ? round((float) $scores->min(), 1)
                        : '—',
                    'avg' => $scores->isNotEmpty()
                        ? round((float) $scores->avg(), 1)
                        : '—',
                ],
            ];
        });
    }

    public function publish(Request $request)
    {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'term_id' => [
                'required',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        $class = ParallelCurriculumClass::with('curriculum')->findOrFail($data['class_id']);
        $term = Term::with('session')->findOrFail($data['term_id']);
        $report = $this->results->classReport($class, $term);

        if (! $this->results->canPublish($report)) {
            $message = match (true) {
                $report['students_count'] < 1 => 'No active students are enrolled in this parallel class for the selected session.',
                $report['subjects_count'] < 1 => 'No active subjects are assigned to this parallel class.',
                $report['components']->isEmpty() => 'The class has no usable Assessment Template.',
                abs((float) $report['component_weight'] - 100.0) > 0.001 => 'The parallel Assessment Template must total exactly 100% before publication.',
                $report['grades']->isEmpty() => 'Configure the programme grading scale before publishing results.',
                ! $report['grading_scale_complete'] => 'The programme grading scale must cover every score from 0 to 100 without gaps.',
                $report['ungraded_subject_results_count'] > 0 => 'One or more completed subject scores do not resolve to a configured grade.',
                default => 'Complete all subject scores for every active student before publishing this parallel result.',
            };

            throw ValidationException::withMessages(['publication' => $message]);
        }

        $publication = ParallelCurriculumReportPublication::where(
                'parallel_curriculum_class_id',
                $class->id
            )
            ->where('term_id', $term->id)
            ->first();

        $wasPublished = $publication?->isPublished() ?? false;

        $publication ??= new ParallelCurriculumReportPublication([
            'tenant_id' => $tenantId,
            'parallel_curriculum_class_id' => $class->id,
            'term_id' => $term->id,
        ]);

        $publication->fill([
            'parallel_curriculum_id' => $class->parallel_curriculum_id,
            'status' => ParallelCurriculumReportPublication::STATUS_PUBLISHED,
            'published_by' => auth()->id(),
            'published_at' => now(),
            'unpublished_at' => null,
        ])->save();

        $notified = 0;
        if (! $wasPublished) {
            $studentIds = $report['results']
                ->pluck('student.id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $students = Student::with('guardians')
                ->whereIn('id', $studentIds)
                ->get()
                ->keyBy('id');

            $notifier = app(GuardianNotifier::class);
            $schoolName = auth()->user()->tenant?->name;
            $programmeName = $class->curriculum?->name ?: 'Parallel Curriculum';

            foreach ($report['results'] as $row) {
                $student = $students->get((int) $row['student']->id);
                if (! $student) {
                    continue;
                }

                $guardian = $student->guardians->firstWhere('pivot.is_primary_contact', true)
                    ?? $student->guardians->first();

                if (! $guardian) {
                    continue;
                }

                try {
                    $notifier->send(
                        $guardian,
                        $programmeName.' result published — '.$student->full_name,
                        [
                            ($term->name ?? 'Term').' '.$programmeName.
                                ' result for '.$student->full_name.' is now available.',
                            'Sign in to the parent portal to view or download the published result.',
                        ],
                        smsBody: 'Dear Parent, '.$student->full_name."'s ".
                            ($term->name ?? 'term').' '.$programmeName.
                            ' result is now available on the EduCore parent portal.',
                        actionLabel: 'View Results',
                        actionUrl: route('login'),
                        schoolName: $schoolName,
                    );
                    $notified++;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error(
                        "Parallel-result notification failed for student {$student->id}: ".$e->getMessage()
                    );
                }
            }
        }

        $message = 'Parallel curriculum result published. Source scores are now locked.';
        if (! $wasPublished && $notified > 0) {
            $message .= " {$notified} parent/guardian notification(s) were queued/sent.";
        }

        return back()->with('success', $message);
    }

    public function unpublish(Request $request)
    {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'term_id' => [
                'required',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        $publication = ParallelCurriculumReportPublication::where(
                'parallel_curriculum_class_id',
                $data['class_id']
            )
            ->where('term_id', $data['term_id'])
            ->first();

        if ($publication) {
            $publication->update([
                'status' => ParallelCurriculumReportPublication::STATUS_DRAFT,
                'unpublished_at' => now(),
            ]);
        }

        return back()->with('success', 'Parallel curriculum result unpublished. Score entry is open again unless the conventional report is published.');
    }
}
