<?php

namespace App\Services\Cbt;

use App\Models\AuditLog;
use App\Models\CbtQuestionScore;
use App\Models\CbtSectionAttempt;
use App\Models\CbtStudentSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

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
            $pending = CbtQuestionScore::with(['question', 'section'])
                ->where('cbt_student_session_id', $locked->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->get();
            $groups = $this->manualScoreGroups($locked, $pending);
            foreach ($groups as $group) {
                $questionId = $group['question_id'];
                if (! array_key_exists($questionId, $scores)) {
                    abort_if($group['required'], 422, 'Every pending question in a required theory section must be scored.');
                    continue;
                }
                $value = round((float) $scores[$questionId], 2);
                abort_if($value < 0 || $value > $group['maximum_score'], 422, 'A question score exceeds its maximum mark.');

                // The marker enters one total for the complete parent question.
                // Existing leaf score rows remain an internal audit structure only.
                $remaining = $value;
                $rows = $group['scores']->values();
                foreach ($rows as $index => $questionScore) {
                    $maximum = (float) $questionScore->maximum_score;
                    $allocated = $index === $rows->count() - 1
                        ? $remaining
                        : min($maximum, $remaining);
                    $allocated = round(max(0, min($maximum, $allocated)), 2);
                    $remaining = round($remaining - $allocated, 2);
                    $questionScore->update(['score' => $allocated, 'status' => 'scored', 'scored_by' => $markerId, 'scored_at' => now()]);
                }
                abort_if(abs($remaining) > 0.01, 422, 'The question score could not be allocated within its maximum mark.');
            }
            $exam = $locked->exam()->with('sections')->firstOrFail();
            $this->aggregateSections($locked, $exam);
            $includedScores = CbtQuestionScore::where('cbt_student_session_id', $locked->id)->where('status', 'scored')->get();
            $raw = (float) $includedScores->sum('score');
            $maximum = (float) $includedScores->sum('maximum_score');
            $requiredSectionIds = $exam->sections->where('is_required', true)->pluck('id');
            $stillPending = CbtQuestionScore::where('cbt_student_session_id', $locked->id)->whereIn('cbt_exam_section_id', $requiredSectionIds)->where('status', 'pending')->exists();
            $locked->forceFill([
                'manual_scores' => array_replace((array) $locked->manual_scores, $scores), 'marked_by' => $markerId, 'score' => $raw,
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

    /**
     * Return one marking unit per top-level theory question. A question such
     * as 1(a)(i)–1(b)(ii) therefore produces one input whose maximum is the
     * sum of all pending scored branches.
     */
    public function pendingManualGroups(CbtStudentSession $session): Collection
    {
        $pending = $session->relationLoaded('questionScores')
            ? $session->questionScores->where('status', 'pending')->values()
            : CbtQuestionScore::with(['question', 'section'])
                ->where('cbt_student_session_id', $session->id)
                ->where('status', 'pending')
                ->get();

        $pending->loadMissing(['question', 'section']);

        return $this->manualScoreGroups($session, $pending);
    }

    private function manualScoreGroups(CbtStudentSession $session, Collection $pending): Collection
    {
        $questions = $session->resolvedQuestions()->keyBy('id');
        $order = array_flip($session->questionIds());
        $groups = [];

        foreach ($pending as $questionScore) {
            $question = $questions->get((int) $questionScore->cbt_question_id) ?: $questionScore->question;
            if (! $question) continue;

            $root = $question;
            $visited = [];
            while ($root->parent_question_id && ! isset($visited[$root->id])) {
                $visited[$root->id] = true;
                $parent = $questions->get((int) $root->parent_question_id);
                if (! $parent) break;
                $root = $parent;
            }

            $key = (int) $root->id;
            $groups[$key] ??= [
                'question_id' => $key,
                'question' => $root,
                'maximum_score' => 0.0,
                'branch_count' => 0,
                'required' => false,
                'sort_order' => $order[$key] ?? PHP_INT_MAX,
                'scores' => collect(),
            ];
            $groups[$key]['maximum_score'] += (float) $questionScore->maximum_score;
            $groups[$key]['branch_count']++;
            $groups[$key]['required'] = $groups[$key]['required'] || (bool) $questionScore->section?->is_required;
            $groups[$key]['sort_order'] = min($groups[$key]['sort_order'], $order[(int) $questionScore->cbt_question_id] ?? PHP_INT_MAX);
            $groups[$key]['scores']->push($questionScore);
        }

        return collect($groups)->sortBy('sort_order')->values();
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
