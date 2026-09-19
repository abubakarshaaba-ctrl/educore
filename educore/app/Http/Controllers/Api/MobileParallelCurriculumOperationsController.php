<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumTimetablePeriod;
use App\Models\Term;
use App\Services\ParallelCurriculumOperationsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileParallelCurriculumOperationsController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumOperationsService $operations,
    ) {}

    public function index(Request $request)
    {
        $data = $request->validate([
            'parallel_curriculum_id' => ['nullable', 'integer'],
            'session_id' => ['nullable', 'integer'],
            'term_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'arm_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        abort_unless($user->isTenantStaff() || $user->isSuperAdmin(), 403, 'Parallel operations are available to school staff only.');
        $tenantId = (int) $user->tenant_id;

        $curricula = ParallelCurriculum::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $sessions = AcademicSession::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $terms = Term::query()
            ->with('session')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->get();

        $curriculumId = (int) ($data['parallel_curriculum_id'] ?? 0)
            ?: (int) optional($curricula->first())->id;
        $sessionId = (int) ($data['session_id'] ?? 0)
            ?: (int) optional($sessions->firstWhere('is_current', true) ?? $sessions->first())->id;

        $curriculum = $curriculumId
            ? ParallelCurriculum::query()
                ->where('tenant_id', $tenantId)
                ->with([
                    'classes' => fn ($query) => $query->where('is_active', true)
                        ->with([
                            'arms' => fn ($armQuery) => $armQuery->where('is_active', true),
                            'subjectAssignments' => fn ($subjectQuery) => $subjectQuery
                                ->where('is_active', true)
                                ->with(['subject', 'teacher']),
                        ]),
                ])
                ->find($curriculumId)
            : null;

        $classes = $curriculum?->classes ?? collect();
        $classId = (int) ($data['class_id'] ?? 0) ?: (int) optional($classes->first())->id;
        $class = $classes->firstWhere('id', $classId);

        $arms = $class?->arms ?? collect();
        $armId = (int) ($data['arm_id'] ?? 0) ?: (int) optional($arms->first())->id;
        $arm = $arms->firstWhere('id', $armId);

        $terms = $terms->where('session_id', $sessionId)->values();

        $requestedTermId = (int) ($data['term_id'] ?? 0);
        $termId = $terms->contains('id', $requestedTermId)
            ? $requestedTermId
            : (int) optional($terms->firstWhere('is_current', true) ?? $terms->first())->id;

        $date = $data['date'] ?? now()->toDateString();

        $periods = $arm && $sessionId
            ? ParallelCurriculumTimetablePeriod::query()
                ->with(['subject', 'teacher'])
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_class_arm_id', $arm->id)
                ->where('session_id', $sessionId)
                ->get()
                ->sortBy(fn (ParallelCurriculumTimetablePeriod $period) =>
                    array_search($period->day_of_week, ['monday','tuesday','wednesday','thursday','friday'], true)
                    .'-'.substr((string) $period->start_time, 0, 5)
                )
                ->values()
            : collect();

        $canSaveAttendance = $arm
            ? $this->operations->canMarkAttendance($user, $arm)
            : false;

        $attendance = null;
        if ($arm && $termId && $canSaveAttendance) {
            $sheet = $this->operations->attendanceSheet($user, $arm->id, $termId, $date);
            $attendance = [
                'date' => $sheet['date'],
                'version' => $sheet['version'],
                'students' => $sheet['enrolments']->map(function ($enrolment) use ($sheet): array {
                    $record = $sheet['records']->get($enrolment->id);

                    return [
                        'enrolment_id' => $enrolment->id,
                        'student_id' => $enrolment->student_id,
                        'name' => $enrolment->student?->full_name ?? 'Student',
                        'admission_number' => $enrolment->student?->admission_number,
                        'status' => $record?->status,
                        'remark' => $record?->remark,
                    ];
                })->values(),
            ];
        }

        return response()->json([
            'contract_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'selected' => [
                'parallel_curriculum_id' => $curriculum?->id,
                'session_id' => $sessionId ?: null,
                'term_id' => $termId ?: null,
                'class_id' => $class?->id,
                'arm_id' => $arm?->id,
                'date' => $date,
            ],
            'capabilities' => [
                'manage_timetable' => $this->operations->canManageTimetable($user),
                'save_attendance' => $canSaveAttendance,
            ],
            'curricula' => $curricula->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
            ])->values(),
            'sessions' => $sessions->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'is_current' => (bool) $item->is_current,
            ])->values(),
            'terms' => $terms->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'session_id' => $item->session_id,
                'session_name' => $item->session?->name,
                'is_current' => (bool) $item->is_current,
            ])->values(),
            'classes' => $classes->map(function ($item) use ($tenantId): array {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'code' => $item->code,
                    'arms' => $item->arms->map(fn ($armItem) => [
                        'id' => $armItem->id,
                        'name' => $armItem->name,
                        'code' => $armItem->code,
                        'capacity' => $armItem->capacity,
                    ])->values(),
                    'subjects' => $item->subjectAssignments->map(function ($assignment) use ($tenantId, $item): array {
                        return [
                            'id' => $assignment->parallel_curriculum_subject_id,
                            'name' => $assignment->subject?->name ?? 'Subject',
                            'code' => $assignment->subject?->code,
                            'teacher_id' => $assignment->teacher_id,
                            'teacher_name' => $assignment->teacher?->name,
                            'class_id' => $item->id,
                        ];
                    })->values(),
                ];
            })->values(),
            'periods' => $periods->map(fn (ParallelCurriculumTimetablePeriod $period) => $this->periodPayload($period))->values(),
            'attendance' => $attendance,
        ]);
    }

    public function storePeriod(Request $request)
    {
        $tenantId = (int) $request->user()->tenant_id;
        $data = $request->validate([
            'parallel_curriculum_class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'parallel_curriculum_class_arm_id' => [
                'required',
                Rule::exists('parallel_curriculum_class_arms', 'id')->where('tenant_id', $tenantId),
            ],
            'parallel_curriculum_subject_id' => [
                'required',
                Rule::exists('parallel_curriculum_subjects', 'id')->where('tenant_id', $tenantId),
            ],
            'session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id')->where('tenant_id', $tenantId),
            ],
            'day_of_week' => ['required', Rule::in(['monday','tuesday','wednesday','thursday','friday'])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'venue' => ['nullable', 'string', 'max:100'],
        ]);

        $period = $this->operations->createPeriod($request->user(), $data);

        return response()->json([
            'message' => 'Parallel timetable period added.',
            'period' => $this->periodPayload($period),
        ], 201);
    }

    public function destroyPeriod(Request $request, ParallelCurriculumTimetablePeriod $period)
    {
        $this->operations->deletePeriod($request->user(), $period);

        return response()->json(['message' => 'Parallel timetable period removed.']);
    }

    public function saveAttendance(Request $request)
    {
        $tenantId = (int) $request->user()->tenant_id;
        $data = $request->validate([
            'parallel_curriculum_class_arm_id' => [
                'required',
                Rule::exists('parallel_curriculum_class_arms', 'id')->where('tenant_id', $tenantId),
            ],
            'term_id' => [
                'required',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'version' => ['nullable', 'string', 'max:64'],
            'records' => ['required', 'array'],
            'records.*.enrolment_id' => ['required', 'integer'],
            'records.*.status' => ['required', Rule::in(['present','absent','late','excused'])],
            'records.*.remark' => ['nullable', 'string', 'max:200'],
        ]);

        $result = $this->operations->saveAttendance(
            $request->user(),
            (int) $data['parallel_curriculum_class_arm_id'],
            (int) $data['term_id'],
            $data['attendance_date'],
            $data['records'],
            $data['version'] ?? null,
        );

        return response()->json([
            'message' => "Parallel attendance saved for {$result['saved']} learner(s).",
            'saved' => $result['saved'],
            'version' => $result['version'],
            'summary' => $result['summary'],
        ]);
    }

    private function periodPayload(ParallelCurriculumTimetablePeriod $period): array
    {
        return [
            'id' => $period->id,
            'class_id' => $period->parallel_curriculum_class_id,
            'arm_id' => $period->parallel_curriculum_class_arm_id,
            'subject_id' => $period->parallel_curriculum_subject_id,
            'subject' => $period->subject?->name ?? 'Subject',
            'teacher_id' => $period->teacher_id,
            'teacher' => $period->teacher?->name,
            'day_of_week' => $period->day_of_week,
            'start_time' => substr((string) $period->start_time, 0, 5),
            'end_time' => substr((string) $period->end_time, 0, 5),
            'venue' => $period->venue,
        ];
    }
}
