<?php

namespace App\Services\Cbt;

use App\Models\AuditLog;
use App\Models\CbtQuestionScore;
use App\Models\CbtSectionAttempt;
use App\Models\CbtStudentSession;
use Illuminate\Support\Facades\DB;

class CbtSubmissionService
{
    public function __construct(private readonly CbtResultSyncService $resultSync) {}

    public function submit(CbtStudentSession $session, array $answers = [], array $essayAnswers = [], string $reason = 'student_submit', bool $automatic = false): CbtStudentSession
    {
        $session = DB::transaction(function () use ($session, $answers, $essayAnswers, $reason, $automatic) {
            $locked = CbtStudentSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($locked->isFinal()) return $locked;

            $answers = array_replace((array) $locked->answers, $answers);
            $essayAnswers = array_replace((array) $locked->essay_answers, $essayAnswers);
            $questions = $locked->resolvedQuestions();
            $exam = $locked->exam()->with('sections.questions')->firstOrFail();
            $deadline = $locked->started_at?->copy()->addMinutes((int) $exam->duration_minutes);
            if ($exam->scheduled_end && (! $deadline || $exam->scheduled_end->lt($deadline))) $deadline = $exam->scheduled_end;
            if ($deadline && now()->gte($deadline)) { $automatic = true; $reason = 'time_expired'; }
            $assignment = DB::table('cbt_exam_section_questions')->where('cbt_exam_id', $exam->id)
                ->whereIn('cbt_question_id', $questions->pluck('id'))->get()->keyBy('cbt_question_id');
            $parentIds = $questions->pluck('parent_question_id')->filter()->map(fn ($id) => (int) $id)->all();

            $hasPendingRequired = false;
            foreach ($questions as $question) {
                $pivot = $assignment->get($question->id);
                $section = $pivot ? $exam->sections->firstWhere('id', $pivot->cbt_exam_section_id) : null;
                $isLeaf = ! in_array((int) $question->id, $parentIds, true);
                $maximum = $question->countsForMarks() && $isLeaf ? (float) ($pivot->marks_override ?? $question->marks ?? 1) : 0;
                $method = $question->scoring_method ?: match ($section?->scoring_method) {
                    'automatic' => 'automatic',
                    'manual' => 'manual',
                    default => $question->isAutoGraded() ? 'automatic' : 'manual',
                };
                if ($section?->answer_mode === 'paper') $method = 'manual';
                $score = null;
                $status = 'not_required';
                if ($question->countsForMarks() && $isLeaf) {
                    if ($method === 'automatic') {
                        $score = $question->isCorrect((string) ($answers[$question->id] ?? '')) ? $maximum : 0;
                        $status = 'scored';
                    } else {
                        $status = 'pending';
                        if ($section?->is_required ?? true) $hasPendingRequired = true;
                    }
                }
                CbtQuestionScore::updateOrCreate([
                    'cbt_student_session_id' => $locked->id, 'cbt_question_id' => $question->id,
                ], [
                    'tenant_id' => $locked->tenant_id, 'cbt_exam_section_id' => $pivot?->cbt_exam_section_id,
                    'score' => $score, 'maximum_score' => $maximum, 'scoring_method' => $method,
                    'status' => $status, 'scored_at' => $status === 'scored' ? now() : null,
                ]);
            }

            $this->aggregateSections($locked, $exam);
            $includedScores = CbtQuestionScore::where('cbt_student_session_id', $locked->id)->where('status', 'scored')->get();
            $scored = (float) $includedScores->sum('score');
            $maximum = (float) $includedScores->sum('maximum_score');
            $fullyScored = ! $hasPendingRequired;
            $locked->forceFill([
                'answers' => $answers, 'essay_answers' => $essayAnswers,
                'score' => $scored, 'raw_score' => $fullyScored ? $scored : null,
                'maximum_score' => $maximum, 'percentage' => $fullyScored && $maximum > 0 ? round(($scored / $maximum) * 100, 2) : null,
                'submitted_at' => now(), 'submission_reason' => $reason,
                'status' => $automatic ? 'auto_submitted' : ($fullyScored ? 'graded' : 'submitted'),
                'grading_completed_at' => $fullyScored ? now() : null,
            ])->save();

            AuditLog::create([
                'tenant_id' => $locked->tenant_id, 'actor_user_id' => auth()->id(),
                'auditable_type' => CbtStudentSession::class, 'auditable_id' => $locked->id,
                'action' => $automatic ? 'cbt.attempt.auto_submitted' : 'cbt.attempt.submitted',
                'reason' => $reason, 'new_values' => ['status' => $locked->status, 'attempt_number' => $locked->attempt_number],
                'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent(),
            ]);
            return $locked->fresh();
        });

        if ($session->isFullyScored()) $this->resultSync->sync($session);
        return $session;
    }

    public function grade(CbtStudentSession $session, array $scores, int $markerId): CbtStudentSession
    {
        $session = DB::transaction(function () use ($session, $scores, $markerId) {
            $locked = CbtStudentSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->isInvalid(), 422, 'This attempt cannot be graded.');
            $pending = CbtQuestionScore::where('cbt_student_session_id', $locked->id)->where('status', 'pending')->lockForUpdate()->get();
            foreach ($pending as $questionScore) {
                $required = (bool) $questionScore->section?->is_required;
                if (! array_key_exists($questionScore->cbt_question_id, $scores)) {
                    abort_if($required, 422, 'Every pending response in a required section must be scored.');
                    continue;
                }
                $value = (float) $scores[$questionScore->cbt_question_id];
                abort_if($value < 0 || $value > $questionScore->maximum_score, 422, 'A question score exceeds its maximum mark.');
                $questionScore->update(['score' => $value, 'status' => 'scored', 'scored_by' => $markerId, 'scored_at' => now()]);
            }
            $exam = $locked->exam()->with('sections')->firstOrFail();
            $this->aggregateSections($locked, $exam);
            $includedScores = CbtQuestionScore::where('cbt_student_session_id', $locked->id)->where('status', 'scored')->get();
            $raw = (float) $includedScores->sum('score');
            $maximum = (float) $includedScores->sum('maximum_score');
            $requiredSectionIds = $exam->sections->where('is_required', true)->pluck('id');
            $stillPending = CbtQuestionScore::where('cbt_student_session_id', $locked->id)->whereIn('cbt_exam_section_id', $requiredSectionIds)->where('status', 'pending')->exists();
            $locked->forceFill([
                'manual_scores' => $scores, 'marked_by' => $markerId, 'score' => $raw,
                'raw_score' => $stillPending ? null : $raw, 'maximum_score' => $maximum,
                'percentage' => ! $stillPending && $maximum > 0 ? round(($raw / $maximum) * 100, 2) : null,
                'status' => $stillPending ? 'submitted' : 'graded', 'grading_completed_at' => $stillPending ? null : now(),
            ])->save();
            return $locked->fresh();
        });
        AuditLog::create([
            'tenant_id' => $session->tenant_id, 'actor_user_id' => $markerId,
            'auditable_type' => CbtStudentSession::class, 'auditable_id' => $session->id,
            'action' => 'cbt.attempt.manually_scored',
            'new_values' => ['attempt_number' => $session->attempt_number, 'raw_score' => $session->raw_score, 'maximum_score' => $session->maximum_score, 'status' => $session->status],
        ]);
        if ($session->isFullyScored()) $this->resultSync->sync($session);
        return $session;
    }

    private function aggregateSections(CbtStudentSession $session, $exam): void
    {
        foreach ($exam->sections as $section) {
            $query = CbtQuestionScore::where('cbt_student_session_id', $session->id)->where('cbt_exam_section_id', $section->id);
            $pending = (clone $query)->where('status', 'pending')->exists();
            $maximum = (float) (clone $query)->sum('maximum_score');
            $raw = (float) (clone $query)->where('status', 'scored')->sum('score');
            CbtSectionAttempt::updateOrCreate([
                'cbt_student_session_id' => $session->id, 'cbt_exam_section_id' => $section->id,
            ], [
                'tenant_id' => $session->tenant_id, 'raw_score' => $pending ? null : $raw,
                'maximum_score' => $maximum, 'status' => $pending ? 'pending' : 'scored',
                'scored_at' => $pending ? null : now(),
            ]);
        }
    }
}
