<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbtExamSection extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'cbt_exam_id', 'name', 'code', 'title', 'instructions',
        'display_order', 'section_type', 'scoring_method', 'answer_mode',
        'max_marks', 'is_required', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'max_marks' => 'float',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function exam(): BelongsTo { return $this->belongsTo(CbtExam::class, 'cbt_exam_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function sectionAttempts(): HasMany { return $this->hasMany(CbtSectionAttempt::class); }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(CbtQuestion::class, 'cbt_exam_section_questions')
            ->withPivot(['display_order', 'marks_override'])
            ->withTimestamps()
            ->orderBy('cbt_exam_section_questions.display_order');
    }

    public function assignedMarks(): float
    {
        $parentIds = $this->questions->pluck('parent_question_id')->filter()->map(fn ($id) => (int) $id)->all();
        return round((float) $this->questions->sum(function (CbtQuestion $question) use ($parentIds) {
            if (in_array((int) $question->id, $parentIds, true) || $question->is_instruction_only || ! $question->requires_answer) return 0;
            return (float) ($question->pivot->marks_override ?? $question->marks ?? 0);
        }), 2);
    }
}
