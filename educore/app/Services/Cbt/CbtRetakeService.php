<?php

namespace App\Services\Cbt;

use App\Models\AuditLog;
use App\Models\CbtExam;
use App\Models\CbtRetakeAuthorization;
use App\Models\CbtStudentSession;
use App\Models\Student;
use App\Models\User;
use App\Models\ReportCardPublication;
use Illuminate\Support\Facades\DB;

class CbtRetakeService
{
    public function authorize(CbtExam $exam, Student $student, User $actor, string $reason): CbtRetakeAuthorization
    {
        abort_unless((int) $exam->tenant_id === (int) $student->tenant_id && (int) $exam->tenant_id === (int) $actor->tenant_id, 403);
        abort_if(ReportCardPublication::where('class_arm_id', $exam->class_arm_id)->where('term_id', $exam->term_id)->where('status', 'published')->exists(), 423, 'Unpublish the report cards before authorizing a retake.');
        abort_unless(CbtStudentSession::where('cbt_exam_id', $exam->id)->where('student_id', $student->id)->whereIn('status', CbtStudentSession::FINAL_STATUSES)->exists(), 422, 'A retake can only be authorized after a completed attempt.');
        return DB::transaction(function () use ($exam, $student, $actor, $reason) {
            $next = ((int) CbtStudentSession::where('cbt_exam_id', $exam->id)->where('student_id', $student->id)->lockForUpdate()->max('attempt_number')) + 1;
            abort_if(CbtRetakeAuthorization::where('cbt_exam_id', $exam->id)->where('student_id', $student->id)->whereNull('used_at')->whereNull('revoked_at')->exists(), 422, 'An unused retake authorization already exists.');
            $authorization = CbtRetakeAuthorization::create([
                'tenant_id' => $exam->tenant_id, 'cbt_exam_id' => $exam->id, 'student_id' => $student->id,
                'authorized_by' => $actor->id, 'attempt_number' => $next, 'reason' => $reason, 'authorized_at' => now(),
            ]);
            AuditLog::create(['tenant_id' => $exam->tenant_id, 'actor_user_id' => $actor->id, 'auditable_type' => CbtRetakeAuthorization::class, 'auditable_id' => $authorization->id, 'action' => 'cbt.retake.authorized', 'reason' => $reason, 'new_values' => ['student_id' => $student->id, 'exam_id' => $exam->id, 'attempt_number' => $next]]);
            return $authorization;
        });
    }

    public function revoke(CbtRetakeAuthorization $authorization, User $actor, string $reason): void
    {
        abort_unless((int) $authorization->tenant_id === (int) $actor->tenant_id, 403);
        abort_if($authorization->used_at, 422, 'A used retake authorization cannot be revoked. Invalidate the resulting attempt instead.');
        $authorization->update(['revoked_at' => now(), 'revoked_by' => $actor->id, 'revocation_reason' => $reason]);
        AuditLog::create(['tenant_id' => $authorization->tenant_id, 'actor_user_id' => $actor->id, 'auditable_type' => CbtRetakeAuthorization::class, 'auditable_id' => $authorization->id, 'action' => 'cbt.retake.revoked', 'reason' => $reason]);
    }
}
