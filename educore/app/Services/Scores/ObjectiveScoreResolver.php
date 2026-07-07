<?php

namespace App\Services\Scores;

use App\Models\AssessmentType;
use App\Models\CbtExam;
use App\Models\CbtStudentSession;
use App\Models\Student;

/**
 * Resolves a student's objective score for a split assessment type
 * (e.g. "Exam" = Objective from CBT + Theory entered manually).
 *
 * The CBT exam that feeds an assessment type is tagged explicitly via
 * CbtExam::assessment_type_id (set when the exam is created) — this
 * avoids ambiguity when a school runs more than one CBT for the same
 * subject/term (e.g. a CA CBT and a separate Exam CBT).
 */
class ObjectiveScoreResolver
{
    /**
     * Find the closed CBT exam feeding this assessment type for a class/subject/term.
     * If more than one exists (e.g. a retake), the most recently scheduled wins.
     */
    public function findExam(int $classArmId, int $subjectId, int $termId, AssessmentType $assessmentType): ?CbtExam
    {
        return CbtExam::where('class_arm_id', $classArmId)
            ->where('term_id', $termId)
            ->where('assessment_type_id', $assessmentType->id)
            ->where('status', 'closed')
            ->whereHas('questionBank', fn ($q) => $q->where('subject_id', $subjectId))
            ->orderByDesc('scheduled_start')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * The student's objective score, scaled to the assessment type's
     * objective_max. Returns null if no matching CBT exam or session exists
     * (e.g. the student did not sit it, or no CBT has been tagged yet).
     */
    public function resolve(Student $student, CbtExam $exam, AssessmentType $assessmentType): ?float
    {
        $session = CbtStudentSession::where('cbt_exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'graded'])
            ->first();

        if (!$session || !$exam->total_marks || $exam->total_marks <= 0) {
            return null;
        }

        $scaled = ($session->score / $exam->total_marks) * $assessmentType->objective_max;

        return round(max(0, min($scaled, $assessmentType->objective_max)), 1);
    }
}
