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

    public function workingDays(): HasMany
    {
        return $this->hasMany(ParallelCurriculumWorkingDay::class)
            ->orderByRaw("CASE day_of_week
                WHEN 'monday' THEN 1
                WHEN 'tuesday' THEN 2
                WHEN 'wednesday' THEN 3
                WHEN 'thursday' THEN 4
                WHEN 'friday' THEN 5
                WHEN 'saturday' THEN 6
                WHEN 'sunday' THEN 7
                ELSE 8 END");
    }

    public function staffAttendanceRecords(): HasMany
    {
        return $this->hasMany(
            ParallelCurriculumStaffAttendanceRecord::class,
            'parallel_curriculum_id'
        );
    }
}
