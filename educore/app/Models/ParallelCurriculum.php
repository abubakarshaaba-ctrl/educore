<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParallelCurriculum extends BaseTenantModel
{
    protected $fillable = ['tenant_id','name','code','default_assessment_template_id','is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function defaultAssessmentTemplate(): BelongsTo
    {
        return $this->belongsTo(AssessmentTemplate::class, 'default_assessment_template_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(ParallelCurriculumSubject::class)->orderBy('name');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ParallelCurriculumClass::class)->orderBy('sort_order')->orderBy('name');
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(ParallelCurriculumIntegration::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(ParallelCurriculumGrade::class)
            ->orderByDesc('min_score')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function promotionRules(): HasMany
    {
        return $this->hasMany(ParallelCurriculumPromotionRule::class)
            ->orderBy('source_class_id');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(ParallelCurriculumPromotion::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(ParallelCurriculumTransfer::class);
    }

    public function reportPublications(): HasMany
    {
        return $this->hasMany(ParallelCurriculumReportPublication::class);
    }
}
