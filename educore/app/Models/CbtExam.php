<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use App\Models\CbtQuestion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbtExam extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'question_bank_id', 'term_id', 'class_arm_id', 'title',
        'duration_minutes', 'total_questions', 'total_marks',
        'section_objective_count', 'section_objective_marks',
        'section_theory_count',    'section_theory_marks',
        'scheduled_start', 'scheduled_end', 'shuffle_questions', 'shuffle_options', 'status',
        'assessment_type_id',
        'lan_sync_token', 'lan_exported_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_start'          => 'datetime',
            'scheduled_end'            => 'datetime',
            'lan_exported_at'          => 'datetime',
            'shuffle_questions'        => 'boolean',
            'shuffle_options'          => 'boolean',
            'total_marks'              => 'float',
            'section_objective_count'  => 'integer',
            'section_objective_marks'  => 'float',
            'section_theory_count'     => 'integer',
            'section_theory_marks'     => 'float',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function questionBank(): BelongsTo { return $this->belongsTo(CbtQuestionBank::class, 'question_bank_id'); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function classArm(): BelongsTo { return $this->belongsTo(ClassArm::class); }
    public function studentSessions(): HasMany { return $this->hasMany(CbtStudentSession::class); }
    public function assessmentType(): BelongsTo { return $this->belongsTo(AssessmentType::class); }

    public function getExamDateAttribute(): mixed
    {
        return $this->scheduled_start;
    }

    public function getSubjectAttribute(): ?Subject
    {
        if (!$this->relationLoaded('questionBank')) {
            $this->load('questionBank.subject');
        } elseif ($this->questionBank && !$this->questionBank->relationLoaded('subject')) {
            $this->questionBank->load('subject');
        }

        return $this->questionBank?->subject;
    }

    // Questions drawn from the linked bank (not a direct hasMany)
    public function questions()
    {
        return CbtQuestion::where('question_bank_id', $this->question_bank_id);
    }

    public function isActive(): bool { return $this->status === 'active'; }
    public function isClosed(): bool { return $this->status === 'closed'; }
}
