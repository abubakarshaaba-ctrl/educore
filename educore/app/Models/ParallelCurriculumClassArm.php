<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParallelCurriculumClassArm extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_class_id',
        'name',
        'code',
        'capacity',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function curriculumClass(): BelongsTo
    {
        return $this->belongsTo(
            ParallelCurriculumClass::class,
            'parallel_curriculum_class_id'
        );
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(
            ParallelCurriculumEnrolment::class,
            'parallel_curriculum_class_arm_id'
        );
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->curriculumClass?->name ?? '').' '.$this->name);
    }
}
