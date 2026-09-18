<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParallelCurriculumSubject extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id','parallel_curriculum_id','name','code','is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculum::class, 'parallel_curriculum_id');
    }

    public function classAssignments(): HasMany
    {
        return $this->hasMany(ParallelCurriculumClassSubject::class, 'parallel_curriculum_subject_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ParallelCurriculumScore::class, 'parallel_curriculum_subject_id');
    }
}
