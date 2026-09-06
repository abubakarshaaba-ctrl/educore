<?php

namespace App\Services\Mobile;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MobileClassAccessService
{
    public function canManageAllClasses(User $user): bool
    {
        return $user->tenant_id !== null
            && ! $user->isSuperAdmin()
            && $user->canAccessExactModule('students');
    }

    public function canViewClass(User $user, ClassArm|int $classArm): bool
    {
        $classArmId = $classArm instanceof ClassArm ? $classArm->id : $classArm;

        if ($this->canManageAllClasses($user)) {
            return ClassArm::whereKey($classArmId)->exists();
        }

        if (ClassArm::whereKey($classArmId)->where('form_tutor_id', $user->id)->exists()) {
            return true;
        }

        return $this->subjectAssignments($user, $classArmId)->isNotEmpty();
    }

    public function canMarkAttendance(User $user, ClassArm|int $classArm): bool
    {
        if ($user->isAccountant() || $user->tenant_id === null || $user->isSuperAdmin()) {
            return false;
        }

        $classArmId = $classArm instanceof ClassArm ? $classArm->id : $classArm;

        return $this->canManageAllClasses($user)
            || ClassArm::whereKey($classArmId)->where('form_tutor_id', $user->id)->exists();
    }

    public function accessibleClasses(User $user): Builder
    {
        abort_if(
            $user->tenant_id === null || $user->isSuperAdmin() || $user->isParent() || $user->isStudent(),
            403,
            'A school staff account is required to access classes.'
        );
        abort_if($user->isAccountant(), 403, 'Accountants do not have teaching assignments.');

        $query = ClassArm::query()->with(['classLevel', 'academicTrack', 'formTutor:id,name']);
        if ($this->canManageAllClasses($user)) {
            return $query;
        }

        $subjectArmIds = ClassArmSubject::where('teacher_id', $user->id)
            ->when($this->currentSessionId(), fn (Builder $builder, int $sessionId) => $builder->where('session_id', $sessionId))
            ->pluck('class_arm_id');

        return $query->where(function (Builder $builder) use ($user, $subjectArmIds): void {
            $builder->where('form_tutor_id', $user->id)
                ->orWhereIn('id', $subjectArmIds);
        });
    }

    public function classPayload(User $user, ClassArm $classArm): array
    {
        $classArm->loadMissing(['classLevel', 'academicTrack', 'formTutor:id,name']);
        $assignments = $this->subjectAssignments($user, $classArm->id, includeAll: $this->canManageAllClasses($user));
        $roles = [];
        if ($this->canManageAllClasses($user)) {
            $roles[] = 'administrator';
        }
        if ((int) $classArm->form_tutor_id === (int) $user->id) {
            $roles[] = 'form_tutor';
        }
        if ($assignments->contains(fn (ClassArmSubject $assignment) => (int) $assignment->teacher_id === (int) $user->id)) {
            $roles[] = 'subject_teacher';
        }

        return [
            'id' => $classArm->id,
            'name' => trim(($classArm->classLevel?->name ?? '').' '.$classArm->name),
            'level' => $classArm->classLevel ? [
                'id' => $classArm->classLevel->id,
                'name' => $classArm->classLevel->name,
            ] : null,
            'track' => $classArm->academicTrack ? [
                'id' => $classArm->academicTrack->id,
                'name' => $classArm->academicTrack->name,
            ] : null,
            'form_tutor' => $classArm->formTutor ? [
                'id' => $classArm->formTutor->id,
                'name' => $classArm->formTutor->name,
            ] : null,
            'role' => $roles[0] ?? null,
            'roles' => $roles,
            'subject' => count($roles) === 1 && $roles[0] === 'subject_teacher'
                ? $this->firstOwnSubject($assignments, $user)
                : null,
            'subjects' => $assignments
                ->filter(fn (ClassArmSubject $assignment) => $assignment->subject !== null)
                ->map(fn (ClassArmSubject $assignment) => [
                    'id' => $assignment->subject->id,
                    'name' => $assignment->subject->name,
                    'code' => $assignment->subject->code,
                    'teacher_id' => $assignment->teacher_id,
                    'teacher_name' => $assignment->teacher?->name,
                ])
                ->unique('id')
                ->values(),
            'students_count' => $classArm->students()->active()->count(),
            'capabilities' => [
                'view_students' => true,
                'mark_attendance' => $this->canMarkAttendance($user, $classArm),
                'enter_scores' => $this->canManageAllClasses($user)
                    || $assignments->contains(fn (ClassArmSubject $assignment) => (int) $assignment->teacher_id === (int) $user->id),
                'view_results' => $user->canAccessModule('reports'),
                'plan_lessons' => $user->canAccessModule('lesson-planner'),
            ],
        ];
    }

    private function subjectAssignments(User $user, int $classArmId, bool $includeAll = false): Collection
    {
        return ClassArmSubject::with(['subject:id,name,code', 'teacher:id,name'])
            ->where('class_arm_id', $classArmId)
            ->when($this->currentSessionId(), fn (Builder $builder, int $sessionId) => $builder->where('session_id', $sessionId))
            ->when(! $includeAll, function (Builder $builder) use ($user): void {
                $builder->where('teacher_id', $user->id);
            })
            ->orderBy('subject_id')
            ->get();
    }

    private function firstOwnSubject(Collection $assignments, User $user): ?array
    {
        $assignment = $assignments->first(
            fn (ClassArmSubject $item) => (int) $item->teacher_id === (int) $user->id && $item->subject !== null
        );

        return $assignment ? [
            'id' => $assignment->subject->id,
            'name' => $assignment->subject->name,
        ] : null;
    }

    private function currentSessionId(): ?int
    {
        return AcademicSession::current()->value('id');
    }
}
