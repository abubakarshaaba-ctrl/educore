<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\Student;
use App\Models\Term;
use App\Services\GuardianNotifier;
use App\Services\ParallelCurriculumResultService;
use App\Services\ParallelCurriculumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            $user && ($user->isSuperAdmin() || $user->canAccessExactModule('scores')),
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
