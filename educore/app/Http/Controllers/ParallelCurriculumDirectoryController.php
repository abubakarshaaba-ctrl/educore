<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\Student;
use App\Models\User;
use App\Services\ParallelCurriculumService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ParallelCurriculumDirectoryController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumService $service,
    ) {}

    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function assertAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user
                && $user->tenant_id
                && $this->service->enabledForTenant((int) $user->tenant_id),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );

        abort_unless(
            $this->service->canManageLifecycle($user),
            403,
            'Only academic administrators can view parallel curriculum directories.'
        );
    }

    public function classList(Request $request)
    {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        $armSchemaReady = Schema::hasTable('parallel_curriculum_class_arms');

        $curriculumRelations = [
            'classes' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name'),
        ];
        if ($armSchemaReady) {
            $curriculumRelations['classes.arms'] = fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name');
        }

        $curricula = ParallelCurriculum::with($curriculumRelations)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $sessions = AcademicSession::where('tenant_id', $tenantId)
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $selectedCurriculum = $this->selectedCurriculum(
            $curricula,
            $request->integer('parallel_curriculum_id')
        );
        $selectedSession = $this->selectedSession(
            $sessions,
            $request->integer('session_id')
        );

        $classes = $selectedCurriculum?->classes?->values() ?? collect();
        if (! $armSchemaReady) {
            $classes->each(fn (ParallelCurriculumClass $class) =>
                $class->setRelation('arms', collect())
            );
        }

        $selectedClass = $classes->firstWhere(
            'id',
            $request->integer('class_id')
        ) ?? $classes->first();

        $arms = $selectedClass?->arms?->values() ?? collect();
        $allArms = $request->query('arm_id') === 'all';
        $selectedArm = null;

        if (! $allArms && $selectedClass) {
            $requestedArmId = $request->integer('arm_id');
            $selectedArm = $requestedArmId
                ? ($arms->firstWhere('id', $requestedArmId) ?? $arms->first())
                : $arms->first();
        }

        $enrolments = collect();

        if ($selectedCurriculum && $selectedSession && $selectedClass) {
            $enrolmentRelations = ['student.currentClassArm.classLevel'];
            if ($armSchemaReady) {
                $enrolmentRelations[] = 'curriculumClassArm';
            }

            $query = ParallelCurriculumEnrolment::with($enrolmentRelations)
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_id', $selectedCurriculum->id)
                ->where('parallel_curriculum_class_id', $selectedClass->id)
                ->where('session_id', $selectedSession->id)
                ->where('is_active', true)
                ->whereHas('student', fn ($student) =>
                    $student->where('status', Student::STATUS_ACTIVE)
                );

            if ($selectedArm) {
                $query->where(
                    'parallel_curriculum_class_arm_id',
                    $selectedArm->id
                );
            }

            $enrolments = $query
                ->get()
                ->sortBy(fn (ParallelCurriculumEnrolment $enrolment) =>
                    mb_strtolower(
                        trim(
                            ($enrolment->student?->last_name ?? '').' '.
                            ($enrolment->student?->first_name ?? '').' '.
                            ($enrolment->student?->middle_name ?? '')
                        )
                    )
                )
                ->values();
        }

        $maleCount = $enrolments->filter(fn ($enrolment) =>
            strtolower((string) $enrolment->student?->gender) === 'male'
        )->count();
        $femaleCount = $enrolments->filter(fn ($enrolment) =>
            strtolower((string) $enrolment->student?->gender) === 'female'
        )->count();

        return view('parallel-curriculum.directories.class-list', compact(
            'curricula',
            'sessions',
            'selectedCurriculum',
            'selectedSession',
            'classes',
            'selectedClass',
            'arms',
            'selectedArm',
            'allArms',
            'enrolments',
            'maleCount',
            'femaleCount'
        ));
    }

    public function teacherList(Request $request)
    {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        $curricula = ParallelCurriculum::with([
                'classes' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedCurriculum = $this->selectedCurriculum(
            $curricula,
            $request->integer('parallel_curriculum_id')
        );

        $availableClasses = $selectedCurriculum?->classes?->values() ?? collect();
        $selectedClassId = $request->integer('class_id') ?: null;

        if (
            $selectedClassId
            && ! $availableClasses->contains(fn ($class) =>
                (int) $class->id === (int) $selectedClassId
            )
        ) {
            $selectedClassId = null;
        }

        $classes = collect();
        if ($selectedCurriculum) {
            $armSchemaReady = Schema::hasTable('parallel_curriculum_class_arms');
            $relations = [
                'curriculum',
                'subjectAssignments' => fn ($query) => $query
                    ->where('is_active', true),
                'subjectAssignments.subject',
            ];

            if ($armSchemaReady) {
                $relations['arms'] = fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name');
            }

            if (
                $armSchemaReady
                && Schema::hasTable('parallel_curriculum_arm_subject_teachers')
            ) {
                $relations['arms.subjectTeachers'] = fn ($query) =>
                    $query->where('is_active', true);
            }

            $classes = ParallelCurriculumClass::with($relations)
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_id', $selectedCurriculum->id)
                ->where('is_active', true)
                ->when(
                    $selectedClassId,
                    fn ($query) => $query->whereKey($selectedClassId)
                )
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            if (! $armSchemaReady) {
                $classes->each(fn (ParallelCurriculumClass $class) =>
                    $class->setRelation('arms', collect())
                );
            }
        }

        $assignmentRows = collect();
        $unassignedSlots = 0;

        foreach ($classes as $class) {
            $assignments = $class->subjectAssignments
                ->filter(fn ($assignment) => $assignment->subject?->is_active)
                ->values();
            $arms = $class->arms
                ->where('is_active', true)
                ->values();

            if ($arms->isEmpty()) {
                foreach ($assignments as $assignment) {
                    if (! $assignment->teacher_id) {
                        $unassignedSlots++;
                        continue;
                    }

                    $assignmentRows->push([
                        'teacher_id' => (int) $assignment->teacher_id,
                        'assignment_type' => 'Subject Teacher',
                        'class_arm' => $class->name,
                        'subject' => $assignment->subject?->name ?: 'Subject',
                    ]);
                }

                continue;
            }

            foreach ($arms as $arm) {
                if ($arm->usesClassTeacherModel()) {
                    if (! $arm->class_teacher_id) {
                        $unassignedSlots++;
                        continue;
                    }

                    $assignmentRows->push([
                        'teacher_id' => (int) $arm->class_teacher_id,
                        'assignment_type' => 'Class/Form Teacher',
                        'class_arm' => trim($class->name.' '.$arm->name),
                        'subject' => $assignments->isEmpty()
                            ? 'No active subject assigned'
                            : 'All assigned subjects',
                    ]);

                    continue;
                }

                foreach ($assignments as $assignment) {
                    $teacherId = $this->service->effectiveTeacherId(
                        $assignment,
                        $arm
                    );

                    if (! $teacherId) {
                        $unassignedSlots++;
                        continue;
                    }

                    $assignmentRows->push([
                        'teacher_id' => (int) $teacherId,
                        'assignment_type' => 'Subject Teacher',
                        'class_arm' => trim($class->name.' '.$arm->name),
                        'subject' => $assignment->subject?->name ?: 'Subject',
                    ]);
                }
            }
        }

        $teacherIds = $assignmentRows
            ->pluck('teacher_id')
            ->unique()
            ->values();

        $teachers = $teacherIds->isEmpty()
            ? collect()
            : User::where('tenant_id', $tenantId)
                ->whereIn('id', $teacherIds)
                ->get()
                ->keyBy('id');

        $teacherRows = $assignmentRows
            ->groupBy('teacher_id')
            ->map(function (Collection $rows, int|string $teacherId) use ($teachers): ?array {
                $teacher = $teachers->get((int) $teacherId);
                if (! $teacher) {
                    return null;
                }

                $types = $rows->pluck('assignment_type')->unique()->values();

                return [
                    'teacher' => $teacher,
                    'assignment_types' => $types,
                    'class_arms' => $rows->pluck('class_arm')->unique()->sort()->values(),
                    'subjects' => $rows->pluck('subject')->unique()->sort()->values(),
                    'assignment_count' => $rows->count(),
                    'is_active' => (bool) $teacher->is_active
                        && ($teacher->employment_status === null
                            || $teacher->employment_status === User::STAFF_STATUS_ACTIVE),
                ];
            })
            ->filter()
            ->sortBy(fn (array $row) =>
                mb_strtolower((string) $row['teacher']->name)
            )
            ->values();

        $classTeacherCount = $teacherRows
            ->filter(fn (array $row) =>
                $row['assignment_types']->contains('Class/Form Teacher')
            )
            ->count();
        $subjectTeacherCount = $teacherRows
            ->filter(fn (array $row) =>
                $row['assignment_types']->contains('Subject Teacher')
            )
            ->count();

        return view('parallel-curriculum.directories.teacher-list', compact(
            'curricula',
            'selectedCurriculum',
            'availableClasses',
            'selectedClassId',
            'teacherRows',
            'classTeacherCount',
            'subjectTeacherCount',
            'unassignedSlots'
        ));
    }

    private function selectedCurriculum(
        Collection $curricula,
        int $requestedId
    ): ?ParallelCurriculum {
        return $curricula->firstWhere('id', $requestedId)
            ?? $curricula->first();
    }

    private function selectedSession(
        Collection $sessions,
        int $requestedId
    ): ?AcademicSession {
        return $sessions->firstWhere('id', $requestedId)
            ?? $sessions->firstWhere('is_current', true)
            ?? $sessions->first();
    }
}
