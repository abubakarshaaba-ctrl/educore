<?php

namespace App\Services\Cbt;

use App\Models\CbtExam;
use App\Models\CbtExamSection;
use App\Models\CbtQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CbtExamConfigurationService
{
    public function publicationErrors(CbtExam $exam): array
    {
        $exam->load('sections.questions');
        $errors = [];
        $sections = $exam->sections->where('is_active', true);
        if ($sections->isEmpty()) $errors[] = 'Add at least one active section.';

        foreach ($sections as $section) {
            if ($section->questions->isEmpty()) {
                $errors[] = "{$section->name} has no questions.";
                continue;
            }
            if ($section->answer_mode === 'paper' && $section->scoring_method === 'automatic') {
                $errors[] = "{$section->name} uses a paper answer mode and cannot use automatic scoring.";
            }
            $questionIds = $section->questions->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach ($section->questions as $question) {
                if ($question->parent_question_id && ! in_array((int) $question->parent_question_id, $questionIds, true)) {
                    $errors[] = "{$section->name} contains a child question without its parent instruction.";
                }
                if ($section->scoring_method === 'automatic' && $question->countsForMarks() && ! $question->isAutoGraded()) {
                    $errors[] = "{$section->name} contains a manually scored question but is configured for automatic scoring.";
                }
                if ($question->countsForMarks() && $question->isAutoGraded()) {
                    $hasAnswer = $question->isFillBlank() ? filled($question->model_answer) : filled($question->correct_answer_letter);
                    if (! $hasAnswer) $errors[] = "{$section->name} contains an automatic question without a correct answer.";
                }
            }
            $assigned = $section->assignedMarks();
            if ($exam->strict_marks_validation && abs($assigned - (float) $section->max_marks) > 0.009) {
                $errors[] = "{$section->name} is {$assigned} marks but its maximum is {$section->max_marks}.";
            }
        }
        return $errors;
    }

    public function recalculateExamTotals(CbtExam $exam): void
    {
        $exam->load('sections.questions');
        $exam->forceFill([
            'total_questions' => $exam->sections->where('is_active', true)->sum(function ($section) {
                $parentIds = $section->questions->pluck('parent_question_id')->filter()->map(fn ($id) => (int) $id)->all();
                return $section->questions->filter(fn ($question) => $question->countsForMarks() && ! in_array((int) $question->id, $parentIds, true))->count();
            }),
            'total_marks' => round($exam->sections->where('is_active', true)->sum('max_marks'), 2),
        ])->save();
    }

    public function questionIdsForAttempt(CbtExam $exam): array
    {
        $exam->load('sections.questions');
        if ($exam->sections->isNotEmpty()) {
            return $exam->sections->where('is_active', true)->sortBy('display_order')->flatMap(function (CbtExamSection $section) use ($exam) {
                $questions = $section->questions;
                if ($exam->shuffle_questions && ! $questions->contains(fn ($q) => $q->parent_question_id)) {
                    $questions = $questions->shuffle();
                }
                return $questions->pluck('id');
            })->unique()->map(fn ($id) => (int) $id)->values()->all();
        }

        return $exam->questions()->inRandomOrder()->limit($exam->total_questions)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function sectionPayload(CbtExam $exam, Collection $attemptQuestions): Collection
    {
        $exam->load('sections.questions');
        $allowed = $attemptQuestions->keyBy('id');
        $numbering = app(CbtQuestionNumberingService::class);

        return $exam->sections->where('is_active', true)->sortBy('display_order')->map(function (CbtExamSection $section) use ($allowed, $numbering) {
            $questions = $section->questions->filter(fn ($q) => $allowed->has($q->id));
            return ['section' => $section, 'questions' => $numbering->number($questions)];
        })->values();
    }

    public function createDefaultSections(CbtExam $exam, int $objectiveCount, float $objectiveMark, int $theoryCount, float $theoryMark, ?int $actorId): void
    {
        DB::transaction(function () use ($exam, $objectiveCount, $objectiveMark, $theoryCount, $theoryMark, $actorId) {
            $definitions = [];
            if ($objectiveCount > 0) $definitions[] = ['A', 'Section A', 'Objective Questions', 'objective', 'automatic', $objectiveCount, $objectiveMark, ['mcq', 'true_false', 'fill_blank']];
            if ($theoryCount > 0) $definitions[] = ['B', 'Section B', 'Theory Questions', 'theory', 'manual', $theoryCount, $theoryMark, ['essay', 'short_answer']];

            foreach ($definitions as $position => [$code, $name, $title, $type, $method, $count, $mark, $types]) {
                $section = $exam->sections()->create([
                    'tenant_id' => $exam->tenant_id, 'name' => $name, 'code' => $code, 'title' => $title,
                    'display_order' => $position + 1, 'section_type' => $type, 'scoring_method' => $method,
                    'answer_mode' => 'online', 'max_marks' => round($count * $mark, 2),
                    'is_required' => true, 'is_active' => true, 'created_by' => $actorId,
                ]);
                $ids = CbtQuestion::where('question_bank_id', $exam->question_bank_id)->whereIn('type', $types)->inRandomOrder()->limit($count)->pluck('id');
                foreach ($ids as $index => $id) {
                    $section->questions()->attach($id, ['tenant_id' => $exam->tenant_id, 'cbt_exam_id' => $exam->id, 'display_order' => $index + 1, 'marks_override' => $mark]);
                }
            }
            $this->recalculateExamTotals($exam->fresh());
        });
    }
}
