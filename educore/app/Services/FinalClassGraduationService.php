<?php

namespace App\Services;

use App\Models\AlumniProfile;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentStatusHistory;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FinalClassGraduationService
{
    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
    }

    /**
     * Graduate active students in the tenant's final class level when the
     * third term is closed. The operation is intentionally idempotent:
     * only active students with a current enrolment in the source session
     * are considered, and alumni profiles are created only when absent.
     *
     * @return array{eligible: bool, graduated: int, skipped: int, final_class_level_ids: array<int>}
     */
    public function transitionForClosedTerm(Term $term, ?User $actor = null): array
    {
        $tenantId = (int) $term->tenant_id;

        if (!$this->isThirdTerm($term)) {
            return [
                'eligible' => false,
                'graduated' => 0,
                'skipped' => 0,
                'final_class_level_ids' => [],
            ];
        }

        $maxOrder = ClassLevel::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->max('order_index');

        if ($maxOrder === null) {
            return [
                'eligible' => true,
                'graduated' => 0,
                'skipped' => 0,
                'final_class_level_ids' => [],
            ];
        }

        $finalLevelIds = ClassLevel::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('order_index', $maxOrder)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $finalArmIds = ClassArm::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereIn('class_level_id', $finalLevelIds)
            ->pluck('id');

        if ($finalArmIds->isEmpty()) {
            return [
                'eligible' => true,
                'graduated' => 0,
                'skipped' => 0,
                'final_class_level_ids' => $finalLevelIds,
            ];
        }

        $enrollmentIds = StudentEnrollment::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('session_id', $term->session_id)
            ->where('is_current', true)
            ->whereIn('class_arm_id', $finalArmIds)
            ->pluck('id');

        $graduated = 0;
        $skipped = 0;

        foreach ($enrollmentIds as $enrollmentId) {
            $didGraduate = DB::transaction(function () use ($tenantId, $term, $enrollmentId, $actor) {
                $enrollment = StudentEnrollment::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->whereKey((int) $enrollmentId)
                    ->lockForUpdate()
                    ->first();

                if (!$enrollment || !$enrollment->is_current || (int) $enrollment->session_id !== (int) $term->session_id) {
                    return false;
                }

                $student = Student::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($enrollment->student_id)
                    ->lockForUpdate()
                    ->first();

                if (!$student || $student->status !== Student::STATUS_ACTIVE) {
                    return false;
                }

                $graduationDate = $term->end_date?->toDateString() ?? now()->toDateString();
                $oldStatus = $student->status;
                $oldClassArmId = $student->current_class_arm_id;

                $enrollment->forceFill([
                    'is_current' => false,
                    'end_date' => $graduationDate,
                    'status' => StudentEnrollment::STATUS_GRADUATED,
                    'ended_by' => $actor?->id,
                    'ended_reason' => 'Final class graduation after third term',
                ])->save();

                $student->forceFill([
                    'status' => Student::STATUS_GRADUATED,
                    'graduation_date' => $graduationDate,
                    'current_class_arm_id' => null,
                ])->save();

                TermlySummary::withoutTenantScope()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'student_id' => $student->id,
                        'term_id' => $term->id,
                        'session_id' => $term->session_id,
                    ],
                    [
                        'class_arm_id' => $enrollment->class_arm_id,
                        'promotion_status' => 'graduated',
                    ]
                );

                StudentStatusHistory::withoutTenantScope()->create([
                    'tenant_id' => $tenantId,
                    'student_id' => $student->id,
                    'old_status' => $oldStatus,
                    'new_status' => Student::STATUS_GRADUATED,
                    'effective_date' => $graduationDate,
                    'reason' => 'Completed final class level after third term',
                    'changed_by' => $actor?->id,
                    'approved_by' => $actor?->id,
                    'approved_at' => now(),
                ]);

                AlumniProfile::withoutTenantScope()->firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'student_id' => $student->id,
                    ],
                    [
                        'graduation_year' => substr($graduationDate, 0, 4),
                    ]
                );

                $this->auditLogger->record(
                    $tenantId,
                    $actor,
                    $student,
                    'student.graduated_to_alumni',
                    [
                        'status' => $oldStatus,
                        'current_class_arm_id' => $oldClassArmId,
                        'enrollment_id' => $enrollment->id,
                    ],
                    [
                        'status' => Student::STATUS_GRADUATED,
                        'graduation_date' => $graduationDate,
                        'current_class_arm_id' => null,
                        'term_id' => $term->id,
                        'session_id' => $term->session_id,
                    ],
                    'Final class graduation after third term'
                );

                return true;
            });

            if ($didGraduate) {
                $graduated++;
            } else {
                $skipped++;
            }
        }

        return [
            'eligible' => true,
            'graduated' => $graduated,
            'skipped' => $skipped,
            'final_class_level_ids' => $finalLevelIds,
        ];
    }

    private function isThirdTerm(Term $term): bool
    {
        $termIds = Term::withoutTenantScope()
            ->where('tenant_id', $term->tenant_id)
            ->where('session_id', $term->session_id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        return $termIds->count() >= 3 && (int) $termIds->get(2) === (int) $term->id;
    }
}
