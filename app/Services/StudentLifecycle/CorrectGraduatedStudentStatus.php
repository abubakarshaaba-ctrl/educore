<?php

namespace App\Services\StudentLifecycle;

use App\Models\ClassArm;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentStatusHistory;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CorrectGraduatedStudentStatus
{
    use ResolvesActiveAcademicContext;

    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
    }

    public function execute(User $actor, Student $student, array $data, ?Request $request = null): Student
    {
        $tenantId = (int) $actor->tenant_id;

        if (!$actor->can('student.status.correct-graduation')) {
            throw ValidationException::withMessages([
                'student' => 'You do not have permission to correct graduated student status.',
            ]);
        }

        Validator::make($data, [
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'class_arm_id' => ['required', 'integer'],
            'document_path' => ['nullable', 'string', 'max:2048'],
        ])->validate();

        return DB::transaction(function () use ($actor, $student, $data, $request, $tenantId) {
            $lockedStudent = Student::where('tenant_id', $tenantId)
                ->whereKey($student->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedStudent->status !== Student::STATUS_GRADUATED) {
                throw ValidationException::withMessages([
                    'student' => 'Only graduated students can use graduation correction.',
                ]);
            }

            $currentEnrollments = StudentEnrollment::where('tenant_id', $tenantId)
                ->where('student_id', $lockedStudent->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->get();

            if ($currentEnrollments->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'student' => 'This student already has a current enrolment. Run enrolment repair before correction.',
                ]);
            }

            $context = $this->activeAcademicContext($tenantId);
            $classArm = ClassArm::where('tenant_id', $tenantId)
                ->whereKey($data['class_arm_id'])
                ->firstOrFail();

            $oldGraduationDate = optional($lockedStudent->graduation_date)->toDateString();

            $enrollment = StudentEnrollment::create([
                'tenant_id' => $tenantId,
                'student_id' => $lockedStudent->id,
                'class_arm_id' => $classArm->id,
                'session_id' => $context['session']->id,
                'term_id' => $context['term']->id,
                'start_date' => $data['effective_date'],
                'end_date' => null,
                'is_current' => true,
                'status' => StudentEnrollment::STATUS_ACTIVE,
                'created_by' => $actor->id,
            ]);

            $lockedStudent->forceFill([
                'status' => Student::STATUS_ACTIVE,
                'current_class_arm_id' => $classArm->id,
                'graduation_date' => null,
            ])->save();
            $syncedSubjects = $lockedStudent->fresh()->syncCompulsorySubjects($context['session']->id);

            $history = StudentStatusHistory::create([
                'tenant_id' => $tenantId,
                'student_id' => $lockedStudent->id,
                'old_status' => Student::STATUS_GRADUATED,
                'new_status' => Student::STATUS_ACTIVE,
                'effective_date' => $data['effective_date'],
                'reason' => $data['reason'],
                'document_path' => $data['document_path'] ?? null,
                'changed_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $lockedStudent,
                'student.graduation_corrected',
                [
                    'status' => Student::STATUS_GRADUATED,
                    'graduation_date' => $oldGraduationDate,
                ],
                [
                    'status' => Student::STATUS_ACTIVE,
                    'graduation_date' => null,
                    'status_history_id' => $history->id,
                    'new_enrollment_id' => $enrollment->id,
                    'synced_subjects' => $syncedSubjects,
                    'current_class_arm_id' => $classArm->id,
                ],
                $data['reason'],
                $request
            );

            return $lockedStudent->fresh();
        });
    }
}
