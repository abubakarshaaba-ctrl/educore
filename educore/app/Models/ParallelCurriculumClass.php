<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParallelCurriculumClass extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id','parallel_curriculum_id','assessment_template_id',
        'name','code','sort_order','is_active',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculum::class, 'parallel_curriculum_id');
    }

    public function assessmentTemplate(): BelongsTo
    {
        return $this->belongsTo(AssessmentTemplate::class, 'assessment_template_id');
    }

    public function arms(): HasMany
    {
        return $this->hasMany(ParallelCurriculumClassArm::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function classGrades(): HasMany
    {
        return $this->hasMany(ParallelCurriculumClassGrade::class)
            ->orderByDesc('min_score')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function promotionRule(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ParallelCurriculumPromotionRule::class, 'source_class_id');
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(ParallelCurriculumClassSubject::class);
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(ParallelCurriculumEnrolment::class);
    }

    public function effectiveAssessmentTemplate(): ?AssessmentTemplate
    {
        return $this->assessmentTemplate ?: $this->curriculum?->defaultAssessmentTemplate;
    }
}
