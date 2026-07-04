<?php

namespace App\Services\StudentLifecycle;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentStatusHistory;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ChangeStudentStatus
{
    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
    }

    public function execute(User $actor, Student $student, array $data, ?Request $request = null): Student
    {
        $tenantId = (int) $actor->tenant_id;

        if (!$actor->can('student.status.approve')) {
            throw ValidationException::withMessages([
                'status' => 'Direct lifecycle changes require status approval permission.',
            ]);
        }

        Validator::make($data, [
            'new_status' => ['required', 'string'],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'destination_school' => ['required_if:new_status,' . Student::STATUS_TRANSFERRED_OUT, 'nullable', 'string', 'max:255'],
            'transfer_certificate_number' => ['nullable', 'string', 'max:100'],
            'document_path' => ['nullable', 'string', 'max:2048'],
        ])->validate();

        return DB::transaction(function () use ($actor, $student, $data, $request, $tenantId) {
            $lockedStudent = Student::where('tenant_id', $tenantId)
                ->whereKey($student->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $lockedStudent->status;
            $newStatus = $data['new_status'];

            if (!StudentLifecycleRules::canChangeDirectly($oldStatus, $newStatus)) {
                throw ValidationException::withMessages([
                    'new_status' => 'This status transition is not allowed from the current student status.',
                ]);
            }

            $currentEnrollments = StudentEnrollment::where('tenant_id', $tenantId)
                ->where('student_id', $lockedStudent->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->get();

            if ($currentEnrollments->count() !== 1) {
                throw ValidationException::withMessages([
                    'new_status' => 'The student must have exactly one current enrolment before changing lifecycle status.',
                ]);
            }

            $oldValues = [
                'status' => $oldStatus,
                'graduation_date' => optional($lockedStudent->graduation_date)->toDateString(),
                'current_enrollment_ids' => $currentEnrollments->pluck('id')->all(),
            ];

            $updates = ['status' => $newStatus];
            if ($newStatus === Student::STATUS_GRADUATED) {
                $updates['graduation_date'] = $data['effective_date'];
            }

            $lockedStudent->forceFill($updates)->save();

            if (StudentLifecycleRules::requiresClosedEnrollmentOnExit($newStatus) && $currentEnrollments->count() === 1) {
                $currentEnrollments->first()->forceFill([
                    'is_current' => false,
                    'end_date' => $data['effective_date'],
                    'status' => StudentLifecycleRules::enrollmentClosedStatusFor($newStatus),
                    'ended_by' => $actor->id,
                    'ended_reason' => $data['reason'],
                ])->save();
            }

            $history = StudentStatusHistory::create([
                'tenant_id' => $tenantId,
                'student_id' => $lockedStudent->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'effective_date' => $data['effective_date'],
                'reason' => $data['reason'],
                'destination_school' => $data['destination_school'] ?? null,
                'transfer_certificate_number' => $data['transfer_certificate_number'] ?? null,
                'document_path' => $data['document_path'] ?? null,
                'changed_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $newValues = [
                'status' => $newStatus,
                'effective_date' => $data['effective_date'],
                'reason' => $data['reason'],
                'status_history_id' => $history->id,
                'graduation_date' => optional($lockedStudent->fresh()->graduation_date)->toDateString(),
            ];

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $lockedStudent,
                StudentLifecycleRules::auditActionFor($newStatus),
                $oldValues,
                $newValues,
                $data['reason'],
                $request
            );

            if (StudentLifecycleRules::auditActionFor($newStatus) !== 'student.status.changed') {
                $this->auditLogger->record(
                    $tenantId,
                    $actor,
                    $lockedStudent,
                    'student.status.changed',
                    $oldValues,
                    $newValues,
                    $data['reason'],
                    $request
                );
            }

            return $lockedStudent->fresh();
        });
    }
}
