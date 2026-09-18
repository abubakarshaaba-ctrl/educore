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

    public function classes(): HasMany
    {
        return $this->hasMany(ParallelCurriculumClass::class)->orderBy('sort_order')->orderBy('name');
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(ParallelCurriculumIntegration::class);
    }
}
