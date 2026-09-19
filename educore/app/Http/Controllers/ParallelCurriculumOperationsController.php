<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumTimetablePeriod;
use App\Models\Term;
use App\Services\ParallelCurriculumOperationsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParallelCurriculumOperationsController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumOperationsService $operations,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
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

        $curriculumId = $request->integer('parallel_curriculum_id')
            ?: (int) optional($curricula->first())->id;
        $sessionId = $request->integer('session_id')
            ?: (int) optional($sessions->firstWhere('is_current', true) ?? $sessions->first())->id;

        $selectedCurriculum = $curriculumId
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

        $classes = $selectedCurriculum?->classes ?? collect();
        $classId = $request->integer('class_id') ?: (int) optional($classes->first())->id;
        $selectedClass = $classes->firstWhere('id', $classId);

        $arms = $selectedClass?->arms ?? collect();
        $armId = $request->integer('arm_id') ?: (int) optional($arms->first())->id;
        $selectedArm = $arms->firstWhere('id', $armId);

        $termId = $request->integer('term_id');
        if (! $termId) {
            $termId = (int) optional(
                $terms->firstWhere('is_current', true)
                ?? $terms->firstWhere('session_id', $sessionId)
                ?? $terms->first()
            )->id;
        }

        $date = $request->input('date', now()->toDateString());

        $periods = collect();
        if ($selectedArm && $sessionId) {
            $periods = ParallelCurriculumTimetablePeriod::query()
                ->with(['subject', 'teacher'])
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_class_arm_id', $selectedArm->id)
                ->where('session_id', $sessionId)
                ->orderByRaw("CASE day_of_week
                    WHEN 'monday' THEN 1
                    WHEN 'tuesday' THEN 2
                    WHEN 'wednesday' THEN 3
                    WHEN 'thursday' THEN 4
                    WHEN 'friday' THEN 5
                    ELSE 6 END")
                ->orderBy('start_time')
                ->get();
        }

        $attendance = null;
        $canMarkAttendance = false;
        if ($selectedArm && $termId) {
            $canMarkAttendance = $this->operations->canMarkAttendance($user, $selectedArm);
            if ($canMarkAttendance) {
                $attendance = $this->operations->attendanceSheet(
                    $user,
                    (int) $selectedArm->id,
                    (int) $termId,
                    $date,
                );
            }
        }

        return view('parallel-curriculum.operations', [
            'curricula' => $curricula,
            'sessions' => $sessions,
            'terms' => $terms,
            'selectedCurriculum' => $selectedCurriculum,
            'selectedClass' => $selectedClass,
            'selectedArm' => $selectedArm,
            'curriculumId' => $curriculumId,
            'sessionId' => $sessionId,
            'termId' => $termId,
            'classId' => $classId,
            'armId' => $armId,
            'date' => $date,
            'periods' => $periods,
            'attendance' => $attendance,
            'canManageTimetable' => $this->operations->canManageTimetable($user),
            'canMarkAttendance' => $canMarkAttendance,
        ]);
    }

    public function storePeriod(Request $request)
    {
        $tenantId = (int) $request->user()->tenant_id;
        $validated = $request->validate([
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

        $period = $this->operations->createPeriod($request->user(), $validated);

        return redirect()->route('parallel-curriculum.operations.index', [
            'parallel_curriculum_id' => $period->parallel_curriculum_id,
            'session_id' => $period->session_id,
            'class_id' => $period->parallel_curriculum_class_id,
            'arm_id' => $period->parallel_curriculum_class_arm_id,
        ])->with('success', 'Parallel timetable period added.');
    }

    public function destroyPeriod(Request $request, ParallelCurriculumTimetablePeriod $period)
    {
        $context = [
            'parallel_curriculum_id' => $period->parallel_curriculum_id,
            'session_id' => $period->session_id,
            'class_id' => $period->parallel_curriculum_class_id,
            'arm_id' => $period->parallel_curriculum_class_arm_id,
        ];

        $this->operations->deletePeriod($request->user(), $period);

        return redirect()->route('parallel-curriculum.operations.index', $context)
            ->with('success', 'Parallel timetable period removed.');
    }

    public function saveAttendance(Request $request)
    {
        $tenantId = (int) $request->user()->tenant_id;
        $validated = $request->validate([
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
            (int) $validated['parallel_curriculum_class_arm_id'],
            (int) $validated['term_id'],
            $validated['attendance_date'],
            $validated['records'],
            $validated['version'] ?? null,
        );

        $arm = ParallelCurriculumClassArm::query()
            ->with('curriculumClass')
            ->where('tenant_id', $tenantId)
            ->findOrFail((int) $validated['parallel_curriculum_class_arm_id']);

        return redirect()->route('parallel-curriculum.operations.index', [
            'parallel_curriculum_id' => $arm->curriculumClass?->parallel_curriculum_id,
            'session_id' => optional(Term::find($validated['term_id']))->session_id,
            'term_id' => $validated['term_id'],
            'class_id' => $arm->parallel_curriculum_class_id,
            'arm_id' => $arm->id,
            'date' => $validated['attendance_date'],
        ])->with('success', "Parallel attendance saved for {$result['saved']} learner(s).");
    }
}
