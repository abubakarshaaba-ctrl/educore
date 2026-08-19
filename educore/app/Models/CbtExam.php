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
        'created_by', 'malpractice_enabled', 'focus_loss_policy', 'max_focus_losses',
        'require_fullscreen', 'retake_policy', 'strict_marks_validation',
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
            'malpractice_enabled'       => 'boolean',
            'max_focus_losses'          => 'integer',
            'require_fullscreen'        => 'boolean',
            'strict_marks_validation'   => 'boolean',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function questionBank(): BelongsTo { return $this->belongsTo(CbtQuestionBank::class, 'question_bank_id'); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function classArm(): BelongsTo { return $this->belongsTo(ClassArm::class); }
    public function studentSessions(): HasMany { return $this->hasMany(CbtStudentSession::class); }
    public function assessmentType(): BelongsTo { return $this->belongsTo(AssessmentType::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function sections(): HasMany { return $this->hasMany(CbtExamSection::class)->orderBy('display_order'); }
    public function retakeAuthorizations(): HasMany { return $this->hasMany(CbtRetakeAuthorization::class); }
    public function integrityEvents(): HasMany { return $this->hasMany(CbtIntegrityEvent::class); }

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

    public function configuredQuestions()
    {
        return CbtQuestion::query()
            ->join('cbt_exam_section_questions', 'cbt_questions.id', '=', 'cbt_exam_section_questions.cbt_question_id')
            ->where('cbt_exam_section_questions.cbt_exam_id', $this->id)
            ->select('cbt_questions.*')
            ->orderBy('cbt_exam_section_questions.display_order');
    }

    public function isActive(): bool { return $this->status === 'active'; }
    public function isClosed(): bool { return $this->status === 'closed'; }
}
