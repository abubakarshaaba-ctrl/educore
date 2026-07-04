<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbtStudentSession extends BaseTenantModel
{
    public const FINAL_STATUSES = ['submitted', 'graded', 'completed', 'timed_out'];

    protected $fillable = [
        'tenant_id',
        'cbt_exam_id',
        'student_id',
        'question_order',
        'answers',
        'essay_answers',      // JSON — essay type answers
        'flagged_questions',
        'started_at',
        'submitted_at',
        'last_synced_at',
        'score',
        'percentage',
        'status',             // in_progress | submitted | graded
        'manual_scores',      // JSON — teacher-assigned scores per question
        'marked_by',          // user_id of teacher who graded essays
    ];

    protected function casts(): array
    {
        return [
            'question_order'    => 'array',
            'answers'           => 'array',
            'essay_answers'     => 'array',
            'flagged_questions' => 'array',
            'manual_scores'     => 'array',
            'started_at'        => 'datetime',
            'submitted_at'      => 'datetime',
            'last_synced_at'    => 'datetime',
            'score'             => 'float',
            'percentage'        => 'float',
        ];
    }

    public function tenant(): BelongsTo  { return $this->belongsTo(Tenant::class); }
    public function exam(): BelongsTo    { return $this->belongsTo(CbtExam::class, 'cbt_exam_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function marker(): BelongsTo  { return $this->belongsTo(User::class, 'marked_by'); }

    public function isSubmitted(): bool  { return $this->status === 'submitted'; }
    public function isInProgress(): bool { return $this->status === 'in_progress'; }
    public function isGraded(): bool     { return $this->status === 'graded'; }
    public function awaitingMarking(): bool { return $this->status === 'submitted'; }
    public function isFinal(): bool      { return in_array($this->status, self::FINAL_STATUSES, true); }

    public function questionIds(): array
    {
        $ids = is_array($this->question_order) ? $this->question_order : [];

        if (!empty($ids)) {
            return array_values(array_map('intval', $ids));
        }

        if ($this->exam) {
            return $this->exam->questions()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return [];
    }

    public function resolvedQuestions(): Collection
    {
        $ids = $this->questionIds();

        if (empty($ids)) {
            return collect();
        }

        $questions = CbtQuestion::whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $questions->get($id))
            ->filter()
            ->values();
    }

    public function totalPossibleMarks(): float
    {
        return (float) $this->resolvedQuestions()->sum(fn ($question) => $question->marks ?? 1);
    }

    public function displayPercentage(): ?float
    {
        if ($this->percentage !== null) {
            return round((float) $this->percentage, 1);
        }

        $totalMarks = $this->totalPossibleMarks();

        if ($this->score === null || $totalMarks <= 0) {
            return null;
        }

        return round(((float) $this->score / $totalMarks) * 100, 1);
    }

    public function getTotalQuestionsAttribute(): int
    {
        if (is_array($this->question_order)) {
            return count($this->question_order);
        }

        return $this->exam ? $this->exam->questions()->count() : 0;
    }

    public function getTotalPossibleMarksAttribute(): float
    {
        return $this->totalPossibleMarks();
    }

    public function getDisplayPercentageAttribute(): ?float
    {
        return $this->displayPercentage();
    }
}
