<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbtStudentSession extends BaseTenantModel
{
    public const FINAL_STATUSES = ['submitted', 'graded', 'completed', 'timed_out', 'auto_submitted', 'expired', 'cancelled', 'invalidated'];

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
        'attempt_number', 'is_authorized_attempt', 'is_active_result', 'retake_authorization_id',
        'integrity_acknowledged_at', 'focus_loss_count', 'submission_reason',
        'raw_score', 'maximum_score', 'grading_completed_at',
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
            'attempt_number'     => 'integer',
            'is_authorized_attempt' => 'boolean',
            'is_active_result'   => 'boolean',
            'integrity_acknowledged_at' => 'datetime',
            'focus_loss_count'   => 'integer',
            'raw_score'          => 'float',
            'maximum_score'      => 'float',
            'grading_completed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo  { return $this->belongsTo(Tenant::class); }
    public function exam(): BelongsTo    { return $this->belongsTo(CbtExam::class, 'cbt_exam_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function marker(): BelongsTo  { return $this->belongsTo(User::class, 'marked_by'); }
    public function retakeAuthorization(): BelongsTo { return $this->belongsTo(CbtRetakeAuthorization::class); }
    public function integrityEvents(): HasMany { return $this->hasMany(CbtIntegrityEvent::class); }
    public function sectionAttempts(): HasMany { return $this->hasMany(CbtSectionAttempt::class); }
    public function questionScores(): HasMany { return $this->hasMany(CbtQuestionScore::class); }

    public function isSubmitted(): bool  { return in_array($this->status, ['submitted', 'auto_submitted'], true); }
    public function isInProgress(): bool { return $this->status === 'in_progress'; }
    public function isGraded(): bool     { return $this->status === 'graded'; }
    public function awaitingMarking(): bool { return in_array($this->status, ['submitted', 'auto_submitted'], true) && ! $this->isFullyScored(); }
    public function isFinal(): bool      { return in_array($this->status, self::FINAL_STATUSES, true); }
    public function isInvalid(): bool { return in_array($this->status, ['invalidated', 'cancelled'], true); }
    public function isFullyScored(): bool { return $this->grading_completed_at !== null; }

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
