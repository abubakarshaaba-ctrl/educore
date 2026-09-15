<?php

namespace App\Services\Cbt;

use App\Models\AuditLog;
use App\Models\CbtStudentSession;
use App\Models\ReportCardPublication;
use App\Models\Score;
use Illuminate\Support\Facades\DB;

class CbtResultSyncService
{
    public function sync(CbtStudentSession $completed): array
    {
        $completed->loadMissing('exam.questionBank.subject', 'exam.assessmentType', 'exam.term', 'student');
        $exam = $completed->exam;
        if (! $exam?->assessment_type_id) {
            return ['synced' => false, 'reason' => 'not_linked'];
        }
        if (! $completed->isFullyScored()) {
            return ['synced' => false, 'reason' => 'pending_manual_scoring'];
        }

        $assessment = $exam->assessmentType;
        if (! $assessment || ! $assessment->is_exam || (int) $assessment->term_id !== (int) $exam->term_id) {
            return ['synced' => false, 'reason' => 'invalid_exam_component'];
        }

        $studentClassArmId = $completed->student?->current_class_arm_id ?: $exam->class_arm_id;
        $published = ReportCardPublication::where('class_arm_id', $studentClassArmId)
            ->where('term_id', $exam->term_id)
            ->where('status', 'published')
            ->exists();
        if ($published) {
            return ['synced' => false, 'reason' => 'result_published'];
        }

        $active = CbtStudentSession::where('cbt_exam_id', $exam->id)
            ->where('student_id', $completed->student_id)
            ->where('is_authorized_attempt', true)
            ->whereNotIn('status', ['invalidated', 'cancelled'])
            ->whereNotNull('grading_completed_at')
            ->orderByDesc('attempt_number')
            ->first();
        if (! $active) {
            return ['synced' => false, 'reason' => 'no_valid_attempt'];
        }

        return DB::transaction(function () use ($active, $exam, $assessment) {
            CbtStudentSession::where('cbt_exam_id', $exam->id)
                ->where('student_id', $active->student_id)
                ->update(['is_active_result' => false]);
            $active->forceFill(['is_active_result' => true])->save();

            // raw_score and maximum_score are produced entirely by the CBT
            // scoring pipeline. Objective questions are auto-scored and theory
            // questions are teacher-scored on the CBT interface. Only the final
            // aggregate is converted to the assessment-template Exam weight.
            $maximum = (float) ($active->maximum_score ?: $exam->total_marks);
            $weighted = $maximum > 0
                ? round(((float) $active->raw_score / $maximum) * (float) $assessment->weight_percentage, 2)
                : 0;

            $score = Score::updateOrCreate([
                'tenant_id' => $exam->tenant_id,
                'student_id' => $active->student_id,
                'subject_id' => $exam->questionBank->subject_id,
                'assessment_type_id' => $assessment->id,
                'term_id' => $exam->term_id,
            ], [
                'tenant_id' => $exam->tenant_id,
                'session_id' => $exam->term->session_id,
                'score' => min($weighted, (float) $assessment->weight_percentage),
                // Breakdown remains authoritative in CBT question/section scores.
                'objective_score' => null,
                'theory_score' => null,
                'cbt_exam_id' => $exam->id,
                'entered_by' => $active->marked_by,
                'entered_at' => now(),
                'score_source' => 'cbt',
                'source_reference_type' => CbtStudentSession::class,
                'source_reference_id' => $active->id,
                'is_source_locked' => true,
                'source_synced_at' => now(),
            ]);

            AuditLog::create([
                'tenant_id' => $exam->tenant_id,
                'actor_user_id' => $active->marked_by,
                'auditable_type' => Score::class,
                'auditable_id' => $score->id,
                'action' => 'cbt.result.synced',
                'new_values' => [
                    'session_id' => $active->id,
                    'attempt_number' => $active->attempt_number,
                    'raw_score' => $active->raw_score,
                    'maximum_score' => $maximum,
                    'exam_weight' => $assessment->weight_percentage,
                    'weighted_score' => $score->score,
                ],
            ]);

            return ['synced' => true, 'score' => $score];
        });
    }
}
