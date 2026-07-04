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

class ReactivateStudent
{
    use ResolvesActiveAcademicContext;

    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
    }

    public function execute(User $actor, Student $student, array $data, ?Request $request = null): Student
    {
        $tenantId = (int) $actor->tenant_id;

        if (!$actor->can('student.reactivate')) {
            throw ValidationException::withMessages([
                'student' => 'You do not have permission to reactivate students.',
            ]);
        }

        Validator::make($data, [
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'class_arm_id' => ['nullable', 'integer'],
            'document_path' => ['nullable', 'string', 'max:2048'],
        ])->validate();

        return DB::transaction(function () use ($actor, $student, $data, $request, $tenantId) {
            $lockedStudent = Student::where('tenant_id', $tenantId)
                ->whereKey($student->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $lockedStudent->status;

            if (!StudentLifecycleRules::canReactivate($oldStatus)) {
                throw ValidationException::withMessages([
                    'student' => 'Only suspended, left, or withdrawn students can be reactivated here.',
                ]);
            }

            $currentEnrollments = StudentEnrollment::where('tenant_id', $tenantId)
                ->where('student_id', $lockedStudent->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->get();

            $createdEnrollmentId = null;
            $syncedSubjects = 0;

            if ($oldStatus === Student::STATUS_SUSPENDED) {
                if ($currentEnrollments->count() !== 1) {
                    throw ValidationException::withMessages([
                        'student' => 'Suspended students must have exactly one current enrolment before reactivation.',
                    ]);
                }
            } else {
                if ($currentEnrollments->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'student' => 'This student already has a current enrolment. Run enrolment repair before reactivation.',
                    ]);
                }

                $context = $this->activeAcademicContext($tenantId);
                $classArm = ClassArm::where('tenant_id', $tenantId)
                    ->whereKey($data['class_arm_id'])
                    ->firstOrFail();

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

                $createdEnrollmentId = $enrollment->id;
                $lockedStudent->forceFill(['current_class_arm_id' => $classArm->id])->save();
                $syncedSubjects = $lockedStudent->fresh()->syncCompulsorySubjects($context['session']->id);
            }

            $lockedStudent->forceFill(['status' => Student::STATUS_ACTIVE])->save();

            $history = StudentStatusHistory::create([
                'tenant_id' => $tenantId,
                'student_id' => $lockedStudent->id,
                'old_status' => $oldStatus,
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
                'student.reactivated',
                [
                    'status' => $oldStatus,
                    'current_enrollment_ids' => $currentEnrollments->pluck('id')->all(),
                ],
                [
                    'status' => Student::STATUS_ACTIVE,
                    'status_history_id' => $history->id,
                    'new_enrollment_id' => $createdEnrollmentId,
                    'synced_subjects' => $syncedSubjects,
                    'current_class_arm_id' => $lockedStudent->current_class_arm_id,
                ],
                $data['reason'],
                $request
            );

            return $lockedStudent->fresh();
        });
    }
}
