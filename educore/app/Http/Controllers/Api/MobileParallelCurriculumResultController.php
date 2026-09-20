<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\Student;
use App\Models\Term;
use App\Services\GuardianNotifier;
use App\Services\ParallelCurriculumResultService;
use App\Services\ParallelCurriculumService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileParallelCurriculumResultController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumService $parallel,
        private readonly ParallelCurriculumResultService $results,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);

        $terms = Term::with('session')
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $classes = ParallelCurriculumClass::with('curriculum')
            ->where('is_active', true)
            ->whereHas('curriculum', fn ($query) => $query->where('is_active', true))
            ->orderBy('parallel_curriculum_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $currentTerm = $terms->firstWhere('is_current', true) ?: $terms->first();
        $termId = (int) ($request->integer('term_id') ?: ($currentTerm?->id ?? 0));
        $classId = (int) ($request->integer('class_id') ?: ($classes->first()?->id ?? 0));

        $term = $termId ? $terms->firstWhere('id', $termId) : null;
        $class = $classId ? $classes->firstWhere('id', $classId) : null;

        abort_if($termId && ! $term, 404, 'The selected term is unavailable.');
        abort_if($classId && ! $class, 404, 'The selected parallel class is unavailable.');

        $report = ($term && $class)
            ? $this->results->classReport($class, $term)
            : null;

        return response()->json([
            'contract_version' => 1,
            'selected_class_id' => $class?->id,
            'selected_term_id' => $term?->id,
            'classes' => $classes->map(fn (ParallelCurriculumClass $item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'curriculum_id' => (int) $item->parallel_curriculum_id,
                'curriculum_name' => $item->curriculum?->name,
                'label' => trim(($item->curriculum?->name ?: 'Parallel').' · '.$item->name),
            ])->values(),
            'terms' => $terms->map(fn (Term $item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'session_id' => (int) $item->session_id,
                'session_name' => $item->session?->name,
                'is_current' => (bool) $item->is_current,
                'label' => trim(($item->session?->name ?: 'Session').' · '.$item->name),
            ])->values(),
            'report' => $report ? $this->serializeClassReport($report) : null,
            'tenant_id' => $tenantId,
        ]);
    }

    public function student(
        Request $request,
        ParallelCurriculumClass $class,
        Student $student
    ): JsonResponse {
        $tenantId = $this->assertManage($request);
        abort_unless(
            (int) $class->tenant_id === $tenantId
                && (int) $student->tenant_id === $tenantId,
            403
        );

        $data = $request->validate([
            'term_id' => [
                'required',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        $term = Term::with('session')->findOrFail($data['term_id']);
        $report = $this->results->studentReport(
            $class,
            $term,
            (int) $student->id
        );

        abort_unless(
            $report,
            404,
            'This student is not enrolled in the selected parallel class for this academic session.'
        );

        $row = $report['student_result'];

        return response()->json([
            'contract_version' => 1,
            'class_id' => (int) $class->id,
            'class_name' => $class->name,
            'curriculum_name' => $report['curriculum']?->name,
            'term_id' => (int) $term->id,
            'term' => $term->name,
            'session' => $term->session?->name,
            'is_published' => (bool) $report['is_published'],
            'grading_source' => (string) $report['grading_source'],
            'student' => [
                'id' => (int) $row['student']->id,
                'name' => $row['student']->full_name,
                'admission_number' => $row['student']->admission_number,
                'arm_name' => $row['enrolment']->curriculumClassArm?->name,
                'subject_count' => (int) $row['subject_count'],
                'completed_subject_count' => (int) $row['completed_subject_count'],
                'complete' => (bool) $row['complete'],
                'grand_total' => (float) $row['grand_total'],
                'maximum_total' => (float) $row['maximum_total'],
                'average' => $row['average'] !== null ? (float) $row['average'] : null,
                'failed_subjects' => (int) $row['failed_subjects'],
                'position' => $row['position'] ? (int) $row['position'] : null,
                'subjects' => $row['subjects']->map(fn (array $subject) => [
                    'subject_id' => (int) $subject['subject_id'],
                    'subject' => (string) $subject['subject'],
                    'complete' => (bool) $subject['complete'],
                    'raw_total' => (float) $subject['raw_total'],
                    'percentage' => $subject['percentage'] !== null
                        ? (float) $subject['percentage']
                        : null,
                    'grade' => $subject['grade'],
                    'remark' => $subject['remark'],
                    'is_pass' => $subject['is_pass'],
                    'components' => collect($subject['components'])->map(
                        fn (array $component) => [
                            'id' => (int) $component['id'],
                            'name' => (string) $component['name'],
                            'maximum' => (float) $component['maximum'],
                            'score' => $component['score'] !== null
                                ? (float) $component['score']
                                : null,
                        ]
                    )->values(),
                ])->values(),
            ],
        ]);
    }

    public function cumulativeStudent(
        Request $request,
        ParallelCurriculumClass $class,
        Student $student
    ): JsonResponse {
        $tenantId = $this->assertManage($request);
        abort_unless(
            (int) $class->tenant_id === $tenantId
                && (int) $student->tenant_id === $tenantId,
            403
        );

        $data = $request->validate([
            'session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id')
                    ->where('tenant_id', $tenantId),
            ],
        ]);

        $session = AcademicSession::where('tenant_id', $tenantId)
            ->findOrFail((int) $data['session_id']);
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

        return response()->json(
            $this->serializeCumulativeStudentPresentation(
                $class,
                $student,
                $session,
                $presentation
            )
        );
    }

    public function cumulativeStudentPdf(
        Request $request,
        ParallelCurriculumClass $class,
        Student $student
    ) {
        $tenantId = $this->assertManage($request);
        abort_unless(
            (int) $class->tenant_id === $tenantId
                && (int) $student->tenant_id === $tenantId,
            403
        );

        $data = $request->validate([
            'session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id')
                    ->where('tenant_id', $tenantId),
            ],
        ]);

        $session = AcademicSession::where('tenant_id', $tenantId)
            ->findOrFail((int) $data['session_id']);
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

        $presentation['tenant'] = $request->user()?->tenant;
        $presentation['reportDocumentTitle'] = strtoupper(
            trim((string) ($presentation['parallelProgrammeName'] ?? 'Parallel Curriculum'))
        ).' Cumulative Student Performance Report';

        $filename = str($student->admission_number ?: $student->full_name)
            ->slug('_')
            ->append('_', str($class->curriculum?->name ?: 'parallel_curriculum')->slug('_'))
            ->append('_cumulative_result.pdf')
            ->toString();

        return Pdf::loadView('reports.pdf', $presentation)
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    public function broadsheet(Request $request): JsonResponse
    {
        $this->assertManage($request);
        $context = $this->broadsheetContext($request);

        return response()->json([
            'contract_version' => 1,
            'mode' => $context['mode'],
            'selected' => [
                'class_id' => $context['selectedClass']?->id,
                'arm_id' => $context['selectedArm']?->id,
                'term_id' => $context['selectedTerm']?->id,
                'session_id' => $context['selectedSession']?->id,
            ],
            'classes' => $context['classes']->map(fn (ParallelCurriculumClass $item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'curriculum_id' => (int) $item->parallel_curriculum_id,
                'curriculum_name' => $item->curriculum?->name,
                'label' => trim(($item->curriculum?->name ?: 'Parallel').' · '.$item->name),
                'arms' => $item->arms->map(fn ($arm) => [
                    'id' => (int) $arm->id,
                    'name' => (string) $arm->name,
                ])->values(),
            ])->values(),
            'sessions' => $context['sessions']->map(fn (AcademicSession $item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'is_current' => (bool) $item->is_current,
            ])->values(),
            'terms' => $context['terms']->map(fn (Term $item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'session_id' => (int) $item->session_id,
                'session_name' => $item->session?->name,
                'is_current' => (bool) $item->is_current,
            ])->values(),
            'period_terms' => $context['periodTerms']->map(fn (Term $item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
            ])->values(),
            'subjects' => $context['subjects']->map(fn ($subject) => [
                'id' => (int) $subject->id,
                'name' => (string) $subject->name,
                'code' => $subject->code,
                'stats' => $context['subjectStats']->get((int) $subject->id),
            ])->values(),
            'rows' => $context['matrix']->map(fn (array $row) => [
                'student_id' => (int) $row['student']->id,
                'student_name' => $row['student']->full_name,
                'admission_number' => $row['student']->admission_number,
                'arm_id' => $row['enrolment']->parallel_curriculum_class_arm_id
                    ? (int) $row['enrolment']->parallel_curriculum_class_arm_id
                    : null,
                'subjects' => $row['subjects'],
                'term_averages' => $row['term_averages'],
                'total' => (float) $row['total'],
                'average' => $row['average'] !== null
                    ? (float) $row['average']
                    : null,
                'position' => $row['position'],
            ])->values(),
        ]);
    }

    public function broadsheetPdf(Request $request)
    {
        $this->assertManage($request);
        $context = $this->broadsheetContext($request);

        abort_unless(
            $context['selectedClass'] && $context['matrix']->isNotEmpty(),
            422,
            'Select a parallel class and academic period with result data before exporting the broadsheet.'
        );

        $tenant = $request->user()?->tenant;
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

    public function export(Request $request)
    {
        $tenantId = $this->assertManage($request);
        $data = $request->validate([
            'class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'term_id' => [
                'required',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
            'format' => ['required', Rule::in(['csv', 'pdf'])],
        ]);

        $class = ParallelCurriculumClass::with('curriculum')->findOrFail($data['class_id']);
        $term = Term::with('session')->findOrFail($data['term_id']);
        $report = $this->results->classReport($class, $term);
        $baseName = str(
            ($class->curriculum?->name ?: 'parallel_curriculum').'_'.
            $class->name.'_'.($term->session?->name ?: 'session').'_'.$term->name
        )->slug('_')->append('_result_register')->toString();

        if ($data['format'] === 'csv') {
            return response()->streamDownload(function () use ($report): void {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, [
                    'admission_number',
                    'student_name',
                    'parallel_arm',
                    'completed_subjects',
                    'subject_count',
                    'grand_total',
                    'maximum_total',
                    'average_percent',
                    'position',
                    'failed_subjects',
                    'status',
                    'publication_status',
                ]);

                foreach ($report['results'] as $row) {
                    fputcsv($handle, [
                        $row['student']->admission_number,
                        $row['student']->full_name,
                        $row['enrolment']->curriculumClassArm?->name,
                        $row['completed_subject_count'],
                        $row['subject_count'],
                        number_format((float) $row['grand_total'], 2, '.', ''),
                        number_format((float) $row['maximum_total'], 2, '.', ''),
                        $row['average'] === null
                            ? null
                            : number_format((float) $row['average'], 2, '.', ''),
                        $row['position'],
                        $row['failed_subjects'],
                        $row['complete'] ? 'complete' : 'incomplete',
                        $report['is_published'] ? 'published' : 'draft',
                    ]);
                }

                fclose($handle);
            }, $baseName.'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'private, no-store, max-age=0',
            ]);
        }

        return Pdf::loadView('parallel-curriculum.results.class-register-pdf', [
                'report' => $report,
                'schoolName' => $request->user()?->tenant?->name,
            ])
            ->setPaper('a4', 'landscape')
            ->download($baseName.'.pdf');
    }

    public function studentPdf(
        Request $request,
        ParallelCurriculumClass $class,
        Student $student
    ) {
        $tenantId = $this->assertManage($request);
        abort_unless(
            (int) $class->tenant_id === $tenantId
                && (int) $student->tenant_id === $tenantId,
            403
        );

        $data = $request->validate([
            'term_id' => [
                'required',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        $term = Term::with('session')->findOrFail($data['term_id']);
        $report = $this->results->studentReport($class, $term, (int) $student->id);

        abort_unless(
            $report,
            404,
            'This student is not enrolled in the selected parallel class for this academic session.'
        );

        $filename = str($student->admission_number ?: $student->full_name)
            ->slug('_')
            ->append('_', str($report['curriculum']?->name ?: 'parallel_curriculum')->slug('_'))
            ->append('_result.pdf')
            ->toString();

        return Pdf::loadView('parallel-curriculum.results.pdf', [
                'report' => $report,
                'schoolName' => $request->user()?->tenant?->name,
            ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function publish(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $this->publicationData($request, $tenantId);

        $class = ParallelCurriculumClass::with('curriculum')
            ->findOrFail($data['class_id']);
        $term = Term::with('session')->findOrFail($data['term_id']);
        $report = $this->results->classReport($class, $term);

        if (! $this->results->canPublish($report)) {
            throw ValidationException::withMessages([
                'publication' => $this->publicationBlockers($report)[0]
                    ?? 'Complete the result configuration and score entry before publication.',
            ]);
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
            'published_by' => $request->user()?->id,
            'published_at' => now(),
            'unpublished_at' => null,
        ])->save();

        $notified = $wasPublished
            ? 0
            : $this->notifyGuardians($request, $class, $term, $report);

        $message = 'Parallel curriculum result published. Source scores are now locked.';
        if ($notified > 0) {
            $message .= " {$notified} parent/guardian notification(s) were queued/sent.";
        }

        return response()->json([
            'message' => $message,
            'notified' => $notified,
            'status' => ParallelCurriculumReportPublication::STATUS_PUBLISHED,
        ]);
    }

    public function unpublish(Request $request): JsonResponse
    {
        $tenantId = $this->assertManage($request);
        $data = $this->publicationData($request, $tenantId);

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

        return response()->json([
            'message' => 'Parallel curriculum result unpublished. Score entry is open again unless the conventional report is published.',
            'status' => ParallelCurriculumReportPublication::STATUS_DRAFT,
        ]);
    }

    private function serializeCumulativeStudentPresentation(
        ParallelCurriculumClass $class,
        Student $student,
        AcademicSession $session,
        array $presentation
    ): array {
        $summary = $presentation['summary'];
        $ratings = collect($presentation['skillRatings'])
            ->keyBy('skill_definition_id');

        $serializeSkills = function ($skills) use ($ratings): array {
            return collect($skills)->map(fn ($skill) => [
                'id' => (int) $skill->id,
                'name' => (string) $skill->name,
                'rating' => $ratings->get($skill->id)?->rating,
            ])->values()->all();
        };

        return [
            'contract_version' => 1,
            'class_id' => (int) $class->id,
            'class_name' => (string) $class->name,
            'curriculum_id' => (int) $class->parallel_curriculum_id,
            'curriculum_name' => $presentation['parallelProgrammeName'],
            'document_title' => $presentation['reportDocumentTitle'],
            'session' => [
                'id' => (int) $session->id,
                'name' => (string) $session->name,
            ],
            'student' => [
                'id' => (int) $student->id,
                'name' => $student->full_name,
                'admission_number' => $student->admission_number,
                'class' => $presentation['parallelClassName'],
            ],
            'summary' => [
                'average' => (float) $summary->final_average,
                'position' => $summary->position_in_class,
                'class_size' => (int) $summary->total_students_in_class,
                'subjects_offered' => (int) $summary->subjects_offered,
                'subjects_failed' => (int) $summary->subjects_failed,
                'total_score' => (float) $summary->total_score,
                'form_teacher_remark' => $summary->form_tutor_remark,
                'principal_remark' => $summary->principal_remark,
                'promotion_decision' => $summary->promotion_decision,
                'promoted_to_class' => $summary->promoted_to_class,
            ],
            'subjects' => collect($presentation['subjectRows'])->values(),
            'attendance' => $presentation['attendanceSummary'],
            'skills' => [
                'affective' => $serializeSkills($presentation['affectiveSkills']),
                'psychomotor' => $serializeSkills($presentation['psychomotorSkills']),
            ],
        ];
    }

    private function broadsheetContext(Request $request): array
    {
        $tenantId = (int) $request->user()->tenant_id;
        $data = $request->validate([
            'mode' => ['nullable', Rule::in(['termly', 'cumulative'])],
            'class_id' => [
                'nullable',
                Rule::exists('parallel_curriculum_classes', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'arm_id' => ['nullable', 'integer'],
            'term_id' => ['nullable', 'integer'],
            'session_id' => ['nullable', 'integer'],
        ]);

        $mode = ($data['mode'] ?? 'termly') === 'cumulative'
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
            ->whereHas('curriculum', fn ($query) => $query->where('is_active', true))
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
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $selectedClass = $classes->firstWhere(
            'id',
            (int) ($data['class_id'] ?? ($classes->first()?->id ?? 0))
        );
        $armId = (int) ($data['arm_id'] ?? 0);
        $selectedArm = $selectedClass && $armId
            ? $selectedClass->arms->firstWhere('id', $armId)
            : null;
        abort_if(
            $armId && ! $selectedArm,
            404,
            'The selected parallel class arm is unavailable.'
        );

        $selectedTerm = null;
        $selectedSession = null;
        $periodTerms = collect();

        if ($mode === 'cumulative') {
            $sessionId = (int) (
                $data['session_id']
                ?? ($sessions->firstWhere('is_current', true)?->id
                    ?? $sessions->first()?->id
                    ?? 0)
            );
            $selectedSession = $sessions->firstWhere('id', $sessionId);
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
            $termId = (int) (
                $data['term_id']
                ?? ($terms->firstWhere('is_current', true)?->id
                    ?? $terms->first()?->id
                    ?? 0)
            );
            $selectedTerm = $terms->firstWhere('id', $termId);
            if ($selectedTerm) {
                $selectedSession = $selectedTerm->session;
                $periodTerms = collect([$selectedTerm]);
            }
        }

        $subjects = collect();
        $matrix = collect();
        $subjectStats = collect();

        if ($selectedClass && $periodTerms->isNotEmpty()) {
            $reports = $periodTerms->mapWithKeys(fn (Term $term) => [
                (int) $term->id => $this->results->classReport($selectedClass, $term),
            ]);

            $firstReport = $reports->first();
            $subjects = collect($firstReport['subject_assignments'] ?? [])
                ->map(fn ($assignment) => $assignment->subject)
                ->filter()
                ->unique('id')
                ->sortBy('name')
                ->values();

            $matrix = $mode === 'cumulative'
                ? $this->buildCumulativeBroadsheetMatrix(
                    $reports,
                    $periodTerms,
                    $subjects,
                    $armId
                )
                : $this->buildTermlyBroadsheetMatrix(
                    $firstReport,
                    $subjects,
                    $armId
                );

            $subjectStats = $this->broadsheetSubjectStats($matrix, $subjects);
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
            'subjectStats'
        );
    }

    private function buildTermlyBroadsheetMatrix(
        array $report,
        Collection $subjects,
        int $armId
    ): Collection {
        $rows = collect($report['results'] ?? []);
        if ($armId) {
            $rows = $rows->filter(fn (array $row) =>
                (int) ($row['enrolment']->parallel_curriculum_class_arm_id ?? 0)
                    === $armId
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
                'average' => $row['average'] === null ? null : (float) $row['average'],
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
        $grades = collect($reports->first()['grades'] ?? []);
        $rowsByTerm = $reports->map(function (array $report) use ($armId) {
            $rows = collect($report['results'] ?? []);
            if ($armId) {
                $rows = $rows->filter(fn (array $row) =>
                    (int) ($row['enrolment']->parallel_curriculum_class_arm_id ?? 0)
                        === $armId
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
            $subjects,
            $grades
        ): array {
            $firstRow = $periodTerms
                ->map(fn (Term $term) =>
                    $rowsByTerm->get((int) $term->id, collect())
                        ->get((int) $studentId)
                )
                ->filter()
                ->first();

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
                                (int) $item['subject_id'] === (int) $subject->id
                        )
                        : null;
                    $termScores[(int) $term->id] = $subjectRow['percentage'] ?? null;
                }

                $available = collect($termScores)
                    ->filter(fn ($score) => $score !== null)
                    ->map(fn ($score) => (float) $score);
                $score = $available->isNotEmpty()
                    ? round((float) $available->avg(), 1)
                    : null;
                $grade = $score === null
                    ? null
                    : $grades->first(fn ($item) =>
                        $score >= (float) $item->min_score
                        && $score <= (float) $item->max_score
                    );

                $subjectRows[(int) $subject->id] = [
                    'score' => $score,
                    'term_scores' => $termScores,
                    'grade' => $grade?->grade_letter ?? '—',
                    'is_pass' => (bool) ($grade?->is_pass_grade ?? false),
                    'complete' => $available->count() === $periodTerms->count(),
                ];
            }

            $termAverages = [];
            foreach ($periodTerms as $term) {
                $row = $rowsByTerm
                    ->get((int) $term->id, collect())
                    ->get((int) $studentId);
                $termAverages[(int) $term->id] = $row['average'] ?? null;
            }

            $subjectScores = collect($subjectRows)
                ->pluck('score')
                ->filter(fn ($score) => $score !== null)
                ->map(fn ($score) => (float) $score);

            return [
                'student' => $firstRow['student'] ?? null,
                'enrolment' => $firstRow['enrolment'] ?? null,
                'subjects' => $subjectRows,
                'term_averages' => $termAverages,
                'total' => round((float) $subjectScores->sum(), 1),
                'average' => $subjectScores->isNotEmpty()
                    ? round((float) $subjectScores->avg(), 1)
                    : null,
                'position' => null,
            ];
        })->filter(fn (array $row) =>
            ! empty($row['student']) && ! empty($row['enrolment'])
        )->values();

        return $this->rankBroadsheetRows($matrix);
    }

    private function rankBroadsheetRows(Collection $rows): Collection
    {
        $ranked = $rows
            ->sortByDesc(fn (array $row) => $row['average'] ?? -1)
            ->values();
        $previousAverage = null;
        $previousPosition = null;

        return $ranked->map(function (
            array $row,
            int $index
        ) use (&$previousAverage, &$previousPosition): array {
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
                        : null,
                    'lowest' => $scores->isNotEmpty()
                        ? round((float) $scores->min(), 1)
                        : null,
                    'average' => $scores->isNotEmpty()
                        ? round((float) $scores->avg(), 1)
                        : null,
                ],
            ];
        });
    }

    private function serializeClassReport(array $report): array
    {
        return [
            'curriculum_id' => (int) $report['class']->parallel_curriculum_id,
            'curriculum_name' => $report['curriculum']?->name,
            'class_id' => (int) $report['class']->id,
            'class_name' => (string) $report['class']->name,
            'term_id' => (int) $report['term']->id,
            'term' => (string) $report['term']->name,
            'session' => $report['term']->session?->name,
            'template_name' => $report['template']?->name,
            'component_weight' => (float) $report['component_weight'],
            'grading_source' => (string) $report['grading_source'],
            'grading_scale_complete' => (bool) $report['grading_scale_complete'],
            'is_published' => (bool) $report['is_published'],
            'can_publish' => $this->results->canPublish($report),
            'blockers' => $this->publicationBlockers($report),
            'students_count' => (int) $report['students_count'],
            'subjects_count' => (int) $report['subjects_count'],
            'complete_students_count' => (int) $report['complete_students_count'],
            'ungraded_subject_results_count' => (int) $report['ungraded_subject_results_count'],
            'rows' => $report['results']->map(fn (array $row) => [
                'student_id' => (int) $row['student']->id,
                'student_name' => $row['student']->full_name,
                'admission_number' => $row['student']->admission_number,
                'arm_name' => $row['enrolment']->curriculumClassArm?->name,
                'completed_subject_count' => (int) $row['completed_subject_count'],
                'subject_count' => (int) $row['subject_count'],
                'grand_total' => (float) $row['grand_total'],
                'maximum_total' => (float) $row['maximum_total'],
                'average' => $row['average'] !== null ? (float) $row['average'] : null,
                'position' => $row['position'] ? (int) $row['position'] : null,
                'failed_subjects' => (int) $row['failed_subjects'],
                'complete' => (bool) $row['complete'],
            ])->values(),
        ];
    }

    private function publicationBlockers(array $report): array
    {
        $blockers = [];

        if ($report['students_count'] < 1) {
            $blockers[] = 'No active students are enrolled in this parallel class for the selected session.';
        }
        if ($report['subjects_count'] < 1) {
            $blockers[] = 'No active subjects are assigned to this parallel class.';
        }
        if ($report['components']->isEmpty()) {
            $blockers[] = 'The class has no usable Assessment Template.';
        } elseif (abs((float) $report['component_weight'] - 100.0) > 0.001) {
            $blockers[] = 'The parallel Assessment Template must total exactly 100% before publication.';
        }
        if ($report['grades']->isEmpty()) {
            $blockers[] = 'Configure the programme grading scale before publishing results.';
        } elseif (! $report['grading_scale_complete']) {
            $blockers[] = 'The grading scale must cover every score from 0 to 100 without gaps.';
        }
        if ($report['ungraded_subject_results_count'] > 0) {
            $blockers[] = 'One or more completed subject scores do not resolve to a configured grade.';
        }
        if ($report['complete_students_count'] < $report['students_count']) {
            $blockers[] = ($report['students_count'] - $report['complete_students_count']).
                ' student result(s) are incomplete.';
        }

        return $blockers;
    }

    private function publicationData(Request $request, int $tenantId): array
    {
        return $request->validate([
            'class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'term_id' => [
                'required',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
        ]);
    }

    private function notifyGuardians(
        Request $request,
        ParallelCurriculumClass $class,
        Term $term,
        array $report
    ): int {
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
        $schoolName = $request->user()?->tenant?->name;
        $programmeName = $class->curriculum?->name ?: 'Parallel Curriculum';
        $notified = 0;

        foreach ($report['results'] as $row) {
            $student = $students->get((int) $row['student']->id);
            if (! $student) {
                continue;
            }

            $guardian = $student->guardians->firstWhere(
                'pivot.is_primary_contact',
                true
            ) ?? $student->guardians->first();

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
                        'Sign in to the parent portal to view the published result.',
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
                    "Parallel-result mobile notification failed for student {$student->id}: ".
                    $e->getMessage()
                );
            }
        }

        return $notified;
    }

    private function assertManage(Request $request): int
    {
        $user = $request->user();

        abort_unless(
            $user && $this->parallel->canManageLifecycle($user),
            403,
            'Only academic administrators can manage parallel curriculum results.'
        );

        $tenantId = (int) $user->tenant_id;
        abort_unless($tenantId > 0, 403);

        abort_unless(
            $this->parallel->enabledForTenant($tenantId),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );

        return $tenantId;
    }
}
