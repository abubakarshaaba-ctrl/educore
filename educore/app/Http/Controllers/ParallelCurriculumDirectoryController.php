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
use Illuminate\Support\Facades\DB;
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

            $enrolments = $query->get();

            if (! $armSchemaReady) {
                $enrolments->each(fn (ParallelCurriculumEnrolment $enrolment) =>
                    $enrolment->setRelation('curriculumClassArm', null)
                );
            }

            $enrolments = $enrolments
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

        $user = $request->user();
        $tenantId = $this->tenantId();

        $curricula = DB::table('parallel_curricula')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $requestedCurriculumId = $request->integer('parallel_curriculum_id');
        $selectedCurriculum = $curricula->firstWhere('id', $requestedCurriculumId)
            ?? $curricula->first();

        $availableClasses = collect();
        if ($selectedCurriculum) {
            $availableClasses = DB::table('parallel_curriculum_classes')
                ->where('tenant_id', $tenantId)
                ->where('parallel_curriculum_id', $selectedCurriculum->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'sort_order']);
        }

        $selectedClassId = $request->integer('class_id') ?: null;
        if (
            $selectedClassId
            && ! $availableClasses->contains(fn ($class) =>
                (int) $class->id === (int) $selectedClassId
            )
        ) {
            $selectedClassId = null;
        }

        $classes = $selectedClassId
            ? $availableClasses->where('id', $selectedClassId)->values()
            : $availableClasses->values();

        $classIds = $classes->pluck('id')->map(fn ($id) => (int) $id)->values();

        $assignmentRows = collect();
        $unassignedSlots = 0;

        if ($selectedCurriculum && $classIds->isNotEmpty()) {
            $classSubjectsReady = Schema::hasTable('parallel_curriculum_class_subjects');
            $parallelSubjectsReady = Schema::hasTable('parallel_curriculum_subjects');
            $newSubjectColumnReady = $classSubjectsReady
                && Schema::hasColumn(
                    'parallel_curriculum_class_subjects',
                    'parallel_curriculum_subject_id'
                );
            $legacySubjectColumnReady = $classSubjectsReady
                && Schema::hasColumn(
                    'parallel_curriculum_class_subjects',
                    'subject_id'
                );

            $assignments = collect();

            if ($classSubjectsReady && $newSubjectColumnReady && $parallelSubjectsReady) {
                $assignments = DB::table('parallel_curriculum_class_subjects as pcs')
                    ->join(
                        'parallel_curriculum_subjects as subject',
                        'subject.id',
                        '=',
                        'pcs.parallel_curriculum_subject_id'
                    )
                    ->where('pcs.tenant_id', $tenantId)
                    ->whereIn('pcs.parallel_curriculum_class_id', $classIds)
                    ->where('pcs.is_active', true)
                    ->where('subject.is_active', true)
                    ->get([
                        'pcs.parallel_curriculum_class_id as class_id',
                        'pcs.parallel_curriculum_subject_id as subject_id',
                        'pcs.teacher_id',
                        'subject.name as subject_name',
                    ]);
            } elseif ($classSubjectsReady && $legacySubjectColumnReady) {
                // Compatibility for a deployment interrupted before the
                // independent parallel-subject schema reconciliation completed.
                $assignments = DB::table('parallel_curriculum_class_subjects as pcs')
                    ->join('subjects as subject', 'subject.id', '=', 'pcs.subject_id')
                    ->where('pcs.tenant_id', $tenantId)
                    ->whereIn('pcs.parallel_curriculum_class_id', $classIds)
                    ->where('pcs.is_active', true)
                    ->get([
                        'pcs.parallel_curriculum_class_id as class_id',
                        'pcs.subject_id as subject_id',
                        'pcs.teacher_id',
                        'subject.name as subject_name',
                    ]);
            }

            $assignmentsByClass = $assignments->groupBy(
                fn ($assignment) => (int) $assignment->class_id
            );

            $armsByClass = collect();
            $armModesReady = Schema::hasTable('parallel_curriculum_class_arms');
            $modeColumnReady = $armModesReady
                && Schema::hasColumn(
                    'parallel_curriculum_class_arms',
                    'teaching_assignment_mode'
                );
            $classTeacherColumnReady = $armModesReady
                && Schema::hasColumn(
                    'parallel_curriculum_class_arms',
                    'class_teacher_id'
                );

            if ($armModesReady) {
                $armSelect = [
                    'id',
                    'parallel_curriculum_class_id',
                    'name',
                    'sort_order',
                ];
                if ($modeColumnReady) {
                    $armSelect[] = 'teaching_assignment_mode';
                }
                if ($classTeacherColumnReady) {
                    $armSelect[] = 'class_teacher_id';
                }

                $armsByClass = DB::table('parallel_curriculum_class_arms')
                    ->where('tenant_id', $tenantId)
                    ->whereIn('parallel_curriculum_class_id', $classIds)
                    ->where('is_active', true)
                    ->orderBy('parallel_curriculum_class_id')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get($armSelect)
                    ->groupBy(fn ($arm) =>
                        (int) $arm->parallel_curriculum_class_id
                    );
            }

            $overrideMap = collect();
            if (
                Schema::hasTable('parallel_curriculum_arm_subject_teachers')
                && Schema::hasColumn(
                    'parallel_curriculum_arm_subject_teachers',
                    'parallel_curriculum_subject_id'
                )
            ) {
                $overrideMap = DB::table('parallel_curriculum_arm_subject_teachers')
                    ->where('tenant_id', $tenantId)
                    ->whereIn('parallel_curriculum_class_id', $classIds)
                    ->where('is_active', true)
                    ->get([
                        'parallel_curriculum_class_arm_id as arm_id',
                        'parallel_curriculum_subject_id as subject_id',
                        'teacher_id',
                    ])
                    ->keyBy(fn ($row) =>
                        (int) $row->arm_id.':'.(int) $row->subject_id
                    );
            }

            foreach ($classes as $class) {
                $classAssignments = $assignmentsByClass
                    ->get((int) $class->id, collect())
                    ->values();
                $arms = $armsByClass
                    ->get((int) $class->id, collect())
                    ->values();

                if ($arms->isEmpty()) {
                    foreach ($classAssignments as $assignment) {
                        if (! $assignment->teacher_id) {
                            $unassignedSlots++;
                            continue;
                        }

                        $assignmentRows->push([
                            'teacher_id' => (int) $assignment->teacher_id,
                            'assignment_type' => 'Subject Teacher',
                            'class_arm' => (string) $class->name,
                            'subject' => (string) $assignment->subject_name,
                        ]);
                    }

                    continue;
                }

                foreach ($arms as $arm) {
                    $mode = $modeColumnReady
                        ? ((string) ($arm->teaching_assignment_mode ?? 'subject_based'))
                        : 'subject_based';
                    $classTeacherId = $classTeacherColumnReady
                        ? (int) ($arm->class_teacher_id ?? 0)
                        : 0;
                    $classArmLabel = trim($class->name.' '.$arm->name);

                    foreach ($classAssignments as $assignment) {
                        if ($mode === 'class_teacher') {
                            $teacherId = $classTeacherId ?: null;
                            $assignmentType = 'Class/Form Teacher';
                        } else {
                            $override = $overrideMap->get(
                                (int) $arm->id.':'.(int) $assignment->subject_id
                            );
                            $teacherId = $override?->teacher_id
                                ?: $assignment->teacher_id;
                            $assignmentType = 'Subject Teacher';
                        }

                        if (! $teacherId) {
                            $unassignedSlots++;
                            continue;
                        }

                        $assignmentRows->push([
                            'teacher_id' => (int) $teacherId,
                            'assignment_type' => $assignmentType,
                            'class_arm' => $classArmLabel,
                            'subject' => (string) $assignment->subject_name,
                        ]);
                    }
                }
            }
        }

        $teacherIds = $assignmentRows
            ->pluck('teacher_id')
            ->filter()
            ->unique()
            ->values();

        $teachers = $teacherIds->isEmpty()
            ? collect()
            : DB::table('users')
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $teacherIds)
                ->when(
                    Schema::hasColumn('users', 'deleted_at'),
                    fn ($query) => $query->whereNull('deleted_at')
                )
                ->get([
                    'id',
                    'name',
                    'staff_id',
                    'phone',
                    'role',
                    'is_active',
                    'employment_status',
                ])
                ->keyBy('id');

        $teacherRows = $assignmentRows
            ->groupBy('teacher_id')
            ->map(function (Collection $rows, $teacherId) use ($teachers): ?array {
                $teacher = $teachers->get((int) $teacherId);
                if (! $teacher) {
                    return null;
                }

                $role = (string) ($teacher->role ?? 'staff');
                $roleLabel = User::ROLE_LABELS[$role]
                    ?? ucwords(str_replace('_', ' ', $role));

                return [
                    'teacher' => $teacher,
                    'role_label' => $roleLabel,
                    'assignment_types' => $rows
                        ->pluck('assignment_type')
                        ->unique()
                        ->values(),
                    'class_arms' => $rows
                        ->pluck('class_arm')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values(),
                    'subjects' => $rows
                        ->pluck('subject')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values(),
                    'assignment_count' => $rows->count(),
                    'is_active' => (bool) $teacher->is_active
                        && (
                            $teacher->employment_status === null
                            || $teacher->employment_status
                                === User::STAFF_STATUS_ACTIVE
                        ),
                ];
            })
            ->filter()
            ->sortBy(fn (array $row) =>
                strtolower((string) $row['teacher']->name)
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

        $schoolName = DB::table('tenants')
            ->where('id', $tenantId)
            ->value('name') ?: 'School';

        return view('parallel-curriculum.directories.teacher-list', compact(
            'curricula',
            'selectedCurriculum',
            'availableClasses',
            'selectedClassId',
            'teacherRows',
            'classTeacherCount',
            'subjectTeacherCount',
            'unassignedSlots',
            'schoolName'
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
