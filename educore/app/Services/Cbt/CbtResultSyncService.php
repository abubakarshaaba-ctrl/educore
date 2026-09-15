<?php

namespace App\Services\Cbt;

use App\Models\AssessmentType;
use App\Models\AuditLog;
use App\Models\CbtStudentSession;
use App\Models\ClassArm;
use App\Models\ReportCardPublication;
use App\Models\Score;
use Illuminate\Support\Facades\DB;

class CbtResultSyncService
{
    public function sync(CbtStudentSession $completed): array
    {
        $completed->loadMissing('exam.questionBank.subject', 'exam.assessmentType', 'exam.term', 'student');
        $exam = $completed->exam;
        if (! $exam) {
            return ['synced' => false, 'reason' => 'exam_missing'];
        }
        if (! $completed->isFullyScored()) {
            return ['synced' => false, 'reason' => 'pending_manual_scoring'];
        }

        $studentClassArmId = (int) ($completed->student?->current_class_arm_id ?: $exam->class_arm_id);
        $assessment = $this->resolveExamAssessment($exam, $studentClassArmId);
        if (! $assessment) {
            return ['synced' => false, 'reason' => 'exam_component_not_configured'];
        }

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

            // The CBT module owns raw scoring. Objective questions are scored
            // automatically; theory is entered manually by the teacher on the
            // CBT interface. Only the final aggregate is converted to the
            // school-configured Exam weight on the score sheet.
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
                    'assessment_type_id' => $assessment->id,
                    'exam_weight' => $assessment->weight_percentage,
                    'weighted_score' => $score->score,
                ],
            ]);

            return ['synced' => true, 'score' => $score];
        });
    }

    /**
     * Resolve the score-sheet Exam component from the student's configured
     * class-level assessment template. A manually selected assessment_type_id
     * is accepted only when it is the same term, is an Exam component and is
     * valid for that class level. This keeps legacy exams working while making
     * the template/class configuration the authoritative mapping.
     */
    private function resolveExamAssessment($exam, int $classArmId): ?AssessmentType
    {
        $classLevelId = (int) ClassArm::withoutTenantScope()
            ->where('tenant_id', $exam->tenant_id)
            ->whereKey($classArmId)
            ->value('class_level_id');

        if ($classLevelId <= 0) {
            return null;
        }

        $resolved = AssessmentType::withoutTenantScope()
            ->where('tenant_id', $exam->tenant_id)
            ->where('term_id', $exam->term_id)
            ->whereHas('classLevels', fn ($query) => $query->where('class_levels.id', $classLevelId))
            ->orderBy('is_exam')
            ->orderBy('weight_percentage')
            ->orderBy('name')
            ->get();

        if ($resolved->isEmpty()) {
            $resolved = AssessmentType::withoutTenantScope()
                ->where('tenant_id', $exam->tenant_id)
                ->where('term_id', $exam->term_id)
                ->whereDoesntHave('classLevels')
                ->orderBy('is_exam')
                ->orderBy('weight_percentage')
                ->orderBy('name')
                ->get();
        }

        $examComponents = $resolved->where('is_exam', true)->values();
        if ($examComponents->isEmpty()) {
            return null;
        }

        if ($exam->assessment_type_id) {
            $linked = $examComponents->firstWhere('id', (int) $exam->assessment_type_id);
            if ($linked) {
                return $linked;
            }
        }

        // The assessment template is expected to expose one final Exam column.
        // If legacy data contains several Exam-type rows, prefer the highest
        // weight because that represents the final examination contribution.
        return $examComponents->sortByDesc('weight_percentage')->first();
    }
}
